<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\BonificadorPartidaRepositorio;
use App\Repository\CatalogoPersonajesRepositorio;
use App\Repository\ChampionMatchTelemetryRepository;
use App\Repository\MatchOutcomeRuleRepository;
use App\Repository\MissionCatalogRepository;
use App\Repository\StoreCatalogRepository;
use App\Repository\TituloCompetitivoRepositorio;
use Doctrine\DBAL\Connection;
use Throwable;

final class AdminContentService
{
    public function __construct(
        private Connection $connection,
        private CatalogoPersonajesRepositorio $catalogoPersonajesRepositorio,
        private BonificadorPartidaRepositorio $bonificadorPartidaRepositorio,
        private StoreCatalogRepository $storeCatalogRepository,
        private MissionCatalogRepository $missionCatalogRepository,
        private MatchOutcomeRuleRepository $matchOutcomeRuleRepository,
        private TituloCompetitivoRepositorio $tituloCompetitivoRepositorio,
        private ChampionMatchTelemetryRepository $championMatchTelemetryRepository,
    ) {
    }

    public function getCatalog(string $catalog): array
    {
        return match ($catalog) {
            'personajes' => ['status' => 200, 'data' => ['items' => $this->catalogoPersonajesRepositorio->todos()]],
            'bonificadores' => ['status' => 200, 'data' => ['items' => $this->bonificadorPartidaRepositorio->todos()]],
            'store' => ['status' => 200, 'data' => ['items' => $this->storeCatalogRepository->all()]],
            'misiones' => ['status' => 200, 'data' => ['items' => $this->missionCatalogRepository->all()]],
            'outcome-rules' => ['status' => 200, 'data' => ['items' => $this->matchOutcomeRuleRepository->all()]],
            'titulos' => ['status' => 200, 'data' => ['items' => $this->tituloCompetitivoRepositorio->all()]],
            'telemetria-personajes' => ['status' => 200, 'data' => ['items' => $this->championMatchTelemetryRepository->aggregateByChampion()]],
            default => ['status' => 404, 'data' => ['error' => ['code' => 'CATALOG_NOT_FOUND', 'message' => 'Catalog not found.']]],
        };
    }

    /** @param array<string,mixed> $body */
    public function replaceCatalog(string $catalog, array $body): array
    {
        $items = $body['items'] ?? null;
        if (!is_array($items)) {
            return ['status' => 422, 'data' => ['error' => ['code' => 'INVALID_CATALOG_PAYLOAD', 'message' => 'items array is required.']]];
        }

        try {
            $this->connection->beginTransaction();

            match ($catalog) {
                'personajes' => $this->replacePersonajes($items),
                'bonificadores' => $this->replaceBonificadores($items),
                'store' => $this->replaceStore($items),
                'misiones' => $this->replaceMisiones($items),
                'outcome-rules' => $this->replaceOutcomeRules($items),
                'titulos' => $this->replaceTitulos($items),
                default => throw new \RuntimeException('CATALOG_NOT_FOUND'),
            };

            $this->connection->commit();
        } catch (Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            if ($e->getMessage() === 'CATALOG_NOT_FOUND') {
                return ['status' => 404, 'data' => ['error' => ['code' => 'CATALOG_NOT_FOUND', 'message' => 'Catalog not found.']]];
            }

            return ['status' => 500, 'data' => ['error' => ['code' => 'CATALOG_UPDATE_FAILED', 'message' => 'Could not replace catalog.']]];
        }

        return $this->getCatalog($catalog);
    }

    /** @param list<mixed> $items */
    private function replacePersonajes(array $items): void
    {
        $this->catalogoPersonajesRepositorio->replaceAll($items);
    }

    /** @param list<mixed> $items */
    private function replaceBonificadores(array $items): void
    {
        $this->bonificadorPartidaRepositorio->replaceAll($items);
    }

    /** @param list<mixed> $items */
    private function replaceStore(array $items): void
    {
        $this->storeCatalogRepository->replaceAll($items);
    }

    /** @param list<mixed> $items */
    private function replaceMisiones(array $items): void
    {
        $this->missionCatalogRepository->replaceAll($items);
    }

    /** @param list<mixed> $items */
    private function replaceOutcomeRules(array $items): void
    {
        $this->matchOutcomeRuleRepository->replaceAll($items);
    }

    /** @param list<mixed> $items */
    private function replaceTitulos(array $items): void
    {
        $this->tituloCompetitivoRepositorio->replaceAll($items);
    }
}
