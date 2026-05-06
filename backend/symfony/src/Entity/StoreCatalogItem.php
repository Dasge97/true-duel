<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'store_catalog')]
final class StoreCatalogItem
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 64)]
        private string $id,
        #[ORM\Column(name: 'name', type: 'string', length: 128)]
        private string $name,
        #[ORM\Column(name: 'item_type', type: 'string', length: 32)]
        private string $itemType,
        #[ORM\Column(name: 'price_coins', type: 'integer')]
        private int $priceCoins,
        #[ORM\Column(name: 'sort_order', type: 'integer')]
        private int $sortOrder,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function itemType(): string
    {
        return $this->itemType;
    }

    /** @return array{id:string,name:string,type:string,priceCoins:int} */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->itemType,
            'priceCoins' => $this->priceCoins,
        ];
    }
}
