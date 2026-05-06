<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506_SistemaTitulosSP extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Columnas competitivas SP y tabla de titulos';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE player_profiles ADD COLUMN IF NOT EXISTS puntos_habilidad INT NOT NULL DEFAULT 1000");
        $this->addSql("ALTER TABLE player_profiles ADD COLUMN IF NOT EXISTS titulo_competitivo VARCHAR(64) NOT NULL DEFAULT 'Combatiente'");
        $this->addSql("ALTER TABLE player_profiles ADD COLUMN IF NOT EXISTS posicion_competitiva INT NULL");
        $this->addSql("CREATE TABLE IF NOT EXISTS titulos_competitivos (
                id VARCHAR(64) PRIMARY KEY,
                nombre VARCHAR(96) NOT NULL,
                cupo INT NOT NULL,
                orden INT NOT NULL DEFAULT 0,
                activo BOOLEAN NOT NULL DEFAULT TRUE
            )");
    }
}
