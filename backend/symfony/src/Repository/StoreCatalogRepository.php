<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\StoreCatalogItem;
use Doctrine\ORM\EntityManagerInterface;

final class StoreCatalogRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /** @return list<array{id:string,name:string,type:string,priceCoins:int}> */
    public function all(): array
    {
        $entities = $this->entityManager->createQueryBuilder()
            ->select('s')
            ->from(StoreCatalogItem::class, 's')
            ->orderBy('s.sortOrder', 'ASC')
            ->addOrderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(
            static fn(StoreCatalogItem $item): array => $item->toApiArray(),
            $entities
        );
    }

    /** @return array{id:string,name:string,type:string,priceCoins:int}|null */
    public function find(string $itemId): ?array
    {
        $entity = $this->entityManager->find(StoreCatalogItem::class, $itemId);
        return $entity instanceof StoreCatalogItem ? $entity->toApiArray() : null;
    }

    /** @param list<mixed> $items */
    public function replaceAll(array $items): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\StoreCatalogItem s')->execute();

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $this->entityManager->persist(new StoreCatalogItem(
                (string) ($item['id'] ?? ''),
                (string) ($item['name'] ?? ''),
                (string) ($item['type'] ?? ''),
                (int) ($item['priceCoins'] ?? 0),
                (int) ($item['sortOrder'] ?? ($index + 1)),
            ));
        }

        $this->entityManager->flush();
    }
}
