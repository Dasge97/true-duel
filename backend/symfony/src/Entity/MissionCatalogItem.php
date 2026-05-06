<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'mission_catalog')]
final class MissionCatalogItem
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 64)]
        private string $id,
        #[ORM\Column(type: 'string', length: 160)]
        private string $title,
        #[ORM\Column(name: 'target_value', type: 'integer')]
        private int $targetValue,
        #[ORM\Column(name: 'reward_xp', type: 'integer')]
        private int $rewardXp,
        #[ORM\Column(name: 'reward_coins', type: 'integer')]
        private int $rewardCoins,
        #[ORM\Column(name: 'sort_order', type: 'integer')]
        private int $sortOrder,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    /** @return array{id:string,title:string,target:int,rewardXp:int,rewardCoins:int} */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'target' => $this->targetValue,
            'rewardXp' => $this->rewardXp,
            'rewardCoins' => $this->rewardCoins,
        ];
    }
}
