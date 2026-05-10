<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PlayerProfile;
use App\Service\RankLabelResolver;
use App\Service\RankProgression;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

final class PlayerProfileRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Connection $connection,
    )
    {
    }

    public function create(string $playerId, string $displayName, string $rankLabel, int $mmrGlobal, string $region): PlayerProfile
    {
        $profile = new PlayerProfile(
            $playerId,
            $displayName,
            $rankLabel,
            $mmrGlobal,
            $region,
            1000,
            'Combatiente',
            1000,
        );
        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        return $profile;
    }

    public function findByPlayerId(string $playerId): ?PlayerProfile
    {
        return $this->entityManager->find(PlayerProfile::class, $playerId);
    }

    /** @return list<PlayerProfile> */
    public function findRanking(int $limit = 100): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(PlayerProfile::class, 'p')
            ->orderBy('p.mmrGlobal', 'DESC')
            ->addOrderBy('p.puntosHabilidad', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return list<PlayerProfile> */
    public function findRankingBySp(int $limit = 100): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(PlayerProfile::class, 'p')
            ->orderBy('p.puntosHabilidad', 'DESC')
            ->addOrderBy('p.mmrGlobal', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return list<PlayerProfile> */
    public function findLatest(int $limit = 200): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(PlayerProfile::class, 'p')
            ->orderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
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

        $isWin = $result === 'win';
        $isLoss = $result === 'loss';
        $this->connection->executeStatement(
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
             WHERE player_id = :player_id',
            [
                'mmr_global' => $newMmr,
                'rank_label' => $newRank,
                'coins_delta' => $coinsDelta,
                'gems_delta' => $gemsDelta,
                'experience_total' => $newExperience,
                'level' => $newLevel,
                'wins_delta' => $isWin ? 1 : 0,
                'losses_delta' => $isLoss ? 1 : 0,
                'player_id' => $playerId,
            ]
        );

        return $this->findByPlayerId($playerId) ?? throw new RuntimeException('PROFILE_NOT_FOUND_AFTER_UPDATE');
    }

    public function spendCoins(string $playerId, int $coins): bool
    {
        if ($coins <= 0) {
            return true;
        }

        return $this->connection->executeStatement(
            'UPDATE player_profiles
             SET coins = coins - :coins, updated_at = NOW()
             WHERE player_id = :player_id
               AND coins >= :coins',
            [
                'coins' => $coins,
                'player_id' => $playerId,
            ]
        ) === 1;
    }

    public function grantEconomyAndExperience(string $playerId, int $coinsDelta, int $gemsDelta, int $experienceDelta): ?PlayerProfile
    {
        $profile = $this->findByPlayerId($playerId);
        if ($profile === null) {
            return null;
        }

        $newExperience = max(0, $profile->experienceTotal() + $experienceDelta);
        $newLevel = RankProgression::levelFromExperience($newExperience);

        $this->connection->executeStatement(
            'UPDATE player_profiles
             SET coins = coins + :coins_delta,
                 gems = gems + :gems_delta,
                 experience_total = :experience_total,
                 level = :level,
                 updated_at = NOW()
             WHERE player_id = :player_id',
            [
                'coins_delta' => $coinsDelta,
                'gems_delta' => $gemsDelta,
                'experience_total' => $newExperience,
                'level' => $newLevel,
                'player_id' => $playerId,
            ]
        );

        return $this->findByPlayerId($playerId);
    }

    public function marcarActividadCompetitiva(string $playerId, bool $incrementarPartidas = true): void
    {
        $this->connection->executeStatement(
            'UPDATE player_profiles
             SET last_competitive_activity_at = NOW(),
                 competitive_matches_played = competitive_matches_played + :incremento,
                 updated_at = NOW()
             WHERE player_id = :player_id',
            [
                'incremento' => $incrementarPartidas ? 1 : 0,
                'player_id' => $playerId,
            ]
        );
    }

    public function aplicarDecayCompetitivo(int $daysWithoutActivity, int $spPenalty): int
    {
        return $this->connection->executeStatement(
            "UPDATE player_profiles
             SET puntos_habilidad = GREATEST(0, puntos_habilidad - :sp_penalty),
                 updated_at = NOW()
             WHERE last_competitive_activity_at IS NOT NULL
               AND last_competitive_activity_at < NOW() - (:days || ' days')::interval",
            [
                'sp_penalty' => $spPenalty,
                'days' => (string) $daysWithoutActivity,
            ]
        );
    }

    public function resetTitulosPorInactividad(array $eligiblePlayerIds): int
    {
        if ($eligiblePlayerIds === []) {
            return $this->connection->executeStatement(
                "UPDATE player_profiles
                 SET titulo_competitivo = 'Combatiente',
                     posicion_competitiva = NULL,
                     updated_at = NOW()
                 WHERE titulo_competitivo <> 'Combatiente' OR posicion_competitiva IS NOT NULL"
            );
        }

        $placeholders = [];
        $params = [];
        foreach (array_values($eligiblePlayerIds) as $index => $playerId) {
            $key = ':player_' . $index;
            $placeholders[] = $key;
            $params[$key] = $playerId;
        }

        return $this->connection->executeStatement(
            "UPDATE player_profiles
             SET titulo_competitivo = 'Combatiente',
                 posicion_competitiva = NULL,
                 updated_at = NOW()
             WHERE player_id NOT IN (" . implode(', ', $placeholders) . ')',
            $params
        );
    }

    /** @return list<string> */
    public function findEligibleCompetitivePlayers(int $minMatches, int $windowDays): array
    {
        $rows = $this->connection->fetchFirstColumn(
            "SELECT player_id
             FROM player_profiles
             WHERE competitive_matches_played >= 0
               AND last_competitive_activity_at IS NOT NULL
               AND last_competitive_activity_at >= NOW() - (:days || ' days')::interval",
            [
                'days' => (string) $windowDays,
            ]
        );

        $eligible = [];
        foreach ($rows as $value) {
            if (is_string($value) && $value !== '') {
                $eligible[] = $value;
            }
        }

        return $eligible;
    }

    public function aplicarPuntosHabilidad(string $playerId, int $deltaPuntos): ?PlayerProfile
    {
        if ($this->connection->executeStatement(
            'UPDATE player_profiles
             SET puntos_habilidad = GREATEST(0, puntos_habilidad + :delta_puntos),
                 updated_at = NOW()
             WHERE player_id = :player_id',
            [
                'delta_puntos' => $deltaPuntos,
                'player_id' => $playerId,
            ]
        ) !== 1) {
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

        foreach ($actualizaciones as $row) {
            $this->connection->executeStatement(
                'UPDATE player_profiles
                 SET titulo_competitivo = :titulo,
                     posicion_competitiva = :posicion,
                     updated_at = NOW()
                 WHERE player_id = :player_id',
                [
                    'titulo' => $row['titulo'],
                    'posicion' => $row['posicion'],
                    'player_id' => $row['player_id'],
                ]
            );
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
