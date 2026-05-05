<?php

declare(strict_types=1);

/**
 * Minimal dev bootstrap for local Docker runtime.
 * Creates auth/profile tables and seeds base users if they do not exist.
 */

$dsn = getenv('DATABASE_URL') ?: 'pgsql:host=db;port=5432;dbname=true_duel';
$user = getenv('DATABASE_USER') ?: 'true_duel';
$password = getenv('DATABASE_PASSWORD') ?: 'true_duel';

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "[bootstrap_db] DB connection failed: {$e->getMessage()}\n");
    exit(1);
}

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS auth_users (
    id UUID PRIMARY KEY,
    username VARCHAR(64) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);
SQL);

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS player_profiles (
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
);
SQL);

$pdo->exec(<<<'SQL'
ALTER TABLE player_profiles
    ADD COLUMN IF NOT EXISTS coins INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS gems INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS experience_total INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS level INT NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS total_matches INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS wins INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS losses INT NOT NULL DEFAULT 0;
SQL);

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS match_history (
    id UUID PRIMARY KEY,
    player_id UUID NOT NULL,
    enemy_name VARCHAR(64) NOT NULL,
    result VARCHAR(16) NOT NULL,
    turns INT NOT NULL DEFAULT 0,
    mmr_delta INT NOT NULL DEFAULT 0,
    played_at TIMESTAMP NOT NULL DEFAULT NOW()
);
SQL);

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS matches (
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
);
SQL);

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS matchmaking_tickets (
    id UUID PRIMARY KEY,
    queue_type VARCHAR(16) NOT NULL,
    player_id UUID NOT NULL,
    champion_id VARCHAR(32) NOT NULL,
    region VARCHAR(32) NOT NULL,
    mmr INT NOT NULL DEFAULT 1200,
    status VARCHAR(16) NOT NULL,
    matched_match_id UUID NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);
SQL);

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS turns (
    id UUID PRIMARY KEY,
    match_id UUID NOT NULL,
    turn_no INT NOT NULL,
    actor_id VARCHAR(64) NOT NULL,
    action VARCHAR(16) NOT NULL,
    payload_json JSONB NOT NULL,
    result_json JSONB NOT NULL,
    server_state_version INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);
SQL);

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS player_champion_ratings (
    player_id UUID NOT NULL,
    champion_id VARCHAR(32) NOT NULL,
    mmr INT NOT NULL DEFAULT 1000,
    matches INT NOT NULL DEFAULT 0,
    wins INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    PRIMARY KEY (player_id, champion_id)
);
SQL);

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS match_settlements (
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
);
SQL);

$pdo->exec(<<<'SQL'
ALTER TABLE match_settlements
    ADD COLUMN IF NOT EXISTS xp INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS mastery_xp INT NOT NULL DEFAULT 0;
SQL);

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS player_champions (
    player_id UUID NOT NULL,
    champion_id VARCHAR(32) NOT NULL,
    is_owned BOOLEAN NOT NULL DEFAULT FALSE,
    is_selected BOOLEAN NOT NULL DEFAULT FALSE,
    mastery_level INT NOT NULL DEFAULT 1,
    mastery_xp INT NOT NULL DEFAULT 0,
    unlocked_at TIMESTAMP NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    PRIMARY KEY (player_id, champion_id)
);
SQL);

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS champion_catalog (
    id VARCHAR(32) PRIMARY KEY,
    name VARCHAR(64) NOT NULL,
    role VARCHAR(32) NOT NULL,
    price_coins INT NOT NULL DEFAULT 0,
    starter_owned BOOLEAN NOT NULL DEFAULT FALSE,
    starter_selected BOOLEAN NOT NULL DEFAULT FALSE,
    sort_order INT NOT NULL DEFAULT 0
);
SQL);

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS store_catalog (
    id VARCHAR(64) PRIMARY KEY,
    name VARCHAR(128) NOT NULL,
    item_type VARCHAR(32) NOT NULL,
    price_coins INT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0
);
SQL);

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS mission_catalog (
    id VARCHAR(64) PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    target_value INT NOT NULL,
    reward_xp INT NOT NULL,
    reward_coins INT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0
);
SQL);

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS match_outcome_rules (
    queue_type VARCHAR(32) NOT NULL,
    outcome_key VARCHAR(16) NOT NULL,
    global_mmr_delta INT NOT NULL,
    champion_mmr_delta INT NOT NULL,
    coins INT NOT NULL,
    gems INT NOT NULL DEFAULT 0,
    xp INT NOT NULL,
    mastery_xp INT NOT NULL,
    PRIMARY KEY (queue_type, outcome_key)
);
SQL);

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS player_inventory (
    player_id UUID NOT NULL,
    item_id VARCHAR(64) NOT NULL,
    item_type VARCHAR(32) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    equipped BOOLEAN NOT NULL DEFAULT FALSE,
    acquired_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    PRIMARY KEY (player_id, item_id)
);
SQL);

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS player_daily_missions (
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
);
SQL);

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS player_daily_mission_champions (
    player_id UUID NOT NULL,
    mission_date DATE NOT NULL,
    champion_id VARCHAR(32) NOT NULL,
    PRIMARY KEY (player_id, mission_date, champion_id)
);
SQL);

$seedChampionCatalog = $pdo->prepare(
    'INSERT INTO champion_catalog (id, name, role, price_coins, starter_owned, starter_selected, sort_order)
     VALUES (:id, :name, :role, :price_coins, :starter_owned, :starter_selected, :sort_order)
     ON CONFLICT (id) DO UPDATE
     SET name = EXCLUDED.name,
         role = EXCLUDED.role,
         price_coins = EXCLUDED.price_coins,
         starter_owned = EXCLUDED.starter_owned,
         starter_selected = EXCLUDED.starter_selected,
         sort_order = EXCLUDED.sort_order'
);

foreach ([
    ['id' => 'assassin', 'name' => 'Sombra', 'role' => 'Ataque', 'price' => 0, 'owned' => true, 'selected' => true, 'order' => 1],
    ['id' => 'bruiser', 'name' => 'Titan', 'role' => 'Defensa', 'price' => 600, 'owned' => true, 'selected' => false, 'order' => 2],
    ['id' => 'control', 'name' => 'Guardian', 'role' => 'Control', 'price' => 800, 'owned' => false, 'selected' => false, 'order' => 3],
    ['id' => 'sustain', 'name' => 'Viento', 'role' => 'Sustain', 'price' => 1000, 'owned' => false, 'selected' => false, 'order' => 4],
] as $row) {
    $seedChampionCatalog->execute([
        ':id' => $row['id'],
        ':name' => $row['name'],
        ':role' => $row['role'],
        ':price_coins' => $row['price'],
        ':starter_owned' => $row['owned'] ? 'true' : 'false',
        ':starter_selected' => $row['selected'] ? 'true' : 'false',
        ':sort_order' => $row['order'],
    ]);
}

$seedOutcomeRules = $pdo->prepare(
    'INSERT INTO match_outcome_rules (queue_type, outcome_key, global_mmr_delta, champion_mmr_delta, coins, gems, xp, mastery_xp)
     VALUES (:queue_type, :outcome_key, :global_mmr_delta, :champion_mmr_delta, :coins, :gems, :xp, :mastery_xp)
     ON CONFLICT (queue_type, outcome_key) DO UPDATE
     SET global_mmr_delta = EXCLUDED.global_mmr_delta,
         champion_mmr_delta = EXCLUDED.champion_mmr_delta,
         coins = EXCLUDED.coins,
         gems = EXCLUDED.gems,
         xp = EXCLUDED.xp,
         mastery_xp = EXCLUDED.mastery_xp'
);
foreach ([
    ['queue' => 'ranked', 'outcome' => 'win', 'g' => 15, 'c' => 14, 'coins' => 140, 'gems' => 5, 'xp' => 130, 'mxp' => 60],
    ['queue' => 'ranked', 'outcome' => 'loss', 'g' => -11, 'c' => -10, 'coins' => 45, 'gems' => 0, 'xp' => 85, 'mxp' => 30],
    ['queue' => 'ranked', 'outcome' => 'draw', 'g' => 0, 'c' => 0, 'coins' => 70, 'gems' => 0, 'xp' => 90, 'mxp' => 35],
    ['queue' => 'bot', 'outcome' => 'win', 'g' => 11, 'c' => 9, 'coins' => 120, 'gems' => 0, 'xp' => 110, 'mxp' => 45],
    ['queue' => 'bot', 'outcome' => 'loss', 'g' => -8, 'c' => -6, 'coins' => 50, 'gems' => 0, 'xp' => 75, 'mxp' => 25],
    ['queue' => 'bot', 'outcome' => 'draw', 'g' => 0, 'c' => 0, 'coins' => 70, 'gems' => 0, 'xp' => 90, 'mxp' => 35],
] as $row) {
    $seedOutcomeRules->execute([
        ':queue_type' => $row['queue'],
        ':outcome_key' => $row['outcome'],
        ':global_mmr_delta' => $row['g'],
        ':champion_mmr_delta' => $row['c'],
        ':coins' => $row['coins'],
        ':gems' => $row['gems'],
        ':xp' => $row['xp'],
        ':mastery_xp' => $row['mxp'],
    ]);
}

$seedStoreCatalog = $pdo->prepare(
    'INSERT INTO store_catalog (id, name, item_type, price_coins, sort_order)
     VALUES (:id, :name, :item_type, :price_coins, :sort_order)
     ON CONFLICT (id) DO UPDATE
     SET name = EXCLUDED.name,
         item_type = EXCLUDED.item_type,
         price_coins = EXCLUDED.price_coins,
         sort_order = EXCLUDED.sort_order'
);
foreach ([
    ['id' => 'skin_dorada', 'name' => 'Skin Dorada', 'type' => 'skin', 'price' => 500, 'order' => 1],
    ['id' => 'efecto_victoria', 'name' => 'Efecto de Victoria', 'type' => 'effect', 'price' => 300, 'order' => 2],
    ['id' => 'avatar_legendario', 'name' => 'Avatar Legendario', 'type' => 'avatar', 'price' => 200, 'order' => 3],
    ['id' => 'skin_platino', 'name' => 'Skin Platino', 'type' => 'skin', 'price' => 800, 'order' => 4],
] as $row) {
    $seedStoreCatalog->execute([
        ':id' => $row['id'],
        ':name' => $row['name'],
        ':item_type' => $row['type'],
        ':price_coins' => $row['price'],
        ':sort_order' => $row['order'],
    ]);
}

$seedMissionCatalog = $pdo->prepare(
    'INSERT INTO mission_catalog (id, title, target_value, reward_xp, reward_coins, sort_order)
     VALUES (:id, :title, :target_value, :reward_xp, :reward_coins, :sort_order)
     ON CONFLICT (id) DO UPDATE
     SET title = EXCLUDED.title,
         target_value = EXCLUDED.target_value,
         reward_xp = EXCLUDED.reward_xp,
         reward_coins = EXCLUDED.reward_coins,
         sort_order = EXCLUDED.sort_order'
);
foreach ([
    ['id' => 'win_3_matches', 'title' => 'Gana 3 partidas', 'target' => 3, 'xp' => 100, 'coins' => 120, 'order' => 1],
    ['id' => 'use_defense_5', 'title' => 'Usa 5 acciones de defensa', 'target' => 5, 'xp' => 50, 'coins' => 80, 'order' => 2],
    ['id' => 'play_3_champions', 'title' => 'Juega con 3 campeones diferentes', 'target' => 3, 'xp' => 75, 'coins' => 100, 'order' => 3],
] as $row) {
    $seedMissionCatalog->execute([
        ':id' => $row['id'],
        ':title' => $row['title'],
        ':target_value' => $row['target'],
        ':reward_xp' => $row['xp'],
        ':reward_coins' => $row['coins'],
        ':sort_order' => $row['order'],
    ]);
}

$seedUsers = [
    [
        'id' => '11111111-1111-1111-1111-111111111111',
        'username' => 'playerone',
        'email' => 'playerone@trueduel.local',
        'password' => password_hash('123456', PASSWORD_BCRYPT),
        'display_name' => 'Player One',
        'rank' => 'Silver II',
        'mmr' => 1210,
        'coins' => 1600,
        'xp' => 560,
        'level' => 4,
    ],
    [
        'id' => '22222222-2222-2222-2222-222222222222',
        'username' => 'raven',
        'email' => 'raven@trueduel.local',
        'password' => password_hash('123456', PASSWORD_BCRYPT),
        'display_name' => 'Raven',
        'rank' => 'Gold I',
        'mmr' => 1450,
        'coins' => 2000,
        'xp' => 980,
        'level' => 6,
    ],
    [
        'id' => '33333333-3333-3333-3333-333333333333',
        'username' => 'nova',
        'email' => 'nova@trueduel.local',
        'password' => password_hash('123456', PASSWORD_BCRYPT),
        'display_name' => 'Nova',
        'rank' => 'Gold II',
        'mmr' => 1410,
        'coins' => 1800,
        'xp' => 860,
        'level' => 5,
    ],
];

$insertUser = $pdo->prepare(
    'INSERT INTO auth_users (id, username, email, password_hash)
     VALUES (:id, :username, :email, :password_hash)
     ON CONFLICT (username) DO NOTHING'
);

$insertProfile = $pdo->prepare(
    'INSERT INTO player_profiles (player_id, display_name, rank_label, mmr_global, region, coins, gems, experience_total, level)
     VALUES (:player_id, :display_name, :rank_label, :mmr_global, :region, :coins, 0, :experience_total, :level)
     ON CONFLICT (player_id) DO NOTHING'
);

foreach ($seedUsers as $seed) {
    $insertUser->execute([
        ':id' => $seed['id'],
        ':username' => $seed['username'],
        ':email' => $seed['email'],
        ':password_hash' => $seed['password'],
    ]);

    $insertProfile->execute([
        ':player_id' => $seed['id'],
        ':display_name' => $seed['display_name'],
        ':rank_label' => $seed['rank'],
        ':mmr_global' => $seed['mmr'],
        ':region' => 'eu-west',
        ':coins' => $seed['coins'],
        ':experience_total' => $seed['xp'],
        ':level' => $seed['level'],
    ]);
}

$playerIds = $pdo->query('SELECT player_id FROM player_profiles')->fetchAll(PDO::FETCH_COLUMN);
if (is_array($playerIds)) {
    $championRows = $pdo->query(
        'SELECT id, starter_owned, starter_selected
         FROM champion_catalog
         ORDER BY sort_order ASC, id ASC'
    )->fetchAll();

    $insertChampion = $pdo->prepare(
        'INSERT INTO player_champions (player_id, champion_id, is_owned, is_selected, mastery_level, mastery_xp, unlocked_at, updated_at)
         VALUES (:player_id, :champion_id, :is_owned, :is_selected, 1, 0, :unlocked_at, NOW())
         ON CONFLICT (player_id, champion_id) DO NOTHING'
    );

    foreach ($playerIds as $pid) {
        if (!is_string($pid) || $pid === '') {
            continue;
        }

        foreach ($championRows as $championRow) {
            if (!is_array($championRow)) {
                continue;
            }
            $championId = (string) ($championRow['id'] ?? '');
            if ($championId === '') {
                continue;
            }
            $starterOwned = in_array(strtolower((string) ($championRow['starter_owned'] ?? 'false')), ['1', 't', 'true', 'y', 'yes'], true);
            $starterSelected = in_array(strtolower((string) ($championRow['starter_selected'] ?? 'false')), ['1', 't', 'true', 'y', 'yes'], true);
            $insertChampion->execute([
                ':player_id' => $pid,
                ':champion_id' => $championId,
                ':is_owned' => $starterOwned ? 'true' : 'false',
                ':is_selected' => $starterSelected ? 'true' : 'false',
                ':unlocked_at' => $starterOwned ? gmdate('Y-m-d H:i:s') : null,
            ]);
        }
    }

    $clearSelection = $pdo->prepare('UPDATE player_champions SET is_selected = FALSE WHERE player_id = :player_id');
    $setSelection = $pdo->prepare(
        'UPDATE player_champions
         SET is_selected = TRUE
         WHERE player_id = :player_id AND champion_id = (
             SELECT champion_id
             FROM player_champions
             WHERE player_id = :player_id AND is_owned = TRUE
             ORDER BY champion_id
             LIMIT 1
         )'
    );
    foreach ($playerIds as $pid) {
        if (!is_string($pid) || $pid === '') {
            continue;
        }
        $clearSelection->execute([':player_id' => $pid]);
        $setSelection->execute([':player_id' => $pid]);
    }
}

fwrite(STDOUT, "[bootstrap_db] Schema ready and base seed users ensured.\n");
