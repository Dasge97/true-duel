<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\GameMatch;
use App\Entity\MatchHistoryEntry;
use App\Entity\MatchmakingTicket;
use App\Entity\PlayerProfile;

final class ProfileViewFactory
{
    /** @return array<string,mixed> */
    public function profile(PlayerProfile $profile, array $championRatings): array
    {
        return [
            'playerId' => $profile->playerId(),
            'name' => $profile->displayName(),
            'rank' => $profile->rankLabel(),
            'mmrGlobal' => $profile->mmrGlobal(),
            'puntosHabilidad' => $profile->puntosHabilidad(),
            'tituloCompetitivo' => $profile->tituloCompetitivo(),
            'posicionCompetitiva' => $profile->posicionCompetitiva(),
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
        ];
    }

    /** @return array<string,mixed> */
    public function summaryProfile(PlayerProfile $profile): array
    {
        return [
            'playerId' => $profile->playerId(),
            'name' => $profile->displayName(),
            'rank' => $profile->rankLabel(),
            'tituloCompetitivo' => $profile->tituloCompetitivo(),
            'coins' => $profile->coins(),
            'gems' => $profile->gems(),
            'level' => $profile->level(),
            'experienceTotal' => $profile->experienceTotal(),
            'experienceToNextLevel' => RankProgression::experienceToNextLevel($profile->experienceTotal()),
        ];
    }

    /** @return array<string,mixed> */
    public function rankingRow(PlayerProfile $profile): array
    {
        return [
            'playerId' => $profile->playerId(),
            'name' => $profile->displayName(),
            'mmr' => $profile->mmrGlobal(),
            'sp' => $profile->puntosHabilidad(),
            'titulo' => $profile->tituloCompetitivo(),
            'posicion' => $profile->posicionCompetitiva(),
            'level' => $profile->level(),
        ];
    }

    /** @return array<string,mixed> */
    public function userRow(PlayerProfile $profile): array
    {
        return [
            'playerId' => $profile->playerId(),
            'name' => $profile->displayName(),
            'rank' => $profile->rankLabel(),
            'mmr' => $profile->mmrGlobal(),
            'sp' => $profile->puntosHabilidad(),
            'titulo' => $profile->tituloCompetitivo(),
            'posicion' => $profile->posicionCompetitiva(),
            'level' => $profile->level(),
            'region' => $profile->region(),
        ];
    }

    /** @return array<string,mixed> */
    public function historyEntry(MatchHistoryEntry $entry): array
    {
        return [
            'matchId' => $entry->id(),
            'result' => $entry->result(),
            'enemy' => $entry->enemyName(),
            'turns' => $entry->turns(),
            'mmrDelta' => $entry->mmrDelta(),
        ];
    }

    /** @return array<string,mixed>|null */
    public function ticketSummary(?MatchmakingTicket $ticket): ?array
    {
        if ($ticket === null) {
            return null;
        }

        return [
            'ticketId' => $ticket->id(),
            'status' => $ticket->status(),
            'queue' => $ticket->mode(),
            'region' => $ticket->region(),
            'matchId' => $ticket->matchedMatchId(),
        ];
    }

    /** @return array<string,mixed>|null */
    public function matchSummary(?GameMatch $match, string $playerId): ?array
    {
        if ($match === null) {
            return null;
        }

        $state = $match->state();
        $opponentId = $match->playerId() === $playerId ? $match->opponentPlayerId() : $match->playerId();

        return [
            'matchId' => $match->id(),
            'queue' => $match->queueType(),
            'status' => $match->status(),
            'currentPlayerId' => $state['currentPlayerId'] ?? null,
            'turnNo' => (int) ($state['turnNo'] ?? 0),
            'winner' => $state['winner'] ?? null,
            'opponentPlayerId' => $opponentId,
            'botName' => $match->botName(),
        ];
    }
}
