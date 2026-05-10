<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\GameMatch;
use App\Entity\MatchmakingTicket;
use App\Repository\EquipoJugadorRepositorio;
use App\Repository\GameMatchRepository;
use App\Repository\JugadorPersonajeRepositorio;
use App\Repository\MatchmakingTicketRepository;
use App\Repository\PlayerProfileRepository;
use App\Support\UuidGenerator;
use Doctrine\DBAL\Connection;
use Random\Randomizer;
use RuntimeException;
use Throwable;

final class MatchmakingService
{
    private const BOT_NAME = 'SparringBot';
    private const RANKED_MMR_WINDOW = 75;
    private const TICKET_TTL_SECONDS = 90;

    public function __construct(
        private Connection $connection,
        private GameMatchRepository $matchRepository,
        private MatchmakingTicketRepository $ticketRepository,
        private PlayerProfileRepository $profileRepository,
        private JugadorPersonajeRepositorio $jugadorPersonajeRepositorio,
        private EquipoJugadorRepositorio $equipoJugadorRepositorio,
        private CombatStateFactory $combatStateFactory,
        private UuidGenerator $uuidGenerator,
    ) {
    }

    /** @param array<string, mixed> $body */
    public function enqueue(string $playerId, array $body): array
    {
        $this->normalizarEstadoMatchmaking();

        $activeMatch = $this->matchRepository->findActiveByPlayer($playerId);
        if ($activeMatch !== null) {
            return [
                'status' => 409,
                'data' => [
                    'error' => [
                        'code' => 'MATCH_ALREADY_ACTIVE',
                        'message' => 'Player already has an active match.',
                    ],
                    'matchId' => $activeMatch->id(),
                    'matchStatus' => $activeMatch->status(),
                ],
            ];
        }

        $selection = $this->resolveQueueSelection($body);
        $queue = $selection['queue'];
        $mode = $selection['mode'];
        $vsBot = $selection['vsBot'];
        $equipoActivo = $this->resolverEquipoActivo($playerId);
        if ($equipoActivo === null) {
            return ['status' => 409, 'data' => ['error' => ['code' => 'EQUIPO_INCOMPLETO', 'message' => 'Configura un equipo de 3 personajes antes de entrar en cola.']]];
        }
        $personajePrincipal = $equipoActivo['principal'];

        if ($queue === 'ranked' && !$vsBot) {
            $existingActiveTicket = $this->ticketRepository->findActiveByPlayer($playerId);
            if ($existingActiveTicket !== null) {
                return [
                    'status' => $existingActiveTicket->status() === 'matched' ? 200 : 202,
                    'data' => $this->ticketPayload($existingActiveTicket),
                ];
            }

            $profile = $this->profileRepository->findByPlayerId($playerId);
            $ticket = $this->ticketRepository->create(
                $this->uuidGenerator->v4(),
                'ranked',
                $mode,
                $playerId,
                $personajePrincipal,
                strtolower((string) ($body['region'] ?? 'eu-west')),
                $profile?->mmrGlobal() ?? 1000,
                'queued',
                null
            );

            $matchId = $this->tryMatchRankedTicket($ticket->id());
            if ($matchId !== null) {
                $ticket = $this->ticketRepository->findById($ticket->id()) ?? $ticket;
                return ['status' => 200, 'data' => $this->ticketPayload($ticket, $matchId, 'matched')];
            }

            return ['status' => 202, 'data' => $this->ticketPayload($ticket)];
        }

        $queueType = $mode === 'ranked_bot' ? 'ranked' : 'bot';
        return $this->startBotMatch($playerId, $personajePrincipal, $equipoActivo['equipo'], $queueType, $mode);
    }

    public function activeTicket(string $playerId): array
    {
        $this->normalizarEstadoMatchmaking();

        $ticket = $this->ticketRepository->findActiveByPlayer($playerId);
        if ($ticket === null) {
            return ['status' => 200, 'data' => ['status' => 'none']];
        }

        return ['status' => 200, 'data' => $this->ticketPayload($ticket)];
    }

    public function ticketStatus(string $playerId, string $ticketId): array
    {
        $this->normalizarEstadoMatchmaking();

        $ticket = $this->ticketRepository->findById($ticketId);
        if ($ticket === null) {
            return ['status' => 404, 'data' => ['error' => ['code' => 'TICKET_NOT_FOUND', 'message' => 'Ticket not found.']]];
        }
        if ($ticket->playerId() !== $playerId) {
            return ['status' => 403, 'data' => ['error' => ['code' => 'FORBIDDEN', 'message' => 'Ticket does not belong to player.']]];
        }

        return ['status' => 200, 'data' => $this->ticketPayload($ticket)];
    }

    public function cancelTicket(string $playerId, string $ticketId): array
    {
        $this->normalizarEstadoMatchmaking();

        try {
            $this->connection->beginTransaction();
            $ticket = $this->ticketRepository->findByIdForUpdate($ticketId);
            if ($ticket === null) {
                $this->connection->commit();
                return ['status' => 404, 'data' => ['error' => ['code' => 'TICKET_NOT_FOUND', 'message' => 'Ticket not found.']]];
            }
            if ($ticket->playerId() !== $playerId) {
                $this->connection->commit();
                return ['status' => 403, 'data' => ['error' => ['code' => 'FORBIDDEN', 'message' => 'Ticket does not belong to player.']]];
            }
            if ($ticket->status() === 'cancelled' || $ticket->status() === 'expired') {
                $this->connection->commit();
                return ['status' => 200, 'data' => $this->ticketPayload($ticket)];
            }
            if ($ticket->status() !== 'queued') {
                $this->connection->commit();
                return [
                    'status' => 409,
                    'data' => [
                        'error' => [
                            'code' => 'TICKET_CANNOT_CANCEL',
                            'message' => 'Only queued tickets can be cancelled.',
                        ],
                        'ticket' => $this->ticketPayload($ticket),
                    ],
                ];
            }

            $this->ticketRepository->cancelIfActive($ticketId);
            $ticket = $this->ticketRepository->findById($ticketId) ?? $ticket;
            $this->connection->commit();

            return ['status' => 200, 'data' => $this->ticketPayload($ticket)];
        } catch (Throwable) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            return ['status' => 500, 'data' => ['error' => ['code' => 'TICKET_CANCEL_FAILED', 'message' => 'Failed to cancel ticket.']]];
        }
    }

    /** @param list<string> $equipoJugador */
    private function startBotMatch(string $playerId, string $personajePrincipal, array $equipoJugador, string $queueType, string $mode): array
    {
        $matchId = $this->uuidGenerator->v4();
        $ticketId = $this->uuidGenerator->v4();
        $equipoRival = $this->equipoBotBase();
        $state = [
            'serverStateVersion' => 1,
            'turnNo' => 0,
            'winner' => null,
            'recentEvents' => [],
            'attackCount' => 0,
            'defendCount' => 0,
            'specialCount' => 0,
            'damageDealt' => 0,
            'damageTaken' => 0,
            ...$this->combatStateFactory->estadoInicialBot($equipoJugador, $equipoRival, $queueType),
        ];
        $this->matchRepository->createBotMatch($matchId, $queueType, $playerId, self::BOT_NAME, $state);
        $profile = $this->profileRepository->findByPlayerId($playerId);
        $this->ticketRepository->create(
            $ticketId,
            $queueType,
            $mode,
            $playerId,
            $personajePrincipal,
            'eu-west',
            $profile?->mmrGlobal() ?? 1000,
            'matched',
            $matchId
        );

        $ticket = $this->ticketRepository->findById($ticketId);
        $fallback = new MatchmakingTicket($ticketId, $queueType, $mode, $playerId, $personajePrincipal, 'eu-west', 1000, 'matched', $matchId);

        return ['status' => 200, 'data' => $this->ticketPayload($ticket ?? $fallback, $matchId, 'matched')];
    }

    private function tryMatchRankedTicket(string $ticketId): ?string
    {
        $ticket = $this->ticketRepository->findById($ticketId);
        if ($ticket === null || $ticket->status() !== 'queued') {
            return $ticket?->matchedMatchId();
        }

        try {
            $this->connection->beginTransaction();

            $opponent = $this->ticketRepository->findQueuedOpponent(
                $ticket->id(),
                $ticket->playerId(),
                'ranked',
                $ticket->region(),
                $ticket->mmr(),
                self::RANKED_MMR_WINDOW
            );

            if ($opponent === null) {
                $this->connection->commit();
                return null;
            }

            $matchId = $this->uuidGenerator->v4();
            $firstMarked = $this->ticketRepository->markMatchedIfQueued($ticket->id(), $matchId);
            $secondMarked = $this->ticketRepository->markMatchedIfQueued($opponent->id(), $matchId);
            if (!$firstMarked || !$secondMarked) {
                throw new RuntimeException('Concurrent matchmaking conflict.');
            }

            $equipoP1 = $this->resolverEquipoActivo($ticket->playerId())['equipo'] ?? [$ticket->championId()];
            $equipoP2 = $this->resolverEquipoActivo($opponent->playerId())['equipo'] ?? [$opponent->championId()];
            $personajePrincipalP1 = $equipoP1[0] ?? $ticket->championId();
            $personajePrincipalP2 = $equipoP2[0] ?? $opponent->championId();

            $this->matchRepository->createPlayerMatch(
                $matchId,
                'ranked',
                $ticket->playerId(),
                $opponent->playerId(),
                [
                    'serverStateVersion' => 1,
                    'turnNo' => 0,
                    'winner' => null,
                    'recentEvents' => [],
                    'attackCount' => 0,
                    'defendCount' => 0,
                    'specialCount' => 0,
                    'damageDealt' => 0,
                    'damageTaken' => 0,
                    'currentPlayerId' => $ticket->playerId(),
                    'pvpTurnDeadlineAt' => time() + 90,
                    ...$this->combatStateFactory->estadoInicialPvp($equipoP1, $equipoP2, 'ranked'),
                ]
            );

            $this->connection->commit();
            return $matchId;
        } catch (Throwable) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            $latest = $this->ticketRepository->findById($ticketId);
            return $latest?->matchedMatchId();
        }
    }

    /** @param array<string,mixed> $body @return array{queue:string,mode:string,vsBot:bool} */
    private function resolveQueueSelection(array $body): array
    {
        $mode = strtolower((string) ($body['mode'] ?? ''));
        if (in_array($mode, ['normal_bot', 'ranked_pvp', 'ranked_bot'], true)) {
            return [
                'queue' => $mode === 'normal_bot' ? 'normal' : 'ranked',
                'mode' => $mode,
                'vsBot' => $mode !== 'ranked_pvp',
            ];
        }

        $queue = strtolower((string) ($body['queue'] ?? 'normal'));
        $vsBot = (bool) ($body['vsBot'] ?? false);
        if ($queue === 'ranked' && !$vsBot) {
            return ['queue' => 'ranked', 'mode' => 'ranked_pvp', 'vsBot' => false];
        }
        if ($queue === 'ranked' && $vsBot) {
            return ['queue' => 'ranked', 'mode' => 'ranked_bot', 'vsBot' => true];
        }

        return ['queue' => 'normal', 'mode' => 'normal_bot', 'vsBot' => true];
    }

    /** @return array{principal:string,equipo:list<string>}|null */
    private function resolverEquipoActivo(string $jugadorId): ?array
    {
        $this->jugadorPersonajeRepositorio->inicializarParaJugador($jugadorId);
        $equipo = $this->equipoJugadorRepositorio->obtener($jugadorId);
        if (count($equipo) !== 3) {
            return null;
        }

        usort($equipo, static fn(array $a, array $b): int => (int) ($a['slot'] ?? 0) <=> (int) ($b['slot'] ?? 0));
        $personajes = array_values(array_map(static fn(array $slot): string => (string) ($slot['personajeId'] ?? ''), $equipo));
        if (count($personajes) !== 3 || count(array_unique($personajes)) !== 3) {
            return null;
        }

        foreach ($personajes as $personajeId) {
            if ($personajeId === '' || !$this->jugadorPersonajeRepositorio->estaDesbloqueado($jugadorId, $personajeId)) {
                return null;
            }
        }

        return ['principal' => $personajes[0], 'equipo' => $personajes];
    }

    /** @return list<string> */
    private function equipoBotBase(): array
    {
        return ['vanguard', 'bulwark', 'riftblade'];
    }

    private function ticketPayload(MatchmakingTicket $ticket, ?string $matchId = null, ?string $status = null): array
    {
        $currentStatus = $status ?? $ticket->status();
        $resolvedMatchId = $matchId ?? $ticket->matchedMatchId();
        $matchStatus = null;
        if (is_string($resolvedMatchId) && $resolvedMatchId !== '') {
            $match = $this->matchRepository->findById($resolvedMatchId);
            $matchStatus = $match?->status();
        }

        return [
            'ticketId' => $ticket->id(),
            'status' => $currentStatus,
            'queue' => $ticket->mode(),
            'matchId' => $resolvedMatchId,
            'matchStatus' => $matchStatus,
            'etaSec' => $currentStatus === 'queued' ? $this->ticketEtaSeconds($ticket) : 0,
            'expiresInSec' => $this->ticketExpiresInSeconds($ticket),
            'canCancel' => $currentStatus === 'queued',
            'region' => $ticket->region(),
        ];
    }

    private function ticketExpiresInSeconds(MatchmakingTicket $ticket): int
    {
        $createdAt = $ticket->createdAt();
        if ($createdAt === null || $ticket->status() !== 'queued') {
            return 0;
        }

        $elapsed = time() - $createdAt->getTimestamp();
        return max(0, self::TICKET_TTL_SECONDS - $elapsed);
    }

    private function ticketEtaSeconds(MatchmakingTicket $ticket): int
    {
        return min(20, $this->ticketExpiresInSeconds($ticket));
    }

    private function normalizarEstadoMatchmaking(): void
    {
        $this->ticketRepository->expireQueuedOlderThan(self::TICKET_TTL_SECONDS);
        $this->ticketRepository->expireMatchedWithoutLiveMatch();
    }

}
