<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MissionCatalogItem;
use Doctrine\ORM\EntityManagerInterface;

final class MissionCatalogRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /** @return list<array{id:string,title:string,target:int,rewardXp:int,rewardCoins:int}> */
    public function all(): array
    {
        $entities = $this->entityManager->createQueryBuilder()
            ->select('m')
            ->from(MissionCatalogItem::class, 'm')
            ->orderBy('m.sortOrder', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(
            static fn(MissionCatalogItem $item): array => $item->toApiArray(),
            $entities
        );
    }

    /** @return array{id:string,title:string,target:int,rewardXp:int,rewardCoins:int}|null */
    public function find(string $missionId): ?array
    {
        $entity = $this->entityManager->find(MissionCatalogItem::class, $missionId);
        return $entity instanceof MissionCatalogItem ? $entity->toApiArray() : null;
    }

    /** @param list<mixed> $items */
    public function replaceAll(array $items): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\MissionCatalogItem m')->execute();

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $this->entityManager->persist(new MissionCatalogItem(
                (string) ($item['id'] ?? ''),
                (string) ($item['title'] ?? ''),
                (int) ($item['target'] ?? 0),
                (int) ($item['rewardXp'] ?? 0),
                (int) ($item['rewardCoins'] ?? 0),
                (int) ($item['sortOrder'] ?? ($index + 1)),
            ));
        }

        $this->entityManager->flush();
    }
}
