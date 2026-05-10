<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class UserRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function create(string $id, string $username, string $email, string $passwordHash): User
    {
        $user = new User(
            $id,
            strtolower($username),
            strtolower($email),
            $passwordHash,
            new DateTimeImmutable(),
        );
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function findById(string $id): ?User
    {
        return $this->entityManager->find(User::class, $id);
    }

    public function findByUsernameOrEmail(string $identifier): ?User
    {
        return $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.username = :identifier OR u.email = :identifier')
            ->setParameter('identifier', strtolower($identifier))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
