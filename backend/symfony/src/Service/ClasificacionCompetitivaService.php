<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\CompetitiveMatchLogRepository;
use App\Repository\PlayerProfileRepository;
use App\Support\UuidGenerator;
use Doctrine\DBAL\Connection;

final class ClasificacionCompetitivaService
{
    private const FACTOR_SP = 0.6;
    private const DECAY_DAYS = 14;
    private const DECAY_SP = 20;

    public function __construct(
        private readonly Connection $connection,
        private readonly PlayerProfileRepository $playerProfileRepository,
        private readonly CompetitiveMatchLogRepository $competitiveMatchLogRepository,
        private readonly CompetitiveBattleScoreService $competitiveBattleScoreService,
        private readonly CompetitivePenaltyService $competitivePenaltyService,
        private readonly CompetitiveTitleService $competitiveTitleService,
        private readonly CompetitiveOpponentResolver $competitiveOpponentResolver,
        private readonly UuidGenerator $uuidGenerator,
    ) {
    }

    /** @param array<string,mixed> $metricas */
    public function procesarResultadoPartida(string $playerId, string $matchId, string $resultado, array $metricas): array
    {
        $battleScore = $this->competitiveBattleScoreService->calculate($resultado, $metricas);
        $deltaSpBase = (int) round(($battleScore - 50) * self::FACTOR_SP);
        $opponentPlayerId = $this->competitiveOpponentResolver->resolve($matchId, $playerId);
        $antiFarmPenalty = $opponentPlayerId !== null
            ? $this->competitivePenaltyService->antiFarmPenalty($playerId, $opponentPlayerId)
            : 0;
        $deltaSp = $deltaSpBase - $antiFarmPenalty;
        $perfil = $this->playerProfileRepository->aplicarPuntosHabilidad($playerId, $deltaSp);
        $this->playerProfileRepository->marcarActividadCompetitiva($playerId);
        $this->competitiveMatchLogRepository->add(
            $this->uuidGenerator->v4(),
            $matchId,
            $playerId,
            $opponentPlayerId,
            $resultado,
            'ranked',
            false,
            $battleScore,
            $deltaSp,
        );
        $this->competitiveTitleService->recalculate();
        $perfil = $this->playerProfileRepository->findByPlayerId($playerId);

        return [
            'battleScore' => $battleScore,
            'deltaSp' => $deltaSp,
            'deltaSpBase' => $deltaSpBase,
            'antiFarmPenalty' => $antiFarmPenalty,
            'spActual' => $perfil?->puntosHabilidad() ?? 0,
            'tituloCompetitivo' => $perfil?->tituloCompetitivo() ?? 'Combatiente',
        ];
    }

    public function procesarAbandono(string $playerId, string $matchId): array
    {
        $opponentPlayerId = $this->competitiveOpponentResolver->resolve($matchId, $playerId);
        $abandonPenalty = $this->competitivePenaltyService->abandonPenalty();
        $this->playerProfileRepository->aplicarPuntosHabilidad($playerId, -$abandonPenalty);
        $this->playerProfileRepository->marcarActividadCompetitiva($playerId, false);
        $this->competitiveMatchLogRepository->add(
            $this->uuidGenerator->v4(),
            $matchId,
            $playerId,
            $opponentPlayerId,
            'loss',
            'ranked',
            true,
            0,
            -$abandonPenalty,
        );

        $this->competitiveTitleService->recalculate();
        $perfil = $this->playerProfileRepository->findByPlayerId($playerId);

        return [
            'deltaSp' => -$abandonPenalty,
            'spActual' => $perfil?->puntosHabilidad() ?? 0,
            'tituloCompetitivo' => $perfil?->tituloCompetitivo() ?? 'Combatiente',
            'abandoned' => true,
        ];
    }

    public function ejecutarJobCompetitivo(): array
    {
        $this->connection->beginTransaction();
        try {
            $decayed = $this->playerProfileRepository->aplicarDecayCompetitivo(self::DECAY_DAYS, self::DECAY_SP);
            $this->competitiveTitleService->recalculate();
            $this->connection->commit();
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            throw $e;
        }

        return [
            'decayedProfiles' => $decayed,
            'decayDays' => self::DECAY_DAYS,
            'decaySpPenalty' => self::DECAY_SP,
            'titleActivityWindowDays' => $this->competitiveTitleService->activityWindowDays(),
            'titleActivityMinMatches' => $this->competitiveTitleService->activityMinMatches(),
        ];
    }
}

