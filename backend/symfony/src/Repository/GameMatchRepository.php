<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\GameMatch;
use Doctrine\DBAL\Connection;

final class GameMatchRepository
{
    public function __construct(private Connection $connection)
    {
    }

    /** @param array<string, mixed> $state */
    public function createBotMatch(string $id, string $queueType, string $playerId, string $botName, array $state): GameMatch
    {
        $this->connection->executeStatement(
            'INSERT INTO matches (id, queue_type, p1_id, p2_id, bot_name, status, state_json, started_at)
             VALUES (:id, :queue_type, :p1_id, NULL, :bot_name, :status, CAST(:state_json AS jsonb), NOW())',
            [
                'id' => $id,
                'queue_type' => $queueType,
                'p1_id' => $playerId,
                'bot_name' => $botName,
                'status' => 'active',
                'state_json' => json_encode($state, JSON_THROW_ON_ERROR),
            ]
        );

        return new GameMatch($id, $queueType, $playerId, null, $botName, 'active', $state);
    }

    /** @param array<string, mixed> $state */
    public function createPlayerMatch(
        string $id,
        string $queueType,
        string $playerOneId,
        string $playerTwoId,
        array $state,
    ): GameMatch {
        $this->connection->executeStatement(
            'INSERT INTO matches (id, queue_type, p1_id, p2_id, bot_name, status, state_json, started_at)
             VALUES (:id, :queue_type, :p1_id, :p2_id, NULL, :status, CAST(:state_json AS jsonb), NOW())',
            [
                'id' => $id,
                'queue_type' => $queueType,
                'p1_id' => $playerOneId,
                'p2_id' => $playerTwoId,
                'status' => 'active',
                'state_json' => json_encode($state, JSON_THROW_ON_ERROR),
            ]
        );

        return new GameMatch($id, $queueType, $playerOneId, $playerTwoId, null, 'active', $state);
    }

    public function findById(string $id): ?GameMatch
    {
        return $this->findByIdInternal($id, false);
    }

    public function findByIdForUpdate(string $id): ?GameMatch
    {
        return $this->findByIdInternal($id, true);
    }

    public function findActiveByPlayer(string $playerId): ?GameMatch
    {
        $row = $this->connection->fetchAssociative(
            "SELECT id, queue_type, p1_id, p2_id, bot_name, status, state_json
             FROM matches
             WHERE status = 'active'
               AND (p1_id = :player_id OR p2_id = :player_id)
             ORDER BY started_at DESC
             LIMIT 1",
            [
                'player_id' => $playerId,
            ]
        );
        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByIdInternal(string $id, bool $forUpdate): ?GameMatch
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, queue_type, p1_id, p2_id, bot_name, status, state_json
             FROM matches
             WHERE id = :id
             LIMIT 1' . ($forUpdate ? ' FOR UPDATE' : ''),
            ['id' => $id]
        );
        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    /** @param array<string, mixed> $state */
    public function updateState(GameMatch $match, array $state, string $status): void
    {
        $winnerRef = isset($state['winner']) && is_string($state['winner']) ? $state['winner'] : null;
        $winnerId = match ($winnerRef) {
            'player', 'p1' => $match->playerId(),
            'opponent', 'p2' => $match->opponentPlayerId(),
            default => null,
        };

        if ($status === 'completed') {
            $this->connection->executeStatement(
                'UPDATE matches
                 SET state_json = :state_json,
                     status = :status,
                     ended_at = NOW(),
                     winner_id = :winner_id
                 WHERE id = :id',
                [
                    'state_json' => json_encode($state, JSON_THROW_ON_ERROR),
                    'status' => $status,
                    'winner_id' => $winnerId,
                    'id' => $match->id(),
                ]
            );
            return;
        }

        $this->connection->executeStatement(
            'UPDATE matches
             SET state_json = :state_json,
                 status = :status
             WHERE id = :id',
            [
                'state_json' => json_encode($state, JSON_THROW_ON_ERROR),
                'status' => $status,
                'id' => $match->id(),
            ]
        );
    }

    /** @param array<string,mixed> $row */
    private function hydrate(array $row): GameMatch
    {
        $decoded = json_decode((string) ($row['state_json'] ?? '{}'), true);
        $state = is_array($decoded) ? $decoded : [];

        return new GameMatch(
            (string) $row['id'],
            (string) $row['queue_type'],
            (string) $row['p1_id'],
            isset($row['p2_id']) ? (string) $row['p2_id'] : null,
            isset($row['bot_name']) ? (string) $row['bot_name'] : null,
            (string) $row['status'],
            $state,
        );
    }
}
