<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\PlayerChampionRepository;
use App\Repository\PlayerProfileRepository;
use App\Repository\ChampionCatalogRepository;
use PDO;
use Throwable;

final class ChampionService
{
    public function __construct(
        private PDO $pdo,
        private PlayerChampionRepository $playerChampionRepository,
        private PlayerProfileRepository $playerProfileRepository,
        private ChampionCatalogRepository $championCatalogRepository,
    ) {
    }

    public function catalog(string $playerId): array
    {
        $this->playerChampionRepository->initializeForPlayer($playerId);
        $ownedById = [];
        foreach ($this->playerChampionRepository->findByPlayer($playerId) as $entry) {
            $ownedById[$entry->championId()] = $entry;
        }

        $items = [];
        foreach ($this->championCatalogRepository->all() as $champion) {
            $owned = $ownedById[$champion['id']] ?? null;
            $items[] = [
                'id' => $champion['id'],
                'name' => $champion['name'],
                'role' => $champion['role'],
                'priceCoins' => $champion['priceCoins'],
                'owned' => $owned?->owned() ?? false,
                'selected' => $owned?->selected() ?? false,
                'masteryLevel' => $owned?->masteryLevel() ?? 1,
                'masteryXp' => $owned?->masteryXp() ?? 0,
            ];
        }

        return ['status' => 200, 'data' => ['champions' => $items]];
    }

    public function mine(string $playerId): array
    {
        $this->playerChampionRepository->initializeForPlayer($playerId);
        $items = [];
        foreach ($this->playerChampionRepository->findByPlayer($playerId) as $entry) {
            $metadata = $this->championCatalogRepository->find($entry->championId());
            if ($metadata === null) {
                continue;
            }
            $items[] = [
                'id' => $metadata['id'],
                'name' => $metadata['name'],
                'role' => $metadata['role'],
                'owned' => $entry->owned(),
                'selected' => $entry->selected(),
                'masteryLevel' => $entry->masteryLevel(),
                'masteryXp' => $entry->masteryXp(),
            ];
        }

        return ['status' => 200, 'data' => ['champions' => $items]];
    }

    /** @param array<string,mixed> $body */
    public function unlock(string $playerId, array $body): array
    {
        $championId = strtolower((string) ($body['championId'] ?? ''));
        $champion = $this->championCatalogRepository->find($championId);
        if ($champion === null) {
            return ['status' => 422, 'data' => ['error' => ['code' => 'INVALID_CHAMPION', 'message' => 'Champion not found.']]];
        }

        $this->playerChampionRepository->initializeForPlayer($playerId);
        if ($this->playerChampionRepository->isOwned($playerId, $championId)) {
            return ['status' => 409, 'data' => ['error' => ['code' => 'ALREADY_OWNED', 'message' => 'Champion already unlocked.']]];
        }

        $cost = (int) $champion['priceCoins'];
        try {
            $this->pdo->beginTransaction();

            $paid = $this->playerProfileRepository->spendCoins($playerId, $cost);
            if (!$paid) {
                $this->pdo->rollBack();
                return ['status' => 409, 'data' => ['error' => ['code' => 'INSUFFICIENT_COINS', 'message' => 'Not enough coins to unlock champion.']]];
            }

            $unlocked = $this->playerChampionRepository->unlock($playerId, $championId);
            if (!$unlocked) {
                $this->pdo->rollBack();
                return ['status' => 409, 'data' => ['error' => ['code' => 'ALREADY_OWNED', 'message' => 'Champion already unlocked.']]];
            }

            $this->pdo->commit();
        } catch (Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['status' => 500, 'data' => ['error' => ['code' => 'UNLOCK_FAILED', 'message' => 'Could not unlock champion.']]];
        }

        $profile = $this->playerProfileRepository->findByPlayerId($playerId);
        return ['status' => 200, 'data' => ['championId' => $championId, 'coins' => $profile?->coins() ?? 0]];
    }

    /** @param array<string,mixed> $body */
    public function select(string $playerId, array $body): array
    {
        $championId = strtolower((string) ($body['championId'] ?? ''));
        if ($this->championCatalogRepository->find($championId) === null) {
            return ['status' => 422, 'data' => ['error' => ['code' => 'INVALID_CHAMPION', 'message' => 'Champion not found.']]];
        }

        $this->playerChampionRepository->initializeForPlayer($playerId);
        $selected = $this->playerChampionRepository->selectChampion($playerId, $championId);
        if (!$selected) {
            return ['status' => 409, 'data' => ['error' => ['code' => 'CHAMPION_LOCKED', 'message' => 'Unlock champion before selecting it.']]];
        }

        return ['status' => 200, 'data' => ['selectedChampionId' => $championId]];
    }
}
