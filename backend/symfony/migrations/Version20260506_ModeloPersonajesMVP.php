<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506_ModeloPersonajesMVP extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Modelo de personajes, equipos y bonificadores de partida';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS personajes (
                id VARCHAR(32) PRIMARY KEY,
                nombre VARCHAR(64) NOT NULL,
                rol_sinergia VARCHAR(32) NOT NULL,
                descripcion TEXT NOT NULL,
                habilidad_especial_nombre VARCHAR(96) NOT NULL,
                habilidad_especial_descripcion TEXT NOT NULL,
                efecto_especial_json JSONB NOT NULL DEFAULT '{}'::jsonb,
                coste_cargas INT NOT NULL DEFAULT 2,
                desbloqueado_inicial BOOLEAN NOT NULL DEFAULT FALSE,
                precio_monedas INT NOT NULL DEFAULT 0,
                activo BOOLEAN NOT NULL DEFAULT TRUE,
                orden INT NOT NULL DEFAULT 0
            )");
        $this->addSql("CREATE TABLE IF NOT EXISTS jugador_personajes (
                jugador_id UUID NOT NULL,
                personaje_id VARCHAR(32) NOT NULL,
                desbloqueado BOOLEAN NOT NULL DEFAULT FALSE,
                nivel_maestria INT NOT NULL DEFAULT 1,
                xp_maestria INT NOT NULL DEFAULT 0,
                desbloqueado_en TIMESTAMP NULL,
                actualizado_en TIMESTAMP NOT NULL DEFAULT NOW(),
                PRIMARY KEY (jugador_id, personaje_id)
            )");
        $this->addSql("CREATE TABLE IF NOT EXISTS equipos_jugador (
                jugador_id UUID NOT NULL,
                slot SMALLINT NOT NULL,
                personaje_id VARCHAR(32) NOT NULL,
                actualizado_en TIMESTAMP NOT NULL DEFAULT NOW(),
                PRIMARY KEY (jugador_id, slot),
                UNIQUE (jugador_id, personaje_id),
                CONSTRAINT chk_equipos_jugador_slot CHECK (slot BETWEEN 1 AND 3)
            )");
        $this->addSql("CREATE TABLE IF NOT EXISTS bonificadores_partida (
                id VARCHAR(64) PRIMARY KEY,
                nombre VARCHAR(96) NOT NULL,
                categoria_volatilidad VARCHAR(16) NOT NULL,
                descripcion TEXT NOT NULL,
                reglas_json JSONB NOT NULL DEFAULT '{}'::jsonb,
                activo BOOLEAN NOT NULL DEFAULT TRUE,
                orden INT NOT NULL DEFAULT 0
            )");
    }
}
