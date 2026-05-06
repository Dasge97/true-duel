<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MatchOutcomeRule;
use Doctrine\ORM\EntityManagerInterface;

final class MatchOutcomeRuleRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /** @return list<array{queueType:string,outcomeKey:string,globalMmrDelta:int,championMmrDelta:int,coins:int,gems:int,xp:int,masteryXp:int}> */
    public function all(): array
    {
        $entities = $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(MatchOutcomeRule::class, 'r')
            ->orderBy('r.queueType', 'ASC')
            ->addOrderBy('r.outcomeKey', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(
            static fn(MatchOutcomeRule $rule): array => $rule->toApiArray(),
            $entities
        );
    }

    /** @return array{globalMmrDelta:int,championMmrDelta:int,coins:int,gems:int,xp:int,masteryXp:int}|null */
    public function find(string $queueType, string $outcomeKey): ?array
    {
        $entity = $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(MatchOutcomeRule::class, 'r')
            ->where('r.queueType = :queueType')
            ->andWhere('r.outcomeKey = :outcomeKey')
            ->setParameter('queueType', $queueType)
            ->setParameter('outcomeKey', $outcomeKey)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $entity instanceof MatchOutcomeRule ? $entity->toSettlementArray() : null;
    }

    /** @param list<mixed> $items */
    public function replaceAll(array $items): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\MatchOutcomeRule r')->execute();

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $this->entityManager->persist(new MatchOutcomeRule(
                (string) ($item['queueType'] ?? ''),
                (string) ($item['outcomeKey'] ?? ''),
                (int) ($item['globalMmrDelta'] ?? 0),
                (int) ($item['championMmrDelta'] ?? 0),
                (int) ($item['coins'] ?? 0),
                (int) ($item['gems'] ?? 0),
                (int) ($item['xp'] ?? 0),
                (int) ($item['masteryXp'] ?? 0),
            ));
        }

        $this->entityManager->flush();
    }
}
