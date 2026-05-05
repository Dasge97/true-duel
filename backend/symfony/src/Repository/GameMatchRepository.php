<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\GameMatch;
use PDO;

final class GameMatchRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @param array<string, mixed> $state */
    public function createBotMatch(string $id, string $queueType, string $playerId, string $botName, array $state): GameMatch
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO matches (id, queue_type, p1_id, p2_id, bot_name, status, state_json, started_at)
             VALUES (:id, :queue_type, :p1_id, NULL, :bot_name, :status, :state_json, NOW())'
        );
        $statement->execute([
            ':id' => $id,
            ':queue_type' => $queueType,
            ':p1_id' => $playerId,
            ':bot_name' => $botName,
            ':status' => 'active',
            ':state_json' => json_encode($state, JSON_THROW_ON_ERROR),
        ]);

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
        $statement = $this->pdo->prepare(
            'INSERT INTO matches (id, queue_type, p1_id, p2_id, bot_name, status, state_json, started_at)
             VALUES (:id, :queue_type, :p1_id, :p2_id, NULL, :status, :state_json, NOW())'
        );
        $statement->execute([
            ':id' => $id,
            ':queue_type' => $queueType,
            ':p1_id' => $playerOneId,
            ':p2_id' => $playerTwoId,
            ':status' => 'active',
            ':state_json' => json_encode($state, JSON_THROW_ON_ERROR),
        ]);

        return new GameMatch($id, $queueType, $playerOneId, $playerTwoId, null, 'active', $state);
    }

    public function findById(string $id): ?GameMatch
    {
        $statement = $this->pdo->prepare(
            'SELECT id, queue_type, p1_id, p2_id, bot_name, status, state_json
             FROM matches
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute([':id' => $id]);
        $row = $statement->fetch();
        if (!is_array($row)) {
            return null;
        }

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
            $statement = $this->pdo->prepare(
                'UPDATE matches
                 SET state_json = :state_json,
                     status = :status,
                     ended_at = NOW(),
                     winner_id = :winner_id
                 WHERE id = :id'
            );
            $statement->execute([
                ':state_json' => json_encode($state, JSON_THROW_ON_ERROR),
                ':status' => $status,
                ':winner_id' => $winnerId,
                ':id' => $match->id(),
            ]);
            return;
        }

        $statement = $this->pdo->prepare(
            'UPDATE matches
             SET state_json = :state_json,
                 status = :status
             WHERE id = :id'
        );
        $statement->execute([
            ':state_json' => json_encode($state, JSON_THROW_ON_ERROR),
            ':status' => $status,
            ':id' => $match->id(),
        ]);
    }
}
