<?php

declare(strict_types=1);

namespace App\Matchmaking\Application;

final class EnqueuePlayerHandler
{
    public function __construct(
        private QueueTicketRepository $tickets,
        private MatchmakingPolicy $policy,
        private FeatureFlagProvider $featureFlags,
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function __invoke(array $input): array
    {
        $queue = (string) ($input['queue'] ?? 'normal');
        $playerId = (string) ($input['playerId'] ?? '');
        $championId = (string) ($input['championId'] ?? '');
        $region = (string) ($input['region'] ?? 'global');

        if ($playerId == '' || $championId == '') {
            throw new \InvalidArgumentException('Invalid enqueue payload.');
        }

        if ($queue === 'ranked' && !$this->featureFlags->isEnabled('ranked_enabled')) {
            return ['status' => 403, 'code' => 'RANKED_DISABLED'];
        }

        $ticket = $this->tickets->enqueue($queue, $playerId, $championId, $region);
        $window = $this->policy->resolveWindow((int) $ticket['waitSeconds']);
        $match = $this->tickets->tryMatch((string) $ticket['ticketId'], $window);

        if ($match !== null) {
            return [
                'status' => 200,
                'ticketId' => $ticket['ticketId'],
                'matchId' => $match['matchId'],
                'etaSec' => 0,
                'mmrWindow' => $window,
            ];
        }

        return [
            'status' => 202,
            'ticketId' => $ticket['ticketId'],
            'etaSec' => $this->policy->estimateEtaSeconds($queue, $window),
            'mmrWindow' => $window,
        ];
    }
}

interface QueueTicketRepository
{
    /** @return array{ticketId: string, waitSeconds: int} */
    public function enqueue(string $queue, string $playerId, string $championId, string $region): array;

    /** @return array{matchId: string}|null */
    public function tryMatch(string $ticketId, int $mmrWindow): ?array;
}

interface MatchmakingPolicy
{
    public function resolveWindow(int $waitSeconds): int;

    public function estimateEtaSeconds(string $queue, int $window): int;
}

interface FeatureFlagProvider
{
    public function isEnabled(string $flag): bool;
}
