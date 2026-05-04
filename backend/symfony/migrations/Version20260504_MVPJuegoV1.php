<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260504_MVPJuegoV1 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'MVP Juego V1 core schema with idempotency and economy constraints';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE players (id UUID NOT NULL, account_level INT NOT NULL DEFAULT 1, region VARCHAR(32) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE ratings (id UUID NOT NULL, player_id UUID NOT NULL, queue_type VARCHAR(16) NOT NULL, champion_id VARCHAR(32) DEFAULT NULL, mmr INT NOT NULL, volatility NUMERIC(5, 2) NOT NULL DEFAULT 1.00, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE matches (id UUID NOT NULL, queue_type VARCHAR(16) NOT NULL, p1_id UUID NOT NULL, p2_id UUID NOT NULL, status VARCHAR(16) NOT NULL, seed BIGINT NOT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, ended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, winner_id UUID DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE turns (id UUID NOT NULL, match_id UUID NOT NULL, turn_no INT NOT NULL, actor_id UUID NOT NULL, action VARCHAR(16) NOT NULL, payload_json JSON NOT NULL, result_json JSON NOT NULL, idempotency_key UUID NOT NULL, server_state_version INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_turn_idempotency ON turns (match_id, actor_id, idempotency_key)');
        $this->addSql('CREATE TABLE reward_ledger (id UUID NOT NULL, player_id UUID NOT NULL, source VARCHAR(32) NOT NULL, amount_coins INT NOT NULL DEFAULT 0, amount_gems INT NOT NULL DEFAULT 0, metadata_json JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE economy_catalog (id UUID NOT NULL, sku VARCHAR(64) NOT NULL, kind VARCHAR(32) NOT NULL, affects_combat BOOLEAN NOT NULL DEFAULT false, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE economy_catalog ADD CONSTRAINT chk_economy_not_p2w CHECK (affects_combat = false)');
        $this->addSql('CREATE TABLE event_outbox (id UUID NOT NULL, event_type VARCHAR(64) NOT NULL, aggregate_id UUID NOT NULL, payload_json JSON NOT NULL, published_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE event_outbox ADD last_error VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE TABLE matchmaking_tickets (id UUID NOT NULL, queue_type VARCHAR(16) NOT NULL, player_id UUID NOT NULL, champion_id VARCHAR(32) NOT NULL, region VARCHAR(32) NOT NULL, mmr INT NOT NULL DEFAULT 1200, status VARCHAR(16) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE player_profiles (player_id UUID NOT NULL, account_level INT NOT NULL DEFAULT 1, mmr_global INT NOT NULL DEFAULT 1200, non_combat_stats JSON NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(player_id))');
        $this->addSql('CREATE TABLE onboarding_progress (player_id UUID NOT NULL, tutorial_completed BOOLEAN NOT NULL DEFAULT false, assisted_matches INT NOT NULL DEFAULT 0, ranked_unlocked BOOLEAN NOT NULL DEFAULT false, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(player_id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE event_outbox');
        $this->addSql('DROP TABLE onboarding_progress');
        $this->addSql('DROP TABLE player_profiles');
        $this->addSql('DROP TABLE matchmaking_tickets');
        $this->addSql('DROP TABLE economy_catalog');
        $this->addSql('DROP TABLE reward_ledger');
        $this->addSql('DROP TABLE turns');
        $this->addSql('DROP TABLE matches');
        $this->addSql('DROP TABLE ratings');
        $this->addSql('DROP TABLE players');
    }
}
