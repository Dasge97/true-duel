<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\GameMatchRepository;
use App\Repository\MatchHistoryRepository;
use App\Repository\MatchmakingTicketRepository;
use App\Repository\PlayerMissionRepository;
use App\Repository\PlayerProfileRepository;

final class HomeSummaryService
{
    public function __construct(
        private readonly PlayerProfileRepository $profileRepository,
        private readonly PlayerMissionRepository $missionRepository,
        private readonly MatchHistoryRepository $historyRepository,
        private readonly MatchmakingTicketRepository $ticketRepository,
        private readonly GameMatchRepository $matchRepository,
        private readonly ProfileViewFactory $profileViewFactory,
    ) {
    }

    public function summary(string $playerId): array
    {
        $profile = $this->profileRepository->findByPlayerId($playerId);
        if ($profile === null) {
            return ['status' => 404, 'data' => ['error' => ['code' => 'PROFILE_NOT_FOUND', 'message' => 'Profile not found.']]];
        }

        $missions = $this->missionRepository->findToday($playerId);
        $completed = 0;
        foreach ($missions as $mission) {
            if (($mission['completed'] ?? false) === true) {
                $completed++;
            }
        }

        $history = $this->historyRepository->findLatestByPlayerId($playerId, 5);
        $historyItems = array_map(
            fn($entry): array => $this->profileViewFactory->historyEntry($entry),
            $history
        );

        $activeTicket = $this->ticketRepository->findActiveByPlayer($playerId);
        $activeMatch = $this->matchRepository->findActiveByPlayer($playerId);

        return [
            'status' => 200,
            'data' => [
                'profile' => $this->profileViewFactory->summaryProfile($profile),
                'misionesDiarias' => [
                    'date' => $this->missionRepository->todayDate(),
                    'summary' => [
                        'completed' => $completed,
                        'total' => count($missions),
                    ],
                    'missions' => $missions,
                ],
                'ultimasPartidas' => $historyItems,
                'competitivo' => [
                    'sp' => $profile->puntosHabilidad(),
                    'titulo' => $profile->tituloCompetitivo(),
                    'posicion' => $profile->posicionCompetitiva(),
                ],
                'actividad' => [
                    'ticket' => $this->profileViewFactory->ticketSummary($activeTicket),
                    'match' => $this->profileViewFactory->matchSummary($activeMatch, $playerId),
                ],
            ],
        ];
    }
}
