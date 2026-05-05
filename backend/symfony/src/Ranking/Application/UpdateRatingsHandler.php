<?php

declare(strict_types=1);

namespace App\Ranking\Application;

use App\Combat\Domain\MvpChampionRoster;

final class UpdateRatingsHandler
{
    public function __construct(private RatingsRepository $ratings)
    {
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function __invoke(array $input): array
    {
        $winnerId = (string) ($input['winnerId'] ?? '');
        $loserId = (string) ($input['loserId'] ?? '');
        $winnerChampion = (string) ($input['winnerChampionId'] ?? '');
        $loserChampion = (string) ($input['loserChampionId'] ?? '');

        if ($winnerId === '' || $loserId === '' || $winnerChampion === '' || $loserChampion === '') {
            throw new \InvalidArgumentException('Invalid rating payload.');
        }

        if (!MvpChampionRoster::isValid($winnerChampion) || !MvpChampionRoster::isValid($loserChampion)) {
            throw new \InvalidArgumentException('Champion is outside MVP roster.');
        }

        $winnerGlobal = $this->ratings->load($winnerId, 'global', null);
        $loserGlobal = $this->ratings->load($loserId, 'global', null);
        $winnerChampionRating = $this->ratings->load($winnerId, 'ranked', $winnerChampion);
        $loserChampionRating = $this->ratings->load($loserId, 'ranked', $loserChampion);

        $globalDelta = $this->calculateDelta($winnerGlobal['mmr'], $loserGlobal['mmr'], 24);
        $championDelta = $this->calculateDelta($winnerChampionRating['mmr'], $loserChampionRating['mmr'], 30);

        $this->ratings->save($winnerId, 'global', null, $winnerGlobal['mmr'] + $globalDelta);
        $this->ratings->save($loserId, 'global', null, $loserGlobal['mmr'] - $globalDelta);
        $this->ratings->save($winnerId, 'ranked', $winnerChampion, $winnerChampionRating['mmr'] + $championDelta);
        $this->ratings->save($loserId, 'ranked', $loserChampion, $loserChampionRating['mmr'] - $championDelta);

        return [
            'winnerId' => $winnerId,
            'loserId' => $loserId,
            'globalDelta' => $globalDelta,
            'championDelta' => $championDelta,
        ];
    }

    private function calculateDelta(int $winnerMmr, int $loserMmr, int $kFactor): int
    {
        $expected = 1 / (1 + pow(10, (($loserMmr - $winnerMmr) / 400)));
        $delta = (int) round($kFactor * (1 - $expected));

        return max(5, min(30, $delta));
    }
}

interface RatingsRepository
{
    /** @return array{mmr: int} */
    public function load(string $playerId, string $queueType, ?string $championId): array;

    public function save(string $playerId, string $queueType, ?string $championId, int $mmr): void;
}
