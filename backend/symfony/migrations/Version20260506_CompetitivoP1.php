<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506_CompetitivoP1 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persistencia para job competitivo, salud del ladder y admin de contenido';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE player_profiles ADD COLUMN IF NOT EXISTS last_competitive_activity_at TIMESTAMP NULL");
        $this->addSql("ALTER TABLE player_profiles ADD COLUMN IF NOT EXISTS competitive_matches_played INT NOT NULL DEFAULT 0");
        $this->addSql("CREATE TABLE IF NOT EXISTS competitive_match_log (
                id UUID PRIMARY KEY,
                match_id UUID NOT NULL,
                player_id UUID NOT NULL,
                opponent_player_id UUID NULL,
                result VARCHAR(16) NOT NULL,
                queue_type VARCHAR(16) NOT NULL,
                abandoned BOOLEAN NOT NULL DEFAULT FALSE,
                battle_score INT NOT NULL DEFAULT 0,
                sp_delta INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT NOW()
            )");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_competitive_match_log_player_created_at
             ON competitive_match_log (player_id, created_at DESC)");
        $this->addSql("CREATE INDEX IF NOT EXISTS idx_competitive_match_log_player_opponent_created_at
             ON competitive_match_log (player_id, opponent_player_id, created_at DESC)");
        $this->addSql("CREATE UNIQUE INDEX IF NOT EXISTS uniq_competitive_match_log_player_match
             ON competitive_match_log (match_id, player_id)");
    }
}
