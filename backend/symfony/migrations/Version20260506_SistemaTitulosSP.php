<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506_SistemaTitulosSP extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sistema competitivo SP con títulos por cupo';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE player_profiles ADD COLUMN IF NOT EXISTS puntos_habilidad INT NOT NULL DEFAULT 1000");
        $this->addSql("ALTER TABLE player_profiles ADD COLUMN IF NOT EXISTS titulo_competitivo VARCHAR(64) NOT NULL DEFAULT 'Combatiente'");
        $this->addSql("ALTER TABLE player_profiles ADD COLUMN IF NOT EXISTS posicion_competitiva INT DEFAULT NULL");
        $this->addSql('UPDATE player_profiles SET puntos_habilidad = GREATEST(0, mmr_global) WHERE puntos_habilidad IS NULL OR puntos_habilidad = 1000');

        $this->addSql("CREATE TABLE IF NOT EXISTS titulos_competitivos (id VARCHAR(64) NOT NULL, nombre VARCHAR(96) NOT NULL, cupo INT NOT NULL, orden INT NOT NULL DEFAULT 0, activo BOOLEAN NOT NULL DEFAULT TRUE, PRIMARY KEY(id))");
        $this->addSql("INSERT INTO titulos_competitivos (id, nombre, cupo, orden, activo) VALUES
            ('sp_leyenda_unica', 'Leyenda Única', 1, 1, TRUE),
            ('sp_gran_maestro', 'Gran Maestro', 5, 2, TRUE),
            ('sp_maestro', 'Maestro', 25, 3, TRUE),
            ('sp_diamante', 'Diamante', 75, 4, TRUE),
            ('sp_platino', 'Platino', 150, 5, TRUE),
            ('sp_oro', 'Oro', 300, 6, TRUE)
            ON CONFLICT (id) DO UPDATE
            SET nombre = EXCLUDED.nombre,
                cupo = EXCLUDED.cupo,
                orden = EXCLUDED.orden,
                activo = EXCLUDED.activo");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS titulos_competitivos');
        $this->addSql('ALTER TABLE player_profiles DROP COLUMN IF EXISTS posicion_competitiva');
        $this->addSql('ALTER TABLE player_profiles DROP COLUMN IF EXISTS titulo_competitivo');
        $this->addSql('ALTER TABLE player_profiles DROP COLUMN IF EXISTS puntos_habilidad');
    }
}

