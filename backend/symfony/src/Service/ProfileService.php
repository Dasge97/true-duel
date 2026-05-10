<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ChampionRatingRepository;
use App\Repository\MatchHistoryRepository;
use App\Repository\PlayerProfileRepository;

final class ProfileService
{
    public function __construct(
        private readonly PlayerProfileRepository $profileRepository,
        private readonly MatchHistoryRepository $historyRepository,
        private readonly ChampionRatingRepository $championRatingRepository,
        private readonly ProfileViewFactory $profileViewFactory,
        private readonly HomeSummaryService $homeSummaryService,
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
            'data' => $this->profileViewFactory->profile($profile, $championRatings),
        ];
    }

    public function ranking(): array
    {
        $profiles = $this->profileRepository->findRanking();
        $ranking = array_map(
            fn($profile): array => $this->profileViewFactory->rankingRow($profile),
            $profiles
        );

        return ['status' => 200, 'data' => ['ranking' => $ranking]];
    }

    public function rankingSp(): array
    {
        $profiles = $this->profileRepository->findRankingBySp();
        $ranking = array_map(
            fn($profile): array => $this->profileViewFactory->rankingRow($profile),
            $profiles
        );

        return ['status' => 200, 'data' => ['ranking' => $ranking]];
    }

    public function users(): array
    {
        $profiles = $this->profileRepository->findLatest();
        $users = array_map(
            fn($profile): array => $this->profileViewFactory->userRow($profile),
            $profiles
        );

        return ['status' => 200, 'data' => ['users' => $users]];
    }

    public function history(string $playerId): array
    {
        $history = $this->historyRepository->findLatestByPlayerId($playerId);
        $items = array_map(
            fn($entry): array => $this->profileViewFactory->historyEntry($entry),
            $history
        );

        return ['status' => 200, 'data' => ['matches' => $items]];
    }

    public function homeSummary(string $playerId): array
    {
        return $this->homeSummaryService->summary($playerId);
    }
}
