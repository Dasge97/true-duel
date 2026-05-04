<?php

declare(strict_types=1);

namespace App\Onboarding\Application;

final class ProgressOnboardingHandler
{
    public function __construct(private OnboardingRepository $repository)
    {
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function __invoke(array $input): array
    {
        $playerId = (string) ($input['playerId'] ?? '');
        $step = (string) ($input['step'] ?? '');

        if ($playerId === '' || $step === '') {
            throw new \InvalidArgumentException('Invalid onboarding payload.');
        }

        $state = $this->repository->load($playerId);
        if ($step === 'tutorial_completed') {
            $state['tutorialCompleted'] = true;
        }

        if ($step === 'assisted_match_completed') {
            $state['assistedMatches'] = min(3, (int) $state['assistedMatches'] + 1);
        }

        $state['rankedUnlocked'] = (bool) $state['tutorialCompleted'] && (int) $state['assistedMatches'] >= 3;
        $this->repository->save($playerId, $state);

        return [
            'playerId' => $playerId,
            'tutorialCompleted' => $state['tutorialCompleted'],
            'assistedMatches' => $state['assistedMatches'],
            'rankedUnlocked' => $state['rankedUnlocked'],
        ];
    }
}

interface OnboardingRepository
{
    /** @return array{tutorialCompleted: bool, assistedMatches: int, rankedUnlocked: bool} */
    public function load(string $playerId): array;

    /** @param array{tutorialCompleted: bool, assistedMatches: int, rankedUnlocked: bool} $state */
    public function save(string $playerId, array $state): void;
}
