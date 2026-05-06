<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MatchmakingTicket;
use PDO;

final class MatchmakingTicketRepository
{
    public function __construct(private PDO $pdo)
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
        $statement = $this->pdo->prepare(
            'INSERT INTO matchmaking_tickets (id, queue_type, mode, player_id, champion_id, region, mmr, status, matched_match_id, created_at)
             VALUES (:id, :queue_type, :mode, :player_id, :champion_id, :region, :mmr, :status, :matched_match_id, NOW())'
        );
        $statement->execute([
            ':id' => $id,
            ':queue_type' => $queueType,
            ':mode' => $mode,
            ':player_id' => $playerId,
            ':champion_id' => $championId,
            ':region' => $region,
            ':mmr' => $mmr,
            ':status' => $status,
            ':matched_match_id' => $matchedMatchId,
        ]);

        return new MatchmakingTicket($id, $queueType, $mode, $playerId, $championId, $region, $mmr, $status, $matchedMatchId);
    }

    public function findById(string $id): ?MatchmakingTicket
    {
        $statement = $this->pdo->prepare(
            'SELECT id, queue_type, mode, player_id, champion_id, region, mmr, status, matched_match_id
             FROM matchmaking_tickets
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute([':id' => $id]);
        $row = $statement->fetch();
        if (!is_array($row)) {
            return null;
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
        );
    }

    public function findQueuedByPlayerAndQueue(string $playerId, string $queueType): ?MatchmakingTicket
    {
        $statement = $this->pdo->prepare(
            'SELECT id, queue_type, mode, player_id, champion_id, region, mmr, status, matched_match_id
             FROM matchmaking_tickets
             WHERE player_id = :player_id
               AND queue_type = :queue_type
               AND status = :status
             ORDER BY created_at DESC
             LIMIT 1'
        );
        $statement->execute([
            ':player_id' => $playerId,
            ':queue_type' => $queueType,
            ':status' => 'queued',
        ]);
        $row = $statement->fetch();
        if (!is_array($row)) {
            return null;
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
        );
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
        $statement = $this->pdo->prepare(
            'SELECT id, queue_type, mode, player_id, champion_id, region, mmr, status, matched_match_id
             FROM matchmaking_tickets
             WHERE id <> :exclude_id
               AND player_id <> :exclude_player_id
               AND queue_type = :queue_type
               AND region = :region
               AND status = :status
               AND ABS(mmr - :target_mmr) <= :window
             ORDER BY created_at ASC
             LIMIT 1'
        );
        $statement->bindValue(':exclude_id', $excludeTicketId);
        $statement->bindValue(':exclude_player_id', $excludePlayerId);
        $statement->bindValue(':queue_type', $queueType);
        $statement->bindValue(':region', $region);
        $statement->bindValue(':status', 'queued');
        $statement->bindValue(':target_mmr', $targetMmr, PDO::PARAM_INT);
        $statement->bindValue(':window', $window, PDO::PARAM_INT);
        $statement->execute();
        $row = $statement->fetch();
        if (!is_array($row)) {
            return null;
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
        );
    }

    public function markMatched(string $ticketId, string $matchId): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE matchmaking_tickets
             SET status = :status,
                 matched_match_id = :match_id
             WHERE id = :id'
        );
        $statement->execute([
            ':status' => 'matched',
            ':match_id' => $matchId,
            ':id' => $ticketId,
        ]);
    }

    public function markMatchedIfQueued(string $ticketId, string $matchId): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE matchmaking_tickets
             SET status = :status,
                 matched_match_id = :match_id
             WHERE id = :id AND status = :expected_status'
        );
        $statement->execute([
            ':status' => 'matched',
            ':match_id' => $matchId,
            ':id' => $ticketId,
            ':expected_status' => 'queued',
        ]);

        return $statement->rowCount() === 1;
    }

    public function cancelIfActive(string $ticketId): void
    {
        $statement = $this->pdo->prepare(
            "UPDATE matchmaking_tickets
             SET status = 'cancelled'
             WHERE id = :id AND status IN ('creating', 'queued')"
        );
        $statement->execute([':id' => $ticketId]);
    }

    private function defaultMode(string $queueType): string
    {
        return $queueType === 'ranked' ? 'ranked_pvp' : 'normal_bot';
    }
}
