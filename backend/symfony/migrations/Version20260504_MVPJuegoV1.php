<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260504_MVPJuegoV1 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Esquema base del backend MVP alineado con la API actual';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS auth_users (
                id UUID PRIMARY KEY,
                username VARCHAR(64) NOT NULL UNIQUE,
                email VARCHAR(190) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT NOW()
            )");
        $this->addSql("CREATE TABLE IF NOT EXISTS player_profiles (
                player_id UUID PRIMARY KEY,
                display_name VARCHAR(64) NOT NULL,
                rank_label VARCHAR(32) NOT NULL DEFAULT 'Bronce I',
                mmr_global INT NOT NULL DEFAULT 1000,
                region VARCHAR(32) NOT NULL DEFAULT 'eu-west',
                coins INT NOT NULL DEFAULT 0,
                gems INT NOT NULL DEFAULT 0,
                experience_total INT NOT NULL DEFAULT 0,
                level INT NOT NULL DEFAULT 1,
                total_matches INT NOT NULL DEFAULT 0,
                wins INT NOT NULL DEFAULT 0,
                losses INT NOT NULL DEFAULT 0,
                updated_at TIMESTAMP NOT NULL DEFAULT NOW()
            )");
        $this->addSql("CREATE TABLE IF NOT EXISTS match_history (
                id UUID PRIMARY KEY,
                player_id UUID NOT NULL,
                enemy_name VARCHAR(64) NOT NULL,
                result VARCHAR(16) NOT NULL,
                turns INT NOT NULL DEFAULT 0,
                mmr_delta INT NOT NULL DEFAULT 0,
                played_at TIMESTAMP NOT NULL DEFAULT NOW()
            )");
        $this->addSql("CREATE TABLE IF NOT EXISTS matches (
                id UUID PRIMARY KEY,
                queue_type VARCHAR(16) NOT NULL,
                p1_id UUID NOT NULL,
                p2_id UUID NULL,
                bot_name VARCHAR(64) NULL,
                status VARCHAR(16) NOT NULL,
                state_json JSONB NOT NULL DEFAULT '{}'::jsonb,
                started_at TIMESTAMP NOT NULL DEFAULT NOW(),
                ended_at TIMESTAMP NULL,
                winner_id UUID NULL
            )");
        $this->addSql("CREATE TABLE IF NOT EXISTS matchmaking_tickets (
                id UUID PRIMARY KEY,
                queue_type VARCHAR(16) NOT NULL,
                mode VARCHAR(32) NOT NULL DEFAULT 'normal_bot',
                player_id UUID NOT NULL,
                champion_id VARCHAR(32) NOT NULL,
                region VARCHAR(32) NOT NULL,
                mmr INT NOT NULL DEFAULT 1200,
                status VARCHAR(16) NOT NULL,
                matched_match_id UUID NULL,
                created_at TIMESTAMP NOT NULL DEFAULT NOW()
            )");
        $this->addSql("CREATE TABLE IF NOT EXISTS turns (
                id UUID PRIMARY KEY,
                match_id UUID NOT NULL,
                turn_no INT NOT NULL,
                actor_id UUID NOT NULL,
                action VARCHAR(16) NOT NULL,
                payload_json JSONB NOT NULL,
                result_json JSONB NOT NULL,
                server_state_version INT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT NOW()
            )");
        $this->addSql("CREATE TABLE IF NOT EXISTS player_champion_ratings (
                player_id UUID NOT NULL,
                champion_id VARCHAR(32) NOT NULL,
                mmr INT NOT NULL DEFAULT 1000,
                matches INT NOT NULL DEFAULT 0,
                wins INT NOT NULL DEFAULT 0,
                updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
                PRIMARY KEY (player_id, champion_id)
            )");
        $this->addSql("CREATE TABLE IF NOT EXISTS match_settlements (
                match_id UUID NOT NULL,
                player_id UUID NOT NULL,
                global_mmr_delta INT NOT NULL,
                champion_mmr_delta INT NOT NULL,
                coins INT NOT NULL,
                gems INT NOT NULL DEFAULT 0,
                xp INT NOT NULL DEFAULT 0,
                mastery_xp INT NOT NULL DEFAULT 0,
                winner_ref VARCHAR(16) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                PRIMARY KEY (match_id, player_id)
            )");
        $this->addSql("CREATE TABLE IF NOT EXISTS store_catalog (
                id VARCHAR(64) PRIMARY KEY,
                name VARCHAR(128) NOT NULL,
                item_type VARCHAR(32) NOT NULL,
                price_coins INT NOT NULL,
                sort_order INT NOT NULL DEFAULT 0
            )");
        $this->addSql("CREATE TABLE IF NOT EXISTS mission_catalog (
                id VARCHAR(64) PRIMARY KEY,
                title VARCHAR(160) NOT NULL,
                target_value INT NOT NULL,
                reward_xp INT NOT NULL,
                reward_coins INT NOT NULL,
                sort_order INT NOT NULL DEFAULT 0
            )");
        $this->addSql("CREATE TABLE IF NOT EXISTS match_outcome_rules (
                queue_type VARCHAR(32) NOT NULL,
                outcome_key VARCHAR(16) NOT NULL,
                global_mmr_delta INT NOT NULL,
                champion_mmr_delta INT NOT NULL,
                coins INT NOT NULL,
                gems INT NOT NULL DEFAULT 0,
                xp INT NOT NULL,
                mastery_xp INT NOT NULL,
                PRIMARY KEY (queue_type, outcome_key)
            )");
        $this->addSql("CREATE TABLE IF NOT EXISTS player_inventory (
                player_id UUID NOT NULL,
                item_id VARCHAR(64) NOT NULL,
                item_type VARCHAR(32) NOT NULL,
                quantity INT NOT NULL DEFAULT 0,
                equipped BOOLEAN NOT NULL DEFAULT FALSE,
                acquired_at TIMESTAMP NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
                PRIMARY KEY (player_id, item_id)
            )");
        $this->addSql("CREATE TABLE IF NOT EXISTS player_daily_missions (
                player_id UUID NOT NULL,
                mission_date DATE NOT NULL,
                mission_id VARCHAR(64) NOT NULL,
                target_value INT NOT NULL,
                progress_value INT NOT NULL DEFAULT 0,
                reward_xp INT NOT NULL DEFAULT 0,
                reward_coins INT NOT NULL DEFAULT 0,
                claimed BOOLEAN NOT NULL DEFAULT FALSE,
                updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
                PRIMARY KEY (player_id, mission_date, mission_id)
            )");
        $this->addSql("CREATE TABLE IF NOT EXISTS player_daily_mission_champions (
                player_id UUID NOT NULL,
                mission_date DATE NOT NULL,
                champion_id VARCHAR(32) NOT NULL,
                PRIMARY KEY (player_id, mission_date, champion_id)
            )");
    }
}
