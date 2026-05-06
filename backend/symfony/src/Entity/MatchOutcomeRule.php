<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'match_outcome_rules')]
final class MatchOutcomeRule
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(name: 'queue_type', type: 'string', length: 32)]
        private string $queueType,
        #[ORM\Id]
        #[ORM\Column(name: 'outcome_key', type: 'string', length: 16)]
        private string $outcomeKey,
        #[ORM\Column(name: 'global_mmr_delta', type: 'integer')]
        private int $globalMmrDelta,
        #[ORM\Column(name: 'champion_mmr_delta', type: 'integer')]
        private int $championMmrDelta,
        #[ORM\Column(type: 'integer')]
        private int $coins,
        #[ORM\Column(type: 'integer')]
        private int $gems,
        #[ORM\Column(type: 'integer')]
        private int $xp,
        #[ORM\Column(name: 'mastery_xp', type: 'integer')]
        private int $masteryXp,
    ) {
    }

    /** @return array{queueType:string,outcomeKey:string,globalMmrDelta:int,championMmrDelta:int,coins:int,gems:int,xp:int,masteryXp:int} */
    public function toApiArray(): array
    {
        return [
            'queueType' => $this->queueType,
            'outcomeKey' => $this->outcomeKey,
            'globalMmrDelta' => $this->globalMmrDelta,
            'championMmrDelta' => $this->championMmrDelta,
            'coins' => $this->coins,
            'gems' => $this->gems,
            'xp' => $this->xp,
            'masteryXp' => $this->masteryXp,
        ];
    }

    /** @return array{globalMmrDelta:int,championMmrDelta:int,coins:int,gems:int,xp:int,masteryXp:int} */
    public function toSettlementArray(): array
    {
        return [
            'globalMmrDelta' => $this->globalMmrDelta,
            'championMmrDelta' => $this->championMmrDelta,
            'coins' => $this->coins,
            'gems' => $this->gems,
            'xp' => $this->xp,
            'masteryXp' => $this->masteryXp,
        ];
    }
}
