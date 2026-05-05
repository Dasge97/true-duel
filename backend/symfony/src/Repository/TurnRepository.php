<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class TurnRepository
{
    public function __construct(private PDO $pdo)
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
        $statement = $this->pdo->prepare(
            'INSERT INTO turns (id, match_id, turn_no, actor_id, action, payload_json, result_json, server_state_version, created_at)
             VALUES (:id, :match_id, :turn_no, :actor_id, :action, :payload_json, :result_json, :server_state_version, NOW())'
        );
        $statement->execute([
            ':id' => $id,
            ':match_id' => $matchId,
            ':turn_no' => $turnNo,
            ':actor_id' => $actorId,
            ':action' => $action,
            ':payload_json' => json_encode($payload, JSON_THROW_ON_ERROR),
            ':result_json' => json_encode($result, JSON_THROW_ON_ERROR),
            ':server_state_version' => $serverStateVersion,
        ]);
    }
}
