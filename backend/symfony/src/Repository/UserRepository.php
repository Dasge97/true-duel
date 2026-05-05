<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use PDO;

final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(string $id, string $username, string $email, string $passwordHash): User
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO auth_users (id, username, email, password_hash)
             VALUES (:id, :username, :email, :password_hash)'
        );
        $statement->execute([
            ':id' => $id,
            ':username' => strtolower($username),
            ':email' => strtolower($email),
            ':password_hash' => $passwordHash,
        ]);

        return new User($id, strtolower($username), strtolower($email), $passwordHash, gmdate('c'));
    }

    public function findByUsernameOrEmail(string $identifier): ?User
    {
        $statement = $this->pdo->prepare(
            'SELECT id, username, email, password_hash, created_at
             FROM auth_users
             WHERE username = :identifier OR email = :identifier
             LIMIT 1'
        );
        $statement->execute([':identifier' => strtolower($identifier)]);

        $row = $statement->fetch();
        if (!is_array($row)) {
            return null;
        }

        return new User(
            (string) $row['id'],
            (string) $row['username'],
            (string) $row['email'],
            (string) $row['password_hash'],
            (string) $row['created_at'],
        );
    }
}
