<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\CompetitiveMatchLogRepository;
use App\Repository\PlayerProfileRepository;
use App\Repository\TituloCompetitivoRepositorio;
use Doctrine\DBAL\Connection;

final class CompetitiveTitleService
{
    private const TITLE_ACTIVITY_WINDOW_DAYS = 7;
    private const TITLE_ACTIVITY_MIN_MATCHES = 3;

    public function __construct(
        private readonly Connection $connection,
        private readonly PlayerProfileRepository $playerProfileRepository,
        private readonly TituloCompetitivoRepositorio $tituloCompetitivoRepositorio,
        private readonly CompetitiveMatchLogRepository $competitiveMatchLogRepository,
    ) {
    }

    public function recalculate(): void
    {
        $tramos = $this->tituloCompetitivoRepositorio->tramosActivos();
        if ($tramos === []) {
            return;
        }

        $ranking = $this->playerProfileRepository->findRanking(1000);
        $eligible = [];
        foreach ($ranking as $perfil) {
            $matches = $this->competitiveMatchLogRepository->countRecentCompetitiveMatches($perfil->playerId(), self::TITLE_ACTIVITY_WINDOW_DAYS);
            if ($matches >= self::TITLE_ACTIVITY_MIN_MATCHES) {
                $eligible[] = $perfil->playerId();
            }
        }

        $this->playerProfileRepository->resetTitulosPorInactividad($eligible);
        if ($eligible === []) {
            return;
        }

        $eligibleProfiles = array_values(array_filter(
            $ranking,
            static fn($perfil): bool => in_array($perfil->playerId(), $eligible, true)
        ));

        $posicion = 1;
        foreach ($eligibleProfiles as $perfil) {
            $this->connection->executeStatement(
                'UPDATE player_profiles
                 SET titulo_competitivo = :titulo,
                     posicion_competitiva = :posicion,
                     updated_at = NOW()
                 WHERE player_id = :player_id',
                [
                    'titulo' => $this->resolverTituloPorPosicion($tramos, $posicion),
                    'posicion' => $posicion,
                    'player_id' => $perfil->playerId(),
                ]
            );
            $posicion++;
        }
    }

    public function activityWindowDays(): int
    {
        return self::TITLE_ACTIVITY_WINDOW_DAYS;
    }

    public function activityMinMatches(): int
    {
        return self::TITLE_ACTIVITY_MIN_MATCHES;
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
