<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CharacterCatalog;
use Doctrine\ORM\EntityManagerInterface;

final class CatalogoPersonajesRepositorio
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /** @return list<array<string, mixed>> */
    public function todos(): array
    {
        $entities = $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(CharacterCatalog::class, 'p')
            ->where('p.active = true')
            ->orderBy('p.sortOrder', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(
            static fn(CharacterCatalog $character): array => $character->toApiArray(),
            $entities
        );
    }

    /** @return array<string, mixed>|null */
    public function buscar(string $personajeId): ?array
    {
        $entity = $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(CharacterCatalog::class, 'p')
            ->where('p.id = :id')
            ->andWhere('p.active = true')
            ->setParameter('id', $personajeId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $entity instanceof CharacterCatalog ? $entity->toApiArray() : null;
    }

    /** @param list<mixed> $items */
    public function replaceAll(array $items): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\CharacterCatalog c')->execute();

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $this->entityManager->persist(new CharacterCatalog(
                (string) ($item['id'] ?? ''),
                (string) ($item['nombre'] ?? ''),
                (string) ($item['rolSinergia'] ?? ''),
                (string) ($item['descripcion'] ?? ''),
                (string) ($item['habilidadEspecialNombre'] ?? ''),
                (string) ($item['habilidadEspecialDescripcion'] ?? ''),
                $this->decodeJsonLikeArray($item['efectoEspecial'] ?? []),
                (int) ($item['costeCargas'] ?? 2),
                !empty($item['desbloqueadoInicial']),
                (int) ($item['precioMonedas'] ?? 0),
                array_key_exists('activo', $item) ? !empty($item['activo']) : true,
                (int) ($item['orden'] ?? ($index + 1)),
            ));
        }

        $this->entityManager->flush();
    }

    /** @return array<string, mixed> */
    private function decodeJsonLikeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return [];
    }
}
