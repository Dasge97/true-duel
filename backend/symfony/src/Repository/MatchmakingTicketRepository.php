<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MatchmakingTicket;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;

final class MatchmakingTicketRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function create(
        string $id,
        string $queueType,
        string $mode,
        string $playerId,
        string $championId,
        string $region,
        int $mmr,
        string $status,
        ?string $matchedMatchId,
    ): MatchmakingTicket {
        $this->connection->executeStatement(
            'INSERT INTO matchmaking_tickets (id, queue_type, mode, player_id, champion_id, region, mmr, status, matched_match_id, created_at)
             VALUES (:id, :queue_type, :mode, :player_id, :champion_id, :region, :mmr, :status, :matched_match_id, NOW())',
            [
                'id' => $id,
                'queue_type' => $queueType,
                'mode' => $mode,
                'player_id' => $playerId,
                'champion_id' => $championId,
                'region' => $region,
                'mmr' => $mmr,
                'status' => $status,
                'matched_match_id' => $matchedMatchId,
            ]
        );

        return new MatchmakingTicket($id, $queueType, $mode, $playerId, $championId, $region, $mmr, $status, $matchedMatchId);
    }

    public function findById(string $id): ?MatchmakingTicket
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, queue_type, mode, player_id, champion_id, region, mmr, status, matched_match_id, created_at
             FROM matchmaking_tickets
             WHERE id = :id
             LIMIT 1',
            ['id' => $id]
        );
        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findQueuedByPlayerAndQueue(string $playerId, string $queueType): ?MatchmakingTicket
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, queue_type, mode, player_id, champion_id, region, mmr, status, matched_match_id, created_at
             FROM matchmaking_tickets
             WHERE player_id = :player_id
               AND queue_type = :queue_type
               AND status = :status
             ORDER BY created_at DESC
             LIMIT 1',
            [
                'player_id' => $playerId,
                'queue_type' => $queueType,
                'status' => 'queued',
            ]
        );
        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findActiveByPlayer(string $playerId): ?MatchmakingTicket
    {
        $row = $this->connection->fetchAssociative(
            "SELECT id, queue_type, mode, player_id, champion_id, region, mmr, status, matched_match_id, created_at
             FROM matchmaking_tickets
             WHERE player_id = :player_id
               AND (
                    status = 'queued'
                    OR (
                        status = 'matched'
                        AND matched_match_id IS NOT NULL
                        AND EXISTS (
                            SELECT 1
                            FROM matches m
                            WHERE m.id = matchmaking_tickets.matched_match_id
                              AND m.status = 'active'
                        )
                    )
               )
             ORDER BY created_at DESC
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

    public function findQueuedOpponent(
        string $excludeTicketId,
        string $excludePlayerId,
        string $queueType,
        string $region,
        int $targetMmr,
        int $window
    ): ?MatchmakingTicket
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, queue_type, mode, player_id, champion_id, region, mmr, status, matched_match_id, created_at
             FROM matchmaking_tickets
             WHERE id <> :exclude_id
               AND player_id <> :exclude_player_id
               AND queue_type = :queue_type
               AND region = :region
               AND status = :status
               AND ABS(mmr - :target_mmr) <= :window
             ORDER BY created_at ASC
             LIMIT 1',
            [
                'exclude_id' => $excludeTicketId,
                'exclude_player_id' => $excludePlayerId,
                'queue_type' => $queueType,
                'region' => $region,
                'status' => 'queued',
                'target_mmr' => $targetMmr,
                'window' => $window,
            ]
        );
        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function markMatched(string $ticketId, string $matchId): void
    {
        $this->connection->executeStatement(
            'UPDATE matchmaking_tickets
             SET status = :status,
                 matched_match_id = :match_id
             WHERE id = :id',
            [
                'status' => 'matched',
                'match_id' => $matchId,
                'id' => $ticketId,
            ]
        );
    }

    public function markMatchedIfQueued(string $ticketId, string $matchId): bool
    {
        return $this->connection->executeStatement(
            'UPDATE matchmaking_tickets
             SET status = :status,
                 matched_match_id = :match_id
             WHERE id = :id AND status = :expected_status',
            [
                'status' => 'matched',
                'match_id' => $matchId,
                'id' => $ticketId,
                'expected_status' => 'queued',
            ]
        ) === 1;
    }

    public function cancelIfActive(string $ticketId): void
    {
        $this->connection->executeStatement(
            "UPDATE matchmaking_tickets
             SET status = 'cancelled'
             WHERE id = :id AND status = 'queued'",
            ['id' => $ticketId]
        );
    }

    public function expireQueuedOlderThan(int $ttlSeconds): int
    {
        return $this->connection->executeStatement(
            "UPDATE matchmaking_tickets
             SET status = 'expired'
             WHERE status = 'queued'
               AND created_at <= NOW() - (:ttl_seconds || ' seconds')::interval",
            [
                'ttl_seconds' => (string) $ttlSeconds,
            ]
        );
    }

    public function expireMatchedWithoutLiveMatch(): int
    {
        return $this->connection->executeStatement(
            "UPDATE matchmaking_tickets t
             SET status = 'expired'
             WHERE t.status = 'matched'
               AND (
                    t.matched_match_id IS NULL
                    OR NOT EXISTS (
                        SELECT 1
                        FROM matches m
                        WHERE m.id = t.matched_match_id
                          AND m.status IN ('active', 'completed')
                    )
               )"
        );
    }

    public function expireMatchedByMatchId(string $matchId): int
    {
        return $this->connection->executeStatement(
            "UPDATE matchmaking_tickets
             SET status = 'expired'
             WHERE matched_match_id = :match_id
               AND status = 'matched'",
            [
                'match_id' => $matchId,
            ]
        );
    }

    public function findByIdForUpdate(string $id): ?MatchmakingTicket
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, queue_type, mode, player_id, champion_id, region, mmr, status, matched_match_id, created_at
             FROM matchmaking_tickets
             WHERE id = :id
             LIMIT 1
             FOR UPDATE',
            ['id' => $id]
        );
        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    /** @param array<string,mixed> $row */
    private function hydrate(array $row): MatchmakingTicket
    {
        $createdAt = null;
        if (isset($row['created_at']) && is_string($row['created_at']) && $row['created_at'] !== '') {
            $createdAt = new DateTimeImmutable($row['created_at']);
        }

        return new MatchmakingTicket(
            (string) $row['id'],
            (string) $row['queue_type'],
            (string) ($row['mode'] ?? $this->defaultMode((string) $row['queue_type'])),
            (string) $row['player_id'],
            (string) $row['champion_id'],
            (string) ($row['region'] ?? 'eu-west'),
            (int) ($row['mmr'] ?? 1000),
            (string) $row['status'],
            isset($row['matched_match_id']) ? (string) $row['matched_match_id'] : null,
            $createdAt,
        );
    }

    private function defaultMode(string $queueType): string
    {
        return $queueType === 'ranked' ? 'ranked_pvp' : 'normal_bot';
    }
}
