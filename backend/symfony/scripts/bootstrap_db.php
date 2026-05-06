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
    puntos_habilidad INT NOT NULL DEFAULT 1000,
    titulo_competitivo VARCHAR(64) NOT NULL DEFAULT 'Combatiente',
    posicion_competitiva INT NULL,
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
    ADD COLUMN IF NOT EXISTS puntos_habilidad INT NOT NULL DEFAULT 1000,
    ADD COLUMN IF NOT EXISTS titulo_competitivo VARCHAR(64) NOT NULL DEFAULT 'Combatiente',
    ADD COLUMN IF NOT EXISTS posicion_competitiva INT NULL,
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
    mode VARCHAR(32) NOT NULL DEFAULT 'normal_bot',
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
ALTER TABLE matchmaking_tickets
    ADD COLUMN IF NOT EXISTS mode VARCHAR(32) NOT NULL DEFAULT 'normal_bot';
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

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS personajes (
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
);
SQL);

$pdo->exec("ALTER TABLE personajes ADD COLUMN IF NOT EXISTS precio_monedas INT NOT NULL DEFAULT 0");

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS jugador_personajes (
    jugador_id UUID NOT NULL,
    personaje_id VARCHAR(32) NOT NULL,
    desbloqueado BOOLEAN NOT NULL DEFAULT FALSE,
    nivel_maestria INT NOT NULL DEFAULT 1,
    xp_maestria INT NOT NULL DEFAULT 0,
    desbloqueado_en TIMESTAMP NULL,
    actualizado_en TIMESTAMP NOT NULL DEFAULT NOW(),
    PRIMARY KEY (jugador_id, personaje_id)
);
SQL);

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS equipos_jugador (
    jugador_id UUID NOT NULL,
    slot SMALLINT NOT NULL CHECK (slot BETWEEN 1 AND 3),
    personaje_id VARCHAR(32) NOT NULL,
    actualizado_en TIMESTAMP NOT NULL DEFAULT NOW(),
    PRIMARY KEY (jugador_id, slot),
    UNIQUE (jugador_id, personaje_id)
);
SQL);

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS bonificadores_partida (
    id VARCHAR(64) PRIMARY KEY,
    nombre VARCHAR(96) NOT NULL,
    categoria_volatilidad VARCHAR(16) NOT NULL,
    descripcion TEXT NOT NULL,
    reglas_json JSONB NOT NULL DEFAULT '{}'::jsonb,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    orden INT NOT NULL DEFAULT 0
);
SQL);

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS titulos_competitivos (
    id VARCHAR(64) PRIMARY KEY,
    nombre VARCHAR(96) NOT NULL,
    cupo INT NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    activo BOOLEAN NOT NULL DEFAULT TRUE
);
SQL);

$seedPersonajes = $pdo->prepare(
    'INSERT INTO personajes (id, nombre, rol_sinergia, descripcion, habilidad_especial_nombre, habilidad_especial_descripcion, efecto_especial_json, coste_cargas, desbloqueado_inicial, precio_monedas, activo, orden)
     VALUES (:id, :nombre, :rol_sinergia, :descripcion, :habilidad_especial_nombre, :habilidad_especial_descripcion, CAST(:efecto_especial_json AS jsonb), :coste_cargas, :desbloqueado_inicial, :precio_monedas, TRUE, :orden)
     ON CONFLICT (id) DO UPDATE
     SET nombre = EXCLUDED.nombre,
         rol_sinergia = EXCLUDED.rol_sinergia,
         descripcion = EXCLUDED.descripcion,
         habilidad_especial_nombre = EXCLUDED.habilidad_especial_nombre,
         habilidad_especial_descripcion = EXCLUDED.habilidad_especial_descripcion,
         efecto_especial_json = EXCLUDED.efecto_especial_json,
         coste_cargas = EXCLUDED.coste_cargas,
         desbloqueado_inicial = EXCLUDED.desbloqueado_inicial,
         precio_monedas = EXCLUDED.precio_monedas,
         activo = TRUE,
         orden = EXCLUDED.orden'
);

foreach ([
    ['id' => 'vanguard', 'nombre' => 'Vanguard', 'rol' => 'iniciador', 'descripcion' => 'Abre ventanas tácticas aplicando presión inicial.', 'habilidad' => 'Golpe balístico', 'detalle' => 'Daño medio y aplica Expuesto durante 1 turno.', 'efecto' => ['tipo' => 'aplicar_estado', 'estado' => 'expuesto', 'duracion_turnos' => 1, 'dano' => 'medio'], 'inicial' => true, 'precio' => 0, 'orden' => 1],
    ['id' => 'bulwark', 'nombre' => 'Bulwark', 'rol' => 'amplificador', 'descripcion' => 'Estabiliza la composición con mitigación y economía defensiva.', 'habilidad' => 'Muralla cinética', 'detalle' => 'Mitigación global 1 turno y mejora ganancia de carga al bloquear.', 'efecto' => ['tipo' => 'mitigacion_global', 'duracion_turnos' => 1, 'mejora_carga_bloqueo' => true], 'inicial' => true, 'precio' => 0, 'orden' => 2],
    ['id' => 'riftblade', 'nombre' => 'Riftblade', 'rol' => 'finalizador', 'descripcion' => 'Convierte estados de vulnerabilidad en daño explosivo.', 'habilidad' => 'Corte de fase', 'detalle' => 'Daño alto; daño extra si el objetivo está Expuesto.', 'efecto' => ['tipo' => 'dano_condicional', 'dano' => 'alto', 'estado_objetivo' => 'expuesto', 'bonus' => 'extra'], 'inicial' => true, 'precio' => 0, 'orden' => 3],
    ['id' => 'hexa', 'nombre' => 'Hexa', 'rol' => 'iniciador', 'descripcion' => 'Disrumpe defensas y castiga especiales rivales.', 'habilidad' => 'Marca de entropía', 'detalle' => 'Reduce bloqueo rival y aumenta fallo de especial rival.', 'efecto' => ['tipo' => 'debuff_defensivo', 'reduce_bloqueo' => true, 'aumenta_fallo_especial' => true], 'inicial' => false, 'precio' => 600, 'orden' => 4],
    ['id' => 'oracle', 'nombre' => 'Oracle', 'rol' => 'amplificador', 'descripcion' => 'Acelera ciclos de especiales mediante precognición.', 'habilidad' => 'Precognición', 'detalle' => 'El próximo especial aliado cuesta 1 carga menos.', 'efecto' => ['tipo' => 'reducir_coste_especial', 'reduccion_cargas' => 1, 'usos' => 1], 'inicial' => false, 'precio' => 800, 'orden' => 5],
    ['id' => 'revenant', 'nombre' => 'Revenant', 'rol' => 'finalizador', 'descripcion' => 'Cierra combates recuperando vida mediante daño infligido.', 'habilidad' => 'Deuda de sangre', 'detalle' => 'Daño y cura proporcional al daño infligido.', 'efecto' => ['tipo' => 'dano_y_curacion', 'curacion_proporcional' => true], 'inicial' => false, 'precio' => 1000, 'orden' => 6],
    ['id' => 'warden', 'nombre' => 'Warden', 'rol' => 'amplificador', 'descripcion' => 'Controla picos de daño anulando bonus ofensivos.', 'habilidad' => 'Interceptar', 'detalle' => 'Anula el próximo bonus ofensivo rival.', 'efecto' => ['tipo' => 'anular_bonus_ofensivo', 'usos' => 1], 'inicial' => false, 'precio' => 1200, 'orden' => 7],
    ['id' => 'spark', 'nombre' => 'Spark', 'rol' => 'amplificador', 'descripcion' => 'Gana tempo con turnos extra controlados.', 'habilidad' => 'Sobrecarga', 'detalle' => 'Turno extra con daño reducido.', 'efecto' => ['tipo' => 'turno_extra', 'dano_reducido' => true, 'max_encadenado' => 1], 'inicial' => false, 'precio' => 1200, 'orden' => 8],
    ['id' => 'mender', 'nombre' => 'Mender', 'rol' => 'amplificador', 'descripcion' => 'Aporta consistencia limpiando presión rival.', 'habilidad' => 'Reforja', 'detalle' => 'Cura y limpia un debuff relevante.', 'efecto' => ['tipo' => 'curar_y_limpiar', 'limpia_debuff' => true], 'inicial' => false, 'precio' => 1400, 'orden' => 9],
    ['id' => 'grim', 'nombre' => 'Grim', 'rol' => 'finalizador', 'descripcion' => 'Castiga ciclos de alto gasto con ejecución matemática.', 'habilidad' => 'Veredicto', 'detalle' => 'Daño escalado por cargas gastadas en el ciclo.', 'efecto' => ['tipo' => 'dano_por_cargas_gastadas'], 'inicial' => false, 'precio' => 1400, 'orden' => 10],
    ['id' => 'tracer', 'nombre' => 'Tracer', 'rol' => 'amplificador', 'descripcion' => 'Multiplica utilidades aliadas mediante ecos tácticos.', 'habilidad' => 'Eco táctico', 'detalle' => 'Repite el último efecto no dañino aliado al 70%.', 'efecto' => ['tipo' => 'repetir_efecto_no_danino', 'potencia' => 0.7], 'inicial' => false, 'precio' => 1600, 'orden' => 11],
    ['id' => 'null', 'nombre' => 'Null', 'rol' => 'iniciador', 'descripcion' => 'Neutraliza contextos de alta varianza.', 'habilidad' => 'Zona muerta', 'detalle' => '1 turno sin críticos ni turnos extra para ambos.', 'efecto' => ['tipo' => 'bloquear_rng', 'sin_criticos' => true, 'sin_turnos_extra' => true, 'duracion_turnos' => 1], 'inicial' => false, 'precio' => 1800, 'orden' => 12],
] as $row) {
    $seedPersonajes->execute([
        ':id' => $row['id'],
        ':nombre' => $row['nombre'],
        ':rol_sinergia' => $row['rol'],
        ':descripcion' => $row['descripcion'],
        ':habilidad_especial_nombre' => $row['habilidad'],
        ':habilidad_especial_descripcion' => $row['detalle'],
        ':efecto_especial_json' => json_encode($row['efecto'], JSON_THROW_ON_ERROR),
        ':coste_cargas' => 2,
        ':desbloqueado_inicial' => $row['inicial'] ? 'true' : 'false',
        ':precio_monedas' => $row['precio'],
        ':orden' => $row['orden'],
    ]);
}

$seedBonificadores = $pdo->prepare(
    'INSERT INTO bonificadores_partida (id, nombre, categoria_volatilidad, descripcion, reglas_json, activo, orden)
     VALUES (:id, :nombre, :categoria_volatilidad, :descripcion, CAST(:reglas_json AS jsonb), TRUE, :orden)
     ON CONFLICT (id) DO UPDATE
     SET nombre = EXCLUDED.nombre,
         categoria_volatilidad = EXCLUDED.categoria_volatilidad,
         descripcion = EXCLUDED.descripcion,
         reglas_json = EXCLUDED.reglas_json,
         activo = TRUE,
         orden = EXCLUDED.orden'
);

foreach ([
    ['id' => 'defensa_reforzada', 'nombre' => 'Defensa reforzada', 'categoria' => 'baja', 'descripcion' => 'Daño final global x0.85.', 'reglas' => ['multiplicador_dano_global' => 0.85], 'orden' => 1],
    ['id' => 'fatiga_suave', 'nombre' => 'Fatiga suave', 'categoria' => 'baja', 'descripcion' => 'Desde turno 4: +5% daño global acumulativo por turno.', 'reglas' => ['desde_turno' => 4, 'dano_global_acumulativo' => 0.05], 'orden' => 2],
    ['id' => 'pulso_estable', 'nombre' => 'Pulso estable', 'categoria' => 'baja', 'descripcion' => '-5 pp crítico global.', 'reglas' => ['critico_pp' => -5], 'orden' => 3],
    ['id' => 'cadencia_tactica', 'nombre' => 'Cadencia táctica', 'categoria' => 'baja', 'descripcion' => '-50% relativo a probabilidad de repetición de acción.', 'reglas' => ['repeticion_accion_relativa' => -0.5], 'orden' => 4],
    ['id' => 'ritmo_acelerado', 'nombre' => 'Ritmo acelerado', 'categoria' => 'media', 'descripcion' => '20% de doble turno, máximo 1 extra.', 'reglas' => ['doble_turno_probabilidad' => 0.2, 'max_extra' => 1], 'orden' => 5],
    ['id' => 'eco_accion_moderado', 'nombre' => 'Eco de acción moderado', 'categoria' => 'media', 'descripcion' => '18% de repetir habilidad al 70% de potencia.', 'reglas' => ['eco_probabilidad' => 0.18, 'potencia' => 0.7], 'orden' => 6],
    ['id' => 'critico_inestable', 'nombre' => 'Crítico inestable', 'categoria' => 'media', 'descripcion' => '+10 pp crítico y +5 pp fallo de habilidades.', 'reglas' => ['critico_pp' => 10, 'fallo_habilidad_pp' => 5], 'orden' => 7],
    ['id' => 'escudo_intermitente', 'nombre' => 'Escudo intermitente', 'categoria' => 'media', 'descripcion' => '25% de reducir 40% del próximo daño recibido ese turno.', 'reglas' => ['escudo_probabilidad' => 0.25, 'reduccion_dano' => 0.4], 'orden' => 8],
    ['id' => 'alta_volatilidad', 'nombre' => 'Alta volatilidad', 'categoria' => 'alta', 'descripcion' => '+15 pp crítico, +10 pp fallo, crítico x1.7.', 'reglas' => ['critico_pp' => 15, 'fallo_habilidad_pp' => 10, 'multiplicador_critico' => 1.7], 'orden' => 9],
    ['id' => 'doble_filo', 'nombre' => 'Doble filo', 'categoria' => 'alta', 'descripcion' => '+20% daño de acciones; si falla habilidad, auto-daño 8% vida máxima.', 'reglas' => ['dano_acciones' => 0.2, 'autodano_fallo_vida_maxima' => 0.08], 'orden' => 10],
] as $row) {
    $seedBonificadores->execute([
        ':id' => $row['id'],
        ':nombre' => $row['nombre'],
        ':categoria_volatilidad' => $row['categoria'],
        ':descripcion' => $row['descripcion'],
        ':reglas_json' => json_encode($row['reglas'], JSON_THROW_ON_ERROR),
        ':orden' => $row['orden'],
    ]);
}

$seedTitulos = $pdo->prepare(
    'INSERT INTO titulos_competitivos (id, nombre, cupo, orden, activo)
     VALUES (:id, :nombre, :cupo, :orden, TRUE)
     ON CONFLICT (id) DO UPDATE
     SET nombre = EXCLUDED.nombre,
         cupo = EXCLUDED.cupo,
         orden = EXCLUDED.orden,
         activo = TRUE'
);
foreach ([
    ['id' => 'sp_leyenda_unica', 'nombre' => 'Leyenda Única', 'cupo' => 1, 'orden' => 1],
    ['id' => 'sp_gran_maestro', 'nombre' => 'Gran Maestro', 'cupo' => 5, 'orden' => 2],
    ['id' => 'sp_maestro', 'nombre' => 'Maestro', 'cupo' => 25, 'orden' => 3],
    ['id' => 'sp_diamante', 'nombre' => 'Diamante', 'cupo' => 75, 'orden' => 4],
    ['id' => 'sp_platino', 'nombre' => 'Platino', 'cupo' => 150, 'orden' => 5],
    ['id' => 'sp_oro', 'nombre' => 'Oro', 'cupo' => 300, 'orden' => 6],
] as $row) {
    $seedTitulos->execute([
        ':id' => $row['id'],
        ':nombre' => $row['nombre'],
        ':cupo' => $row['cupo'],
        ':orden' => $row['orden'],
    ]);
}

$seedTitulos = $pdo->prepare(
    'INSERT INTO titulos_competitivos (id, nombre, cupo, orden, activo)
     VALUES (:id, :nombre, :cupo, :orden, TRUE)
     ON CONFLICT (id) DO UPDATE
     SET nombre = EXCLUDED.nombre,
         cupo = EXCLUDED.cupo,
         orden = EXCLUDED.orden,
         activo = TRUE'
);
foreach ([
    ['id' => 'sp_leyenda_unica', 'nombre' => 'Leyenda Única', 'cupo' => 1, 'orden' => 1],
    ['id' => 'sp_gran_maestro', 'nombre' => 'Gran Maestro', 'cupo' => 5, 'orden' => 2],
    ['id' => 'sp_maestro', 'nombre' => 'Maestro', 'cupo' => 25, 'orden' => 3],
    ['id' => 'sp_diamante', 'nombre' => 'Diamante', 'cupo' => 75, 'orden' => 4],
    ['id' => 'sp_platino', 'nombre' => 'Platino', 'cupo' => 150, 'orden' => 5],
    ['id' => 'sp_oro', 'nombre' => 'Oro', 'cupo' => 300, 'orden' => 6],
] as $row) {
    $seedTitulos->execute([
        ':id' => $row['id'],
        ':nombre' => $row['nombre'],
        ':cupo' => $row['cupo'],
        ':orden' => $row['orden'],
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
        'sp' => 1210,
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
        'sp' => 1450,
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
        'sp' => 1410,
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
    'INSERT INTO player_profiles (player_id, display_name, rank_label, mmr_global, region, puntos_habilidad, titulo_competitivo, coins, gems, experience_total, level)
     VALUES (:player_id, :display_name, :rank_label, :mmr_global, :region, :puntos_habilidad, :titulo_competitivo, :coins, 0, :experience_total, :level)
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
        ':puntos_habilidad' => $seed['sp'],
        ':titulo_competitivo' => 'Combatiente',
        ':coins' => $seed['coins'],
        ':experience_total' => $seed['xp'],
        ':level' => $seed['level'],
    ]);
}

$playerIds = $pdo->query('SELECT player_id FROM player_profiles')->fetchAll(PDO::FETCH_COLUMN);
if (is_array($playerIds)) {
    $personajesIniciales = $pdo->query(
        'SELECT id, desbloqueado_inicial
         FROM personajes
         ORDER BY orden ASC, id ASC'
    )->fetchAll();

    $insertarJugadorPersonaje = $pdo->prepare(
        'INSERT INTO jugador_personajes (jugador_id, personaje_id, desbloqueado, nivel_maestria, xp_maestria, desbloqueado_en, actualizado_en)
         VALUES (:jugador_id, :personaje_id, :desbloqueado, 1, 0, :desbloqueado_en, NOW())
         ON CONFLICT (jugador_id, personaje_id) DO NOTHING'
    );

    $guardarSlotEquipo = $pdo->prepare(
        'INSERT INTO equipos_jugador (jugador_id, slot, personaje_id, actualizado_en)
         VALUES (:jugador_id, :slot, :personaje_id, NOW())
         ON CONFLICT (jugador_id, slot) DO UPDATE
         SET personaje_id = EXCLUDED.personaje_id,
             actualizado_en = NOW()'
    );

    foreach ($playerIds as $pid) {
        if (!is_string($pid) || $pid === '') {
            continue;
        }

        $equipoInicial = [];
        foreach ($personajesIniciales as $personaje) {
            if (!is_array($personaje)) {
                continue;
            }
            $personajeId = (string) ($personaje['id'] ?? '');
            $desbloqueado = in_array(strtolower((string) ($personaje['desbloqueado_inicial'] ?? 'false')), ['1', 't', 'true', 'y', 'yes'], true);
            $insertarJugadorPersonaje->execute([
                ':jugador_id' => $pid,
                ':personaje_id' => $personajeId,
                ':desbloqueado' => $desbloqueado ? 'true' : 'false',
                ':desbloqueado_en' => $desbloqueado ? gmdate('Y-m-d H:i:s') : null,
            ]);
            if ($desbloqueado && count($equipoInicial) < 3) {
                $equipoInicial[] = $personajeId;
            }
        }

        foreach ($equipoInicial as $indice => $personajeId) {
            $guardarSlotEquipo->execute([
                ':jugador_id' => $pid,
                ':slot' => $indice + 1,
                ':personaje_id' => $personajeId,
            ]);
        }
    }
}

$rankingSp = $pdo->query('SELECT player_id FROM player_profiles ORDER BY puntos_habilidad DESC, mmr_global DESC, updated_at ASC')->fetchAll(PDO::FETCH_COLUMN);
$titulosSp = $pdo->query('SELECT nombre, cupo FROM titulos_competitivos WHERE activo = TRUE ORDER BY orden ASC')->fetchAll();
if (is_array($rankingSp) && is_array($titulosSp)) {
    $actualizarTitulo = $pdo->prepare(
        'UPDATE player_profiles
         SET titulo_competitivo = :titulo,
             posicion_competitiva = :posicion,
             updated_at = NOW()
         WHERE player_id = :player_id'
    );

    $posicion = 1;
    foreach ($rankingSp as $playerId) {
        if (!is_string($playerId) || $playerId === '') {
            continue;
        }
        $tituloActual = 'Combatiente';
        $acumulado = 0;
        foreach ($titulosSp as $titulo) {
            if (!is_array($titulo)) {
                continue;
            }
            $acumulado += max(0, (int) ($titulo['cupo'] ?? 0));
            if ($acumulado > 0 && $posicion <= $acumulado) {
                $tituloActual = (string) ($titulo['nombre'] ?? 'Combatiente');
                break;
            }
        }

        $actualizarTitulo->execute([
            ':titulo' => $tituloActual,
            ':posicion' => $posicion,
            ':player_id' => $playerId,
        ]);
        $posicion++;
    }
}

fwrite(STDOUT, "[bootstrap_db] Schema ready and base seed users ensured.\n");
