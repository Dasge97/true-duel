<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\DBAL\Connection;

final class TurnRepository
{
    public function __construct(private Connection $connection)
    {
    }

    /** @param array<string, mixed> $payload */
    public function add(
        string $id,
        string $matchId,
        int $turnNo,
        string $actorId,
        string $action,
        array $payload,
        array $result,
        int $serverStateVersion,
    ): void {
        $this->connection->executeStatement(
            'INSERT INTO turns (id, match_id, turn_no, actor_id, action, payload_json, result_json, server_state_version, created_at)
             VALUES (:id, :match_id, :turn_no, :actor_id, :action, CAST(:payload_json AS jsonb), CAST(:result_json AS jsonb), :server_state_version, NOW())',
            [
                'id' => $id,
                'match_id' => $matchId,
                'turn_no' => $turnNo,
                'actor_id' => $actorId,
                'action' => $action,
                'payload_json' => json_encode($payload, JSON_THROW_ON_ERROR),
                'result_json' => json_encode($result, JSON_THROW_ON_ERROR),
                'server_state_version' => $serverStateVersion,
            ]
        );
    }

    /** @return array<string,mixed>|null */
    public function findReplayableResult(string $matchId, string $actorId, int $clientStateVersion, string $action): ?array
    {
        $row = $this->connection->fetchAssociative(
            "SELECT result_json
             FROM turns
             WHERE match_id = :match_id
               AND actor_id = :actor_id
               AND action = :action
               AND CAST(payload_json->>'clientStateVersion' AS INT) = :client_state_version
             ORDER BY created_at DESC
             LIMIT 1",
            [
                'match_id' => $matchId,
                'actor_id' => $actorId,
                'action' => $action,
                'client_state_version' => $clientStateVersion,
            ]
        );
        if ($row === false) {
            return null;
        }

        $decoded = json_decode((string) ($row['result_json'] ?? '{}'), true);
        return is_array($decoded) ? $decoded : null;
    }
}
