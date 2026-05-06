<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'auth_users')]
final class User
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 36)]
        private string $id,
        #[ORM\Column(type: 'string', length: 64, unique: true)]
        private string $username,
        #[ORM\Column(type: 'string', length: 190, unique: true)]
        private string $email,
        #[ORM\Column(name: 'password_hash', type: 'string', length: 255)]
        private string $passwordHash,
        #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
        private DateTimeImmutable $createdAt = new DateTimeImmutable(),
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
