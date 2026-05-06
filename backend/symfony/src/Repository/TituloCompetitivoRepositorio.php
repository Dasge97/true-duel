<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CompetitiveTitle;
use Doctrine\ORM\EntityManagerInterface;

final class TituloCompetitivoRepositorio
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /** @return list<array{id:string,nombre:string,cupo:int,orden:int,activo:bool}> */
    public function all(): array
    {
        $entities = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(CompetitiveTitle::class, 't')
            ->orderBy('t.sortOrder', 'ASC')
            ->addOrderBy('t.id', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(
            static fn(CompetitiveTitle $title): array => $title->toApiArray(),
            $entities
        );
    }

    /** @return list<array{nombre:string,cupo:int}> */
    public function tramosActivos(): array
    {
        $entities = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(CompetitiveTitle::class, 't')
            ->where('t.active = true')
            ->orderBy('t.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(
            static fn(CompetitiveTitle $title): array => $title->toActiveBracketArray(),
            $entities
        );
    }

    /** @param list<mixed> $items */
    public function replaceAll(array $items): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\CompetitiveTitle t')->execute();

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $this->entityManager->persist(new CompetitiveTitle(
                (string) ($item['id'] ?? 'titulo_' . $index),
                (string) ($item['nombre'] ?? 'Combatiente'),
                max(0, (int) ($item['cupo'] ?? 0)),
                (int) ($item['orden'] ?? ($index + 1)),
                array_key_exists('activo', $item) ? !empty($item['activo']) : true,
            ));
        }

        $this->entityManager->flush();
    }
}

