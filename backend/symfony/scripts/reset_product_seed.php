<?php

declare(strict_types=1);

$dsn = getenv('DATABASE_URL') ?: 'pgsql:host=db;port=5432;dbname=true_duel';
$user = getenv('DATABASE_USER') ?: 'true_duel';
$password = getenv('DATABASE_PASSWORD') ?: 'true_duel';

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "[reset_product_seed] DB connection failed: {$e->getMessage()}\n");
    exit(1);
}

function tableExists(PDO $pdo, string $table): bool
{
    $statement = $pdo->prepare("SELECT to_regclass(:table) IS NOT NULL AS ok");
    $statement->execute([':table' => 'public.' . $table]);
    $row = $statement->fetch();
    if (!is_array($row)) {
        return false;
    }
    $value = strtolower((string) ($row['ok'] ?? ''));
    return in_array($value, ['1', 't', 'true', 'y', 'yes'], true);
}

function truncateIfExists(PDO $pdo, string $table): void
{
    if (!tableExists($pdo, $table)) {
        return;
    }
    $pdo->exec('TRUNCATE TABLE ' . $table);
}

function upsertCatalogs(PDO $pdo): void
{
    $championCatalog = $pdo->prepare(
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
        $championCatalog->execute([
            ':id' => $row['id'],
            ':name' => $row['name'],
            ':role' => $row['role'],
            ':price_coins' => $row['price'],
            ':starter_owned' => $row['owned'] ? 'true' : 'false',
            ':starter_selected' => $row['selected'] ? 'true' : 'false',
            ':sort_order' => $row['order'],
        ]);
    }

    $storeCatalog = $pdo->prepare(
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
        $storeCatalog->execute([
            ':id' => $row['id'],
            ':name' => $row['name'],
            ':item_type' => $row['type'],
            ':price_coins' => $row['price'],
            ':sort_order' => $row['order'],
        ]);
    }

    $missionCatalog = $pdo->prepare(
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
        $missionCatalog->execute([
            ':id' => $row['id'],
            ':title' => $row['title'],
            ':target_value' => $row['target'],
            ':reward_xp' => $row['xp'],
            ':reward_coins' => $row['coins'],
            ':sort_order' => $row['order'],
        ]);
    }

    $outcomeRules = $pdo->prepare(
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
        $outcomeRules->execute([
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
}

function seedUsersAndProfiles(PDO $pdo): array
{
    $seedUsers = [
        ['id' => '11111111-1111-1111-1111-111111111111', 'username' => 'playerone', 'email' => 'playerone@trueduel.local', 'displayName' => 'Player One', 'rank' => 'Silver II', 'mmr' => 1210, 'coins' => 1600, 'gems' => 25, 'xp' => 560, 'level' => 4],
        ['id' => '22222222-2222-2222-2222-222222222222', 'username' => 'raven', 'email' => 'raven@trueduel.local', 'displayName' => 'Raven', 'rank' => 'Gold I', 'mmr' => 1450, 'coins' => 2000, 'gems' => 55, 'xp' => 980, 'level' => 6],
        ['id' => '33333333-3333-3333-3333-333333333333', 'username' => 'nova', 'email' => 'nova@trueduel.local', 'displayName' => 'Nova', 'rank' => 'Gold II', 'mmr' => 1410, 'coins' => 1800, 'gems' => 40, 'xp' => 860, 'level' => 5],
        ['id' => '44444444-4444-4444-4444-444444444444', 'username' => 'ember', 'email' => 'ember@trueduel.local', 'displayName' => 'Ember', 'rank' => 'Silver I', 'mmr' => 1320, 'coins' => 900, 'gems' => 10, 'xp' => 410, 'level' => 3],
        ['id' => '55555555-5555-5555-5555-555555555555', 'username' => 'atlas', 'email' => 'atlas@trueduel.local', 'displayName' => 'Atlas', 'rank' => 'Bronce I', 'mmr' => 1100, 'coins' => 750, 'gems' => 5, 'xp' => 180, 'level' => 2],
        ['id' => '66666666-6666-6666-6666-666666666666', 'username' => 'luna', 'email' => 'luna@trueduel.local', 'displayName' => 'Luna', 'rank' => 'Plata III', 'mmr' => 1260, 'coins' => 1200, 'gems' => 18, 'xp' => 630, 'level' => 4],
    ];

    $insertUser = $pdo->prepare(
        'INSERT INTO auth_users (id, username, email, password_hash, created_at)
         VALUES (:id, :username, :email, :password_hash, NOW())'
    );
    $insertProfile = $pdo->prepare(
        'INSERT INTO player_profiles (player_id, display_name, rank_label, mmr_global, region, coins, gems, experience_total, level, total_matches, wins, losses, updated_at)
         VALUES (:player_id, :display_name, :rank_label, :mmr_global, :region, :coins, :gems, :experience_total, :level, 0, 0, 0, NOW())'
    );

    foreach ($seedUsers as $seed) {
        $insertUser->execute([
            ':id' => $seed['id'],
            ':username' => $seed['username'],
            ':email' => $seed['email'],
            ':password_hash' => password_hash('123456', PASSWORD_BCRYPT),
        ]);
        $insertProfile->execute([
            ':player_id' => $seed['id'],
            ':display_name' => $seed['displayName'],
            ':rank_label' => $seed['rank'],
            ':mmr_global' => $seed['mmr'],
            ':region' => 'eu-west',
            ':coins' => $seed['coins'],
            ':gems' => $seed['gems'],
            ':experience_total' => $seed['xp'],
            ':level' => $seed['level'],
        ]);
    }

    return $seedUsers;
}

function seedPlayerChampions(PDO $pdo): void
{
    $champions = $pdo->query('SELECT id, starter_owned, starter_selected FROM champion_catalog ORDER BY sort_order ASC, id ASC')->fetchAll();
    $players = $pdo->query('SELECT player_id FROM player_profiles')->fetchAll(PDO::FETCH_COLUMN);
    if (!is_array($champions) || !is_array($players)) {
        return;
    }

    $insert = $pdo->prepare(
        'INSERT INTO player_champions (player_id, champion_id, is_owned, is_selected, mastery_level, mastery_xp, unlocked_at, updated_at)
         VALUES (:player_id, :champion_id, :is_owned, :is_selected, 1, 0, :unlocked_at, NOW())'
    );

    foreach ($players as $playerId) {
        if (!is_string($playerId) || $playerId === '') {
            continue;
        }
        foreach ($champions as $champion) {
            if (!is_array($champion)) {
                continue;
            }
            $owned = in_array(strtolower((string) ($champion['starter_owned'] ?? 'false')), ['1', 't', 'true', 'yes', 'y'], true);
            $selected = in_array(strtolower((string) ($champion['starter_selected'] ?? 'false')), ['1', 't', 'true', 'yes', 'y'], true);
            $insert->execute([
                ':player_id' => $playerId,
                ':champion_id' => (string) $champion['id'],
                ':is_owned' => $owned ? 'true' : 'false',
                ':is_selected' => $selected ? 'true' : 'false',
                ':unlocked_at' => $owned ? gmdate('Y-m-d H:i:s') : null,
            ]);
        }
    }

    $unlockExtras = $pdo->prepare(
        'UPDATE player_champions
         SET is_owned = TRUE, unlocked_at = COALESCE(unlocked_at, NOW()), updated_at = NOW()
         WHERE player_id = :player_id AND champion_id = :champion_id'
    );
    $unlockExtras->execute([':player_id' => '11111111-1111-1111-1111-111111111111', ':champion_id' => 'control']);
    $unlockExtras->execute([':player_id' => '22222222-2222-2222-2222-222222222222', ':champion_id' => 'sustain']);

    $clear = $pdo->prepare('UPDATE player_champions SET is_selected = FALSE WHERE player_id = :player_id');
    $set = $pdo->prepare('UPDATE player_champions SET is_selected = TRUE WHERE player_id = :player_id AND champion_id = :champion_id');
    $clear->execute([':player_id' => '11111111-1111-1111-1111-111111111111']);
    $set->execute([':player_id' => '11111111-1111-1111-1111-111111111111', ':champion_id' => 'control']);
}

function seedInventory(PDO $pdo): void
{
    $insert = $pdo->prepare(
        'INSERT INTO player_inventory (player_id, item_id, item_type, quantity, equipped, acquired_at, updated_at)
         VALUES (:player_id, :item_id, :item_type, :quantity, :equipped, NOW(), NOW())'
    );

    foreach ([
        ['player' => '11111111-1111-1111-1111-111111111111', 'item' => 'skin_dorada', 'type' => 'skin', 'quantity' => 1, 'equipped' => 'true'],
        ['player' => '11111111-1111-1111-1111-111111111111', 'item' => 'avatar_legendario', 'type' => 'avatar', 'quantity' => 1, 'equipped' => 'true'],
        ['player' => '22222222-2222-2222-2222-222222222222', 'item' => 'efecto_victoria', 'type' => 'effect', 'quantity' => 1, 'equipped' => 'true'],
    ] as $row) {
        $insert->execute([
            ':player_id' => $row['player'],
            ':item_id' => $row['item'],
            ':item_type' => $row['type'],
            ':quantity' => $row['quantity'],
            ':equipped' => $row['equipped'],
        ]);
    }
}

function seedDailyMissions(PDO $pdo): void
{
    $today = gmdate('Y-m-d');
    $missions = $pdo->query('SELECT id, target_value, reward_xp, reward_coins FROM mission_catalog ORDER BY sort_order ASC')->fetchAll();
    $players = $pdo->query('SELECT player_id FROM player_profiles')->fetchAll(PDO::FETCH_COLUMN);
    if (!is_array($missions) || !is_array($players)) {
        return;
    }

    $insertMission = $pdo->prepare(
        'INSERT INTO player_daily_missions (player_id, mission_date, mission_id, target_value, progress_value, reward_xp, reward_coins, claimed, updated_at)
         VALUES (:player_id, :mission_date, :mission_id, :target_value, :progress_value, :reward_xp, :reward_coins, :claimed, NOW())'
    );
    $insertChampionMarker = $pdo->prepare(
        'INSERT INTO player_daily_mission_champions (player_id, mission_date, champion_id)
         VALUES (:player_id, :mission_date, :champion_id)'
    );

    foreach ($players as $playerId) {
        if (!is_string($playerId) || $playerId === '') {
            continue;
        }
        foreach ($missions as $mission) {
            if (!is_array($mission)) {
                continue;
            }
            $missionId = (string) ($mission['id'] ?? '');
            $progress = 0;
            $claimed = 'false';
            if ($playerId === '11111111-1111-1111-1111-111111111111') {
                if ($missionId === 'use_defense_5') {
                    $progress = (int) ($mission['target_value'] ?? 5);
                    $claimed = 'true';
                } elseif ($missionId === 'win_3_matches') {
                    $progress = 2;
                } elseif ($missionId === 'play_3_champions') {
                    $progress = 1;
                }
            }

            $insertMission->execute([
                ':player_id' => $playerId,
                ':mission_date' => $today,
                ':mission_id' => $missionId,
                ':target_value' => (int) ($mission['target_value'] ?? 0),
                ':progress_value' => $progress,
                ':reward_xp' => (int) ($mission['reward_xp'] ?? 0),
                ':reward_coins' => (int) ($mission['reward_coins'] ?? 0),
                ':claimed' => $claimed,
            ]);
        }

        if ($playerId === '11111111-1111-1111-1111-111111111111') {
            $insertChampionMarker->execute([':player_id' => $playerId, ':mission_date' => $today, ':champion_id' => 'assassin']);
        }
    }
}

try {
    $pdo->beginTransaction();

    upsertCatalogs($pdo);

    foreach ([
        'match_settlements',
        'turns',
        'matchmaking_tickets',
        'matches',
        'match_history',
        'player_daily_mission_champions',
        'player_daily_missions',
        'player_inventory',
        'player_champions',
        'player_profiles',
        'auth_users',
    ] as $table) {
        truncateIfExists($pdo, $table);
    }

    seedUsersAndProfiles($pdo);
    seedPlayerChampions($pdo);
    seedInventory($pdo);
    seedDailyMissions($pdo);

    $pdo->commit();
    fwrite(STDOUT, "[reset_product_seed] Product baseline reseeded.\n");
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "[reset_product_seed] Failed: {$e->getMessage()}\n");
    exit(1);
}
