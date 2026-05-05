<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ChampionRatingRepository;
use App\Repository\MatchHistoryRepository;
use App\Repository\PlayerProfileRepository;

final class ProfileService
{
    public function __construct(
        private PlayerProfileRepository $profileRepository,
        private MatchHistoryRepository $historyRepository,
        private ChampionRatingRepository $championRatingRepository,
    ) {
    }

    public function profile(string $playerId): array
    {
        $profile = $this->profileRepository->findByPlayerId($playerId);
        if ($profile === null) {
            return ['status' => 404, 'data' => ['error' => ['code' => 'PROFILE_NOT_FOUND', 'message' => 'Profile not found.']]];
        }

        $championRatings = $this->championRatingRepository->findByPlayerAsMap($playerId);

        return [
            'status' => 200,
            'data' => [
                'playerId' => $profile->playerId(),
                'name' => $profile->displayName(),
                'rank' => $profile->rankLabel(),
                'mmrGlobal' => $profile->mmrGlobal(),
                'level' => $profile->level(),
                'experienceTotal' => $profile->experienceTotal(),
                'experienceToNextLevel' => RankProgression::experienceToNextLevel($profile->experienceTotal()),
                'coins' => $profile->coins(),
                'gems' => $profile->gems(),
                'stats' => [
                    'matches' => $profile->totalMatches(),
                    'wins' => $profile->wins(),
                    'losses' => $profile->losses(),
                ],
                'mmrByChampion' => $championRatings,
                'freshnessSeconds' => 30,
                'isFresh' => true,
            ],
        ];
    }

    public function ranking(): array
    {
        $profiles = $this->profileRepository->findRanking();
        $ranking = array_map(
            static fn($profile): array => [
                'playerId' => $profile->playerId(),
                'name' => $profile->displayName(),
                'mmr' => $profile->mmrGlobal(),
                'level' => $profile->level(),
            ],
            $profiles
        );

        return ['status' => 200, 'data' => ['ranking' => $ranking]];
    }

    public function users(): array
    {
        $profiles = $this->profileRepository->findLatest();
        $users = array_map(
            static fn($profile): array => [
                'playerId' => $profile->playerId(),
                'name' => $profile->displayName(),
                'rank' => $profile->rankLabel(),
                'mmr' => $profile->mmrGlobal(),
                'level' => $profile->level(),
                'region' => $profile->region(),
            ],
            $profiles
        );

        return ['status' => 200, 'data' => ['users' => $users]];
    }

    public function history(string $playerId): array
    {
        $history = $this->historyRepository->findLatestByPlayerId($playerId);
        $items = array_map(
            static fn($entry): array => [
                'matchId' => $entry->id(),
                'result' => $entry->result(),
                'enemy' => $entry->enemyName(),
                'turns' => $entry->turns(),
                'mmrDelta' => $entry->mmrDelta(),
            ],
            $history
        );

        return ['status' => 200, 'data' => ['matches' => $items]];
    }
}
