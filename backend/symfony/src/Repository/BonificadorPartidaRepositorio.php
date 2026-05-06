<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MatchBooster;
use Doctrine\ORM\EntityManagerInterface;

final class BonificadorPartidaRepositorio
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /** @return list<array<string, mixed>> */
    public function todos(): array
    {
        $entities = $this->entityManager->createQueryBuilder()
            ->select('b')
            ->from(MatchBooster::class, 'b')
            ->where('b.active = true')
            ->orderBy('b.sortOrder', 'ASC')
            ->addOrderBy('b.id', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(
            static fn(MatchBooster $booster): array => $booster->toApiArray(),
            $entities
        );
    }

    /** @param list<mixed> $items */
    public function replaceAll(array $items): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\MatchBooster b')->execute();

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $this->entityManager->persist(new MatchBooster(
                (string) ($item['id'] ?? ''),
                (string) ($item['nombre'] ?? ''),
                (string) ($item['categoriaVolatilidad'] ?? ''),
                (string) ($item['descripcion'] ?? ''),
                is_array($item['reglas'] ?? null) ? (array) $item['reglas'] : [],
                array_key_exists('activo', $item) ? !empty($item['activo']) : true,
                (int) ($item['orden'] ?? ($index + 1)),
            ));
        }

        $this->entityManager->flush();
    }
}
