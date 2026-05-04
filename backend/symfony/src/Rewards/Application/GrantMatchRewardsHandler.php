<?php

declare(strict_types=1);

namespace App\Rewards\Application;

final class GrantMatchRewardsHandler
{
    public function __construct(
        private RewardLedgerRepository $ledger,
        private EconomyCatalogRepository $catalog,
        private FeatureFlagProvider $featureFlags,
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function __invoke(array $input): array
    {
        if (!$this->featureFlags->isEnabled('rewards_enabled')) {
            return ['status' => 403, 'code' => 'REWARDS_DISABLED'];
        }

        $playerId = (string) ($input['playerId'] ?? '');
        $won = (bool) ($input['won'] ?? false);
        $matchId = (string) ($input['matchId'] ?? '');

        if ($playerId === '' || $matchId === '') {
            throw new \InvalidArgumentException('Invalid rewards payload.');
        }

        if ($this->catalog->hasCombatAffectingItem()) {
            throw new \RuntimeException('Economy catalog violates non-P2W guardrail.');
        }

        $coins = $won ? 40 : 25;
        $coinsGrantedToday = $this->ledger->coinsGrantedToday($playerId);
        $dailyCap = 500;
        $grant = max(0, min($coins, $dailyCap - $coinsGrantedToday));

        $this->ledger->append($playerId, 'match_complete', $grant, 0, [
            'matchId' => $matchId,
            'won' => $won,
        ]);

        return [
            'status' => 200,
            'coins' => $grant,
            'gems' => 0,
            'dailyRemaining' => max(0, $dailyCap - ($coinsGrantedToday + $grant)),
        ];
    }
}

interface RewardLedgerRepository
{
    public function coinsGrantedToday(string $playerId): int;

    /** @param array<string, mixed> $metadata */
    public function append(string $playerId, string $source, int $coins, int $gems, array $metadata): void;
}

interface EconomyCatalogRepository
{
    public function hasCombatAffectingItem(): bool;
}

interface FeatureFlagProvider
{
    public function isEnabled(string $flag): bool;
}
