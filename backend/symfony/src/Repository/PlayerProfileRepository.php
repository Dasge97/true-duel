<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PlayerProfile;
use App\Service\RankLabelResolver;
use App\Service\RankProgression;
use PDO;
use RuntimeException;

final class PlayerProfileRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(string $playerId, string $displayName, string $rankLabel, int $mmrGlobal, string $region): PlayerProfile
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO player_profiles (player_id, display_name, rank_label, mmr_global, region, puntos_habilidad, titulo_competitivo, coins, gems, experience_total, level, total_matches, wins, losses, updated_at)
             VALUES (:player_id, :display_name, :rank_label, :mmr_global, :region, 1000, :titulo_competitivo, 1000, 0, 0, 1, 0, 0, 0, NOW())'
        );
        $statement->execute([
            ':player_id' => $playerId,
            ':display_name' => $displayName,
            ':rank_label' => $rankLabel,
            ':mmr_global' => $mmrGlobal,
            ':region' => $region,
            ':titulo_competitivo' => 'Combatiente',
        ]);

        return new PlayerProfile($playerId, $displayName, $rankLabel, $mmrGlobal, $region, 1000, 'Combatiente');
    }

    public function findByPlayerId(string $playerId): ?PlayerProfile
    {
        $statement = $this->pdo->prepare(
            'SELECT player_id, display_name, rank_label, mmr_global, region, puntos_habilidad, titulo_competitivo, posicion_competitiva, coins, gems, experience_total, level, total_matches, wins, losses
             FROM player_profiles
             WHERE player_id = :player_id
             LIMIT 1'
        );
        $statement->execute([':player_id' => $playerId]);
        $row = $statement->fetch();
        if (!is_array($row)) {
            return null;
        }

        return new PlayerProfile(
            (string) $row['player_id'],
            (string) $row['display_name'],
            (string) $row['rank_label'],
            (int) $row['mmr_global'],
            (string) $row['region'],
            (int) ($row['puntos_habilidad'] ?? 1000),
            (string) ($row['titulo_competitivo'] ?? 'Combatiente'),
            (int) ($row['coins'] ?? 0),
            (int) ($row['gems'] ?? 0),
            (int) ($row['experience_total'] ?? 0),
            (int) ($row['level'] ?? 1),
            (int) ($row['total_matches'] ?? 0),
            (int) ($row['wins'] ?? 0),
            (int) ($row['losses'] ?? 0),
            isset($row['posicion_competitiva']) ? (int) $row['posicion_competitiva'] : null,
        );
    }

    /** @return list<PlayerProfile> */
    public function findRanking(int $limit = 100): array
    {
        $statement = $this->pdo->prepare(
            'SELECT player_id, display_name, rank_label, mmr_global, region, puntos_habilidad, titulo_competitivo, posicion_competitiva, coins, gems, experience_total, level, total_matches, wins, losses
             FROM player_profiles
             ORDER BY puntos_habilidad DESC, mmr_global DESC, updated_at ASC
             LIMIT :limit'
        );
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        $rows = $statement->fetchAll();
        if (!is_array($rows)) {
            return [];
        }

        $profiles = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $profiles[] = new PlayerProfile(
                (string) $row['player_id'],
                (string) $row['display_name'],
                (string) $row['rank_label'],
                (int) $row['mmr_global'],
                (string) $row['region'],
                (int) ($row['puntos_habilidad'] ?? 1000),
                (string) ($row['titulo_competitivo'] ?? 'Combatiente'),
                (int) ($row['coins'] ?? 0),
                (int) ($row['gems'] ?? 0),
                (int) ($row['experience_total'] ?? 0),
                (int) ($row['level'] ?? 1),
                (int) ($row['total_matches'] ?? 0),
                (int) ($row['wins'] ?? 0),
                (int) ($row['losses'] ?? 0),
                isset($row['posicion_competitiva']) ? (int) $row['posicion_competitiva'] : null,
            );
        }

        return $profiles;
    }

    /** @return list<PlayerProfile> */
    public function findLatest(int $limit = 200): array
    {
        $statement = $this->pdo->prepare(
            'SELECT player_id, display_name, rank_label, mmr_global, region, puntos_habilidad, titulo_competitivo, posicion_competitiva, coins, gems, experience_total, level, total_matches, wins, losses
             FROM player_profiles
             ORDER BY updated_at DESC
             LIMIT :limit'
        );
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        $rows = $statement->fetchAll();
        if (!is_array($rows)) {
            return [];
        }

        $profiles = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $profiles[] = new PlayerProfile(
                (string) $row['player_id'],
                (string) $row['display_name'],
                (string) $row['rank_label'],
                (int) $row['mmr_global'],
                (string) $row['region'],
                (int) ($row['puntos_habilidad'] ?? 1000),
                (string) ($row['titulo_competitivo'] ?? 'Combatiente'),
                (int) ($row['coins'] ?? 0),
                (int) ($row['gems'] ?? 0),
                (int) ($row['experience_total'] ?? 0),
                (int) ($row['level'] ?? 1),
                (int) ($row['total_matches'] ?? 0),
                (int) ($row['wins'] ?? 0),
                (int) ($row['losses'] ?? 0),
                isset($row['posicion_competitiva']) ? (int) $row['posicion_competitiva'] : null,
            );
        }

        return $profiles;
    }

    public function applyMatchOutcome(
        string $playerId,
        int $mmrDelta,
        int $coinsDelta,
        int $gemsDelta,
        int $experienceDelta,
        string $result,
    ): PlayerProfile {
        $profile = $this->findByPlayerId($playerId);
        if ($profile === null) {
            throw new RuntimeException('PROFILE_NOT_FOUND');
        }

        $newMmr = max(0, $profile->mmrGlobal() + $mmrDelta);
        $newRank = RankLabelResolver::fromMmr($newMmr);
        $newExperience = max(0, $profile->experienceTotal() + $experienceDelta);
        $newLevel = RankProgression::levelFromExperience($newExperience);

        $statement = $this->pdo->prepare(
            'UPDATE player_profiles
             SET mmr_global = :mmr_global,
                 rank_label = :rank_label,
                 coins = coins + :coins_delta,
                 gems = gems + :gems_delta,
                 experience_total = :experience_total,
                 level = :level,
                 total_matches = total_matches + 1,
                 wins = wins + :wins_delta,
                 losses = losses + :losses_delta,
                 updated_at = NOW()
             WHERE player_id = :player_id'
        );
        $isWin = $result === 'win';
        $isLoss = $result === 'loss';
        $statement->execute([
            ':mmr_global' => $newMmr,
            ':rank_label' => $newRank,
            ':coins_delta' => $coinsDelta,
            ':gems_delta' => $gemsDelta,
            ':experience_total' => $newExperience,
            ':level' => $newLevel,
            ':wins_delta' => $isWin ? 1 : 0,
            ':losses_delta' => $isLoss ? 1 : 0,
            ':player_id' => $playerId,
        ]);

        return $this->findByPlayerId($playerId) ?? throw new RuntimeException('PROFILE_NOT_FOUND_AFTER_UPDATE');
    }

    public function spendCoins(string $playerId, int $coins): bool
    {
        if ($coins <= 0) {
            return true;
        }

        $statement = $this->pdo->prepare(
            'UPDATE player_profiles
             SET coins = coins - :coins, updated_at = NOW()
             WHERE player_id = :player_id
               AND coins >= :coins'
        );
        $statement->execute([
            ':coins' => $coins,
            ':player_id' => $playerId,
        ]);

        return $statement->rowCount() === 1;
    }

    public function grantEconomyAndExperience(string $playerId, int $coinsDelta, int $gemsDelta, int $experienceDelta): ?PlayerProfile
    {
        $profile = $this->findByPlayerId($playerId);
        if ($profile === null) {
            return null;
        }

        $newExperience = max(0, $profile->experienceTotal() + $experienceDelta);
        $newLevel = RankProgression::levelFromExperience($newExperience);

        $statement = $this->pdo->prepare(
            'UPDATE player_profiles
             SET coins = coins + :coins_delta,
                 gems = gems + :gems_delta,
                 experience_total = :experience_total,
                 level = :level,
                 updated_at = NOW()
             WHERE player_id = :player_id'
        );
        $statement->execute([
            ':coins_delta' => $coinsDelta,
            ':gems_delta' => $gemsDelta,
            ':experience_total' => $newExperience,
            ':level' => $newLevel,
            ':player_id' => $playerId,
        ]);

        return $this->findByPlayerId($playerId);
    }

    public function aplicarPuntosHabilidad(string $playerId, int $deltaPuntos): ?PlayerProfile
    {
        $statement = $this->pdo->prepare(
            'UPDATE player_profiles
             SET puntos_habilidad = GREATEST(0, puntos_habilidad + :delta_puntos),
                 updated_at = NOW()
             WHERE player_id = :player_id'
        );
        $statement->execute([
            ':delta_puntos' => $deltaPuntos,
            ':player_id' => $playerId,
        ]);

        if ($statement->rowCount() !== 1) {
            return null;
        }

        return $this->findByPlayerId($playerId);
    }

    public function aplicarTitulosCompetitivos(array $tramos): void
    {
        if ($tramos === []) {
            return;
        }

        $ranking = $this->findRanking(1000);
        $actualizaciones = [];
        $posicion = 1;
        foreach ($ranking as $perfil) {
            $titulo = $this->resolverTituloPorPosicion($tramos, $posicion);
            $actualizaciones[] = [
                'player_id' => $perfil->playerId(),
                'titulo' => $titulo,
                'posicion' => $posicion,
            ];
            $posicion++;
        }

        $statement = $this->pdo->prepare(
            'UPDATE player_profiles
             SET titulo_competitivo = :titulo,
                 posicion_competitiva = :posicion,
                 updated_at = NOW()
             WHERE player_id = :player_id'
        );
        foreach ($actualizaciones as $row) {
            $statement->execute([
                ':titulo' => $row['titulo'],
                ':posicion' => $row['posicion'],
                ':player_id' => $row['player_id'],
            ]);
        }
    }

    /** @param list<array{nombre:string,cupo:int}> $tramos */
    private function resolverTituloPorPosicion(array $tramos, int $posicion): string
    {
        $acumulado = 0;
        foreach ($tramos as $tramo) {
            $acumulado += max(0, (int) ($tramo['cupo'] ?? 0));
            if ($acumulado > 0 && $posicion <= $acumulado) {
                return (string) ($tramo['nombre'] ?? 'Combatiente');
            }
        }

        return 'Combatiente';
    }
}
