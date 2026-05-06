<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506_IdempotenciaOperacionesCriticas extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persistencia de respuestas idempotentes para operaciones criticas';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS api_idempotency (
                scope VARCHAR(96) NOT NULL,
                player_id UUID NOT NULL,
                idempotency_key VARCHAR(128) NOT NULL,
                request_hash CHAR(64) NOT NULL,
                response_json JSONB NOT NULL DEFAULT '{}'::jsonb,
                created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                PRIMARY KEY (scope, player_id, idempotency_key)
            )");
    }
}
