<?php

declare(strict_types=1);

namespace App\Profile\Application;

final class GetCompetitiveProfileHandler
{
    public function __construct(private ProfileReadRepository $profiles)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(string $playerId): array
    {
        if ($playerId === '') {
            throw new \InvalidArgumentException('playerId is required');
        }

        $profile = $this->profiles->fetch($playerId);
        $updatedAt = new \DateTimeImmutable((string) $profile['updatedAt']);
        $ageSeconds = time() - $updatedAt->getTimestamp();

        return [
            'playerId' => $playerId,
            'accountLevel' => (int) $profile['accountLevel'],
            'mmrGlobal' => (int) $profile['mmrGlobal'],
            'mmrByChampion' => (array) $profile['mmrByChampion'],
            'recentMatches' => (array) $profile['recentMatches'],
            'nonCombatStats' => (array) $profile['nonCombatStats'],
            'freshnessSeconds' => $ageSeconds,
            'isFresh' => $ageSeconds <= 300,
        ];
    }
}

interface ProfileReadRepository
{
    /** @return array<string, mixed> */
    public function fetch(string $playerId): array;
}
