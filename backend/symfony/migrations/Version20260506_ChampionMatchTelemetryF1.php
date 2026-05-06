<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506_ChampionMatchTelemetryF1 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Telemetria agregable por match y campeon para F1 de sistema de juego';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS champion_match_telemetry (
                id UUID PRIMARY KEY,
                match_id UUID NOT NULL,
                side_ref VARCHAR(16) NOT NULL,
                player_id UUID NULL,
                champion_id VARCHAR(32) NOT NULL,
                queue_type VARCHAR(16) NOT NULL,
                is_bot BOOLEAN NOT NULL DEFAULT FALSE,
                attack_actions INT NOT NULL DEFAULT 0,
                defend_actions INT NOT NULL DEFAULT 0,
                special_actions INT NOT NULL DEFAULT 0,
                exposed_applied INT NOT NULL DEFAULT 0,
                fortified_applied INT NOT NULL DEFAULT 0,
                bleed_applied INT NOT NULL DEFAULT 0,
                overload_applied INT NOT NULL DEFAULT 0,
                silence_applied INT NOT NULL DEFAULT 0,
                shield_applied INT NOT NULL DEFAULT 0,
                result VARCHAR(16) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP NOT NULL DEFAULT NOW()
            )");
        $this->addSql("CREATE UNIQUE INDEX IF NOT EXISTS uniq_champion_match_telemetry_match_side
            ON champion_match_telemetry (match_id, side_ref)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_champion_match_telemetry_champion
            ON champion_match_telemetry (champion_id)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_champion_match_telemetry_result
            ON champion_match_telemetry (result)");
    }
}
