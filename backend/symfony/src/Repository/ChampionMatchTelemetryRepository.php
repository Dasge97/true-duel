<?php

declare(strict_types=1);

namespace App\Repository;

use App\Support\UuidGenerator;
use Doctrine\DBAL\Connection;

final class ChampionMatchTelemetryRepository
{
    public function __construct(
        private readonly Connection $connection,
        private readonly UuidGenerator $uuidGenerator,
    ) {
    }

    /** @param array<string,int> $deltas */
    public function upsertSide(string $matchId, string $sideRef, ?string $playerId, string $championId, string $queueType, bool $isBot, array $deltas = []): void
    {
        $payload = $this->normalizeDeltas($deltas);
        $this->connection->executeStatement(
            'INSERT INTO champion_match_telemetry (
                id, match_id, side_ref, player_id, champion_id, queue_type, is_bot,
                attack_actions, defend_actions, special_actions,
                exposed_applied, fortified_applied, bleed_applied, overload_applied, silence_applied, shield_applied,
                created_at, updated_at
            ) VALUES (
                :id, :match_id, :side_ref, :player_id, :champion_id, :queue_type, :is_bot,
                :attack_actions, :defend_actions, :special_actions,
                :exposed_applied, :fortified_applied, :bleed_applied, :overload_applied, :silence_applied, :shield_applied,
                NOW(), NOW()
            )
            ON CONFLICT (match_id, side_ref) DO UPDATE SET
                player_id = COALESCE(champion_match_telemetry.player_id, EXCLUDED.player_id),
                champion_id = EXCLUDED.champion_id,
                queue_type = EXCLUDED.queue_type,
                is_bot = EXCLUDED.is_bot,
                attack_actions = champion_match_telemetry.attack_actions + EXCLUDED.attack_actions,
                defend_actions = champion_match_telemetry.defend_actions + EXCLUDED.defend_actions,
                special_actions = champion_match_telemetry.special_actions + EXCLUDED.special_actions,
                exposed_applied = champion_match_telemetry.exposed_applied + EXCLUDED.exposed_applied,
                fortified_applied = champion_match_telemetry.fortified_applied + EXCLUDED.fortified_applied,
                bleed_applied = champion_match_telemetry.bleed_applied + EXCLUDED.bleed_applied,
                overload_applied = champion_match_telemetry.overload_applied + EXCLUDED.overload_applied,
                silence_applied = champion_match_telemetry.silence_applied + EXCLUDED.silence_applied,
                shield_applied = champion_match_telemetry.shield_applied + EXCLUDED.shield_applied,
                updated_at = NOW()',
            [
                'id' => $this->uuidGenerator->v4(),
                'match_id' => $matchId,
                'side_ref' => $sideRef,
                'player_id' => $playerId,
                'champion_id' => $championId,
                'queue_type' => $queueType,
                'is_bot' => $isBot,
                ...$payload,
            ]
        );
    }

    public function finalizeSide(string $matchId, string $sideRef, string $result): void
    {
        $this->connection->executeStatement(
            'UPDATE champion_match_telemetry
             SET result = :result,
                 updated_at = NOW()
             WHERE match_id = :match_id
               AND side_ref = :side_ref',
            [
                'match_id' => $matchId,
                'side_ref' => $sideRef,
                'result' => $result,
            ]
        );
    }

    /** @return list<array<string,mixed>> */
    public function aggregateByChampion(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT champion_id,
                    SUM(CASE WHEN result IS NOT NULL THEN 1 ELSE 0 END) AS matches_played,
                    SUM(CASE WHEN result = 'win' THEN 1 ELSE 0 END) AS wins,
                    SUM(CASE WHEN result = 'loss' THEN 1 ELSE 0 END) AS losses,
                    SUM(CASE WHEN result = 'draw' THEN 1 ELSE 0 END) AS draws,
                    SUM(attack_actions) AS attack_actions,
                    SUM(defend_actions) AS defend_actions,
                    SUM(special_actions) AS special_actions,
                    SUM(exposed_applied) AS exposed_applied,
                    SUM(fortified_applied) AS fortified_applied,
                    SUM(bleed_applied) AS bleed_applied,
                    SUM(overload_applied) AS overload_applied,
                    SUM(silence_applied) AS silence_applied,
                    SUM(shield_applied) AS shield_applied
             FROM champion_match_telemetry
             GROUP BY champion_id
             ORDER BY champion_id ASC"
        );

        return array_map(
            static function (array $row): array {
                $matches = max(0, (int) ($row['matches_played'] ?? 0));
                $wins = (int) ($row['wins'] ?? 0);

                return [
                    'championId' => (string) ($row['champion_id'] ?? ''),
                    'matchesPlayed' => $matches,
                    'wins' => $wins,
                    'losses' => (int) ($row['losses'] ?? 0),
                    'draws' => (int) ($row['draws'] ?? 0),
                    'winRate' => $matches > 0 ? round(($wins / $matches) * 100, 2) : 0.0,
                    'actions' => [
                        'attack' => (int) ($row['attack_actions'] ?? 0),
                        'defend' => (int) ($row['defend_actions'] ?? 0),
                        'special' => (int) ($row['special_actions'] ?? 0),
                    ],
                    'effects' => [
                        'expuesto' => (int) ($row['exposed_applied'] ?? 0),
                        'fortificado' => (int) ($row['fortified_applied'] ?? 0),
                        'hemorragia' => (int) ($row['bleed_applied'] ?? 0),
                        'sobrecarga' => (int) ($row['overload_applied'] ?? 0),
                        'silencioTactico' => (int) ($row['silence_applied'] ?? 0),
                        'escudo' => (int) ($row['shield_applied'] ?? 0),
                    ],
                ];
            },
            $rows
        );
    }

    /** @param array<string,int> $deltas @return array<string,int> */
    private function normalizeDeltas(array $deltas): array
    {
        return [
            'attack_actions' => max(0, (int) ($deltas['attack_actions'] ?? 0)),
            'defend_actions' => max(0, (int) ($deltas['defend_actions'] ?? 0)),
            'special_actions' => max(0, (int) ($deltas['special_actions'] ?? 0)),
            'exposed_applied' => max(0, (int) ($deltas['exposed_applied'] ?? 0)),
            'fortified_applied' => max(0, (int) ($deltas['fortified_applied'] ?? 0)),
            'bleed_applied' => max(0, (int) ($deltas['bleed_applied'] ?? 0)),
            'overload_applied' => max(0, (int) ($deltas['overload_applied'] ?? 0)),
            'silence_applied' => max(0, (int) ($deltas['silence_applied'] ?? 0)),
            'shield_applied' => max(0, (int) ($deltas['shield_applied'] ?? 0)),
        ];
    }
}
