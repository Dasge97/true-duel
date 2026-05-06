<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\DBAL\Connection;

final class ApiIdempotencyRepository
{
    public function __construct(private Connection $connection)
    {
    }

    /** @return array{requestHash:string,response:array<string,mixed>}|null */
    public function find(string $scope, string $playerId, string $idempotencyKey): ?array
    {
        $row = $this->connection->fetchAssociative(
            'SELECT request_hash, response_json
             FROM api_idempotency
             WHERE scope = :scope
               AND player_id = :player_id
               AND idempotency_key = :idempotency_key
             LIMIT 1',
            [
                'scope' => $scope,
                'player_id' => $playerId,
                'idempotency_key' => $idempotencyKey,
            ]
        );
        if ($row === false) {
            return null;
        }

        $decoded = json_decode((string) ($row['response_json'] ?? '{}'), true);

        return [
            'requestHash' => (string) ($row['request_hash'] ?? ''),
            'response' => is_array($decoded) ? $decoded : [],
        ];
    }

    /** @param array<string,mixed> $response */
    public function save(string $scope, string $playerId, string $idempotencyKey, string $requestHash, array $response): void
    {
        $this->connection->executeStatement(
            'INSERT INTO api_idempotency (scope, player_id, idempotency_key, request_hash, response_json, created_at)
             VALUES (:scope, :player_id, :idempotency_key, :request_hash, CAST(:response_json AS jsonb), NOW())
             ON CONFLICT (scope, player_id, idempotency_key) DO NOTHING',
            [
                'scope' => $scope,
                'player_id' => $playerId,
                'idempotency_key' => $idempotencyKey,
                'request_hash' => $requestHash,
                'response_json' => json_encode($response, JSON_THROW_ON_ERROR),
            ]
        );
    }
}
