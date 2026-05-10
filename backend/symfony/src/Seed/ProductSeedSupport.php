<?php

declare(strict_types=1);

namespace App\Seed;

use App\Service\CompetitiveTitleService;
use Doctrine\DBAL\Connection;
use Throwable;

final class ProductSeedSupport
{
    public function __construct(
        private readonly Connection $connection,
        private readonly CompetitiveTitleService $competitiveTitleService,
    ) {
    }

    public function isFreshInstall(): bool
    {
        if (!$this->tableExists('auth_users')) {
            return true;
        }

        return (int) $this->connection->fetchOne('SELECT COUNT(*) AS total FROM auth_users') === 0;
    }

    public function seedProduct(bool $resetOperationalData = false, bool $seedOperationalData = true): void
    {
        $this->connection->beginTransaction();
        try {
            $this->upsertCatalogs();

            if ($resetOperationalData) {
                $this->resetOperationalData();
            }

            if ($seedOperationalData) {
                $this->seedUsersAndProfiles();
                $this->seedJugadorPersonajes();
                $this->seedInventory();
                $this->seedDailyMissions();
                $this->competitiveTitleService->recalculate();
            }

            $this->connection->commit();
        } catch (Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            throw $e;
        }
    }

    private function tableExists(string $table): bool
    {
        $value = strtolower((string) $this->connection->fetchOne(
            'SELECT to_regclass(:table) IS NOT NULL AS ok',
            ['table' => 'public.' . $table]
        ));

        return in_array($value, ['1', 't', 'true', 'y', 'yes'], true);
    }

    private function truncateIfExists(string $table): void
    {
        if (!$this->tableExists($table)) {
            return;
        }

        $this->connection->executeStatement('TRUNCATE TABLE ' . $table);
    }

    /** @return list<array<string,mixed>> */
    private function personajesBase(): array
    {
        return [
            ['id' => 'vanguard', 'nombre' => 'Vanguard', 'rol' => 'iniciador', 'descripcion' => 'Abre ventanas tacticas aplicando presion inicial.', 'habilidad' => 'Golpe balistico', 'detalle' => 'Dano medio y aplica Expuesto durante 1 turno.', 'efecto' => ['tipo' => 'aplicar_estado', 'tipoEspecial' => 'apertura', 'estado' => 'expuesto', 'duracion_turnos' => 1, 'rebaja_dano' => 2, 'pasiva' => ['id' => 'apertura_de_choque', 'nombre' => 'Apertura de choque', 'descripcion' => 'El primer golpe sobre un objetivo limpio abre Expuesto y gana dano extra.'], 'tags' => ['starter', 'mark', 'pressure']], 'inicial' => true, 'precio' => 0, 'orden' => 1],
            ['id' => 'bulwark', 'nombre' => 'Bulwark', 'rol' => 'amplificador', 'descripcion' => 'Estabiliza la composicion con mitigacion y economia defensiva.', 'habilidad' => 'Muralla cinetica', 'detalle' => 'Fortifica 1 turno, concede escudo y mejora ganancia de carga al bloquear.', 'efecto' => ['tipo' => 'mitigacion_global', 'tipoEspecial' => 'fortificacion', 'duracion_turnos' => 1, 'escudo' => 8, 'pasiva' => ['id' => 'reserva_bastion', 'nombre' => 'Reserva bastion', 'descripcion' => 'Defender sin cargas genera una capa extra de fortificacion.'], 'tags' => ['amplifier', 'shield', 'defense']], 'inicial' => true, 'precio' => 0, 'orden' => 2],
            ['id' => 'riftblade', 'nombre' => 'Riftblade', 'rol' => 'finalizador', 'descripcion' => 'Convierte estados de vulnerabilidad en dano explosivo.', 'habilidad' => 'Corte de fase', 'detalle' => 'Dano alto; dano extra si el objetivo esta Expuesto o debilitado.', 'efecto' => ['tipo' => 'dano_condicional', 'tipoEspecial' => 'execution', 'dano_base' => 18, 'dano_si_estado' => 24, 'estado_objetivo' => 'expuesto', 'pasiva' => ['id' => 'filo_ejecutor', 'nombre' => 'Filo ejecutor', 'descripcion' => 'Castiga con dano adicional a objetivos vulnerables o bajos de vida.'], 'tags' => ['finisher', 'execute', 'burst']], 'inicial' => true, 'precio' => 0, 'orden' => 3],
            ['id' => 'hexa', 'nombre' => 'Hexa', 'rol' => 'iniciador', 'descripcion' => 'Disrumpe defensas y castiga especiales rivales.', 'habilidad' => 'Marca de entropia', 'detalle' => 'Reduce bloqueo rival, aumenta fallo de especial y deja Hemorragia.', 'efecto' => ['tipo' => 'debuff_defensivo', 'tipoEspecial' => 'disruption', 'estado' => 'hemorragia', 'duracion_turnos' => 2, 'dano_base' => 12, 'pasiva' => ['id' => 'corrupcion_residual', 'nombre' => 'Corrupcion residual', 'descripcion' => 'Sus aperturas dejan desgaste persistente sobre el rival.'], 'tags' => ['starter', 'debuff', 'bleed']], 'inicial' => false, 'precio' => 600, 'orden' => 4],
            ['id' => 'oracle', 'nombre' => 'Oracle', 'rol' => 'amplificador', 'descripcion' => 'Acelera ciclos de especiales mediante precognicion.', 'habilidad' => 'Precognicion', 'detalle' => 'El proximo especial cuesta 1 carga menos y deja Sobrecarga tactica.', 'efecto' => ['tipo' => 'reducir_coste_especial', 'tipoEspecial' => 'tempo', 'reduccion_cargas' => 1, 'dano_base' => 8, 'pasiva' => ['id' => 'presagio_abierto', 'nombre' => 'Presagio abierto', 'descripcion' => 'Su primer especial de cada combate cuesta 1 carga menos.'], 'tags' => ['amplifier', 'charges', 'tempo']], 'inicial' => false, 'precio' => 800, 'orden' => 5],
            ['id' => 'revenant', 'nombre' => 'Revenant', 'rol' => 'finalizador', 'descripcion' => 'Cierra combates recuperando vida mediante dano infligido.', 'habilidad' => 'Deuda de sangre', 'detalle' => 'Dano y cura, con mayor valor si el objetivo ya esta tocado.', 'efecto' => ['tipo' => 'dano_y_curacion', 'tipoEspecial' => 'sustain_finisher', 'curacion' => 7, 'dano_base' => 18, 'pasiva' => ['id' => 'festin_carmesi', 'nombre' => 'Festin carmesi', 'descripcion' => 'Recupera vida extra al presionar objetivos debilitados.'], 'tags' => ['finisher', 'lifesteal', 'sustain']], 'inicial' => false, 'precio' => 1000, 'orden' => 6],
            ['id' => 'warden', 'nombre' => 'Warden', 'rol' => 'amplificador', 'descripcion' => 'Controla picos de dano anulando bonus ofensivos.', 'habilidad' => 'Interceptar', 'detalle' => 'Silencia el impulso ofensivo rival y refuerza su frente.', 'efecto' => ['tipo' => 'anular_bonus_ofensivo', 'tipoEspecial' => 'control', 'estado' => 'silencio_tactico', 'duracion_turnos' => 1, 'dano_base' => 8, 'pasiva' => ['id' => 'guardia_refleja', 'nombre' => 'Guardia refleja', 'descripcion' => 'Cada defensa le deja una capa extra de proteccion.'], 'tags' => ['amplifier', 'control', 'shield']], 'inicial' => false, 'precio' => 1200, 'orden' => 7],
            ['id' => 'spark', 'nombre' => 'Spark', 'rol' => 'amplificador', 'descripcion' => 'Gana tempo con energia residual y picos de presion.', 'habilidad' => 'Sobrecarga', 'detalle' => 'Activa un turno explosivo y prepara el siguiente golpe.', 'efecto' => ['tipo' => 'turno_extra', 'tipoEspecial' => 'tempo_burst', 'dano_base' => 14, 'pasiva' => ['id' => 'chispa_residual', 'nombre' => 'Chispa residual', 'descripcion' => 'Tras usar especial, su siguiente intercambio gana Sobrecarga.'], 'tags' => ['amplifier', 'tempo', 'burst']], 'inicial' => false, 'precio' => 1200, 'orden' => 8],
            ['id' => 'mender', 'nombre' => 'Mender', 'rol' => 'amplificador', 'descripcion' => 'Aporta consistencia limpiando presion rival.', 'habilidad' => 'Reforja', 'detalle' => 'Cura, limpia debuffs y deja una capa de proteccion.', 'efecto' => ['tipo' => 'curar_y_limpiar', 'tipoEspecial' => 'cleanse', 'dano_base' => 4, 'pasiva' => ['id' => 'malla_vital', 'nombre' => 'Malla vital', 'descripcion' => 'Cada curacion relevante deja un escudo pequeno.'], 'tags' => ['amplifier', 'cleanse', 'shield']], 'inicial' => false, 'precio' => 1400, 'orden' => 9],
            ['id' => 'grim', 'nombre' => 'Grim', 'rol' => 'finalizador', 'descripcion' => 'Castiga ciclos de alto gasto con ejecucion matematica.', 'habilidad' => 'Veredicto', 'detalle' => 'Dano escalado por cargas gastadas y por vida faltante del rival.', 'efecto' => ['tipo' => 'dano_por_cargas_gastadas', 'tipoEspecial' => 'execute', 'escalado_por_carga' => 2, 'pasiva' => ['id' => 'sentencia_final', 'nombre' => 'Sentencia final', 'descripcion' => 'Sus especiales mejoran cuanto mas herido esta el rival.'], 'tags' => ['finisher', 'execute', 'cycle']], 'inicial' => false, 'precio' => 1400, 'orden' => 10],
            ['id' => 'tracer', 'nombre' => 'Tracer', 'rol' => 'amplificador', 'descripcion' => 'Multiplica utilidades aliadas mediante ecos tacticos.', 'habilidad' => 'Eco tactico', 'detalle' => 'Recicla utilidad, gana curacion ligera y una fortificacion corta.', 'efecto' => ['tipo' => 'repetir_efecto_no_danino', 'tipoEspecial' => 'echo_support', 'dano_base' => 9, 'potencia' => 0.7, 'pasiva' => ['id' => 'eco_estable', 'nombre' => 'Eco estable', 'descripcion' => 'Sus momentos de apoyo dejan capas extra de seguridad.'], 'tags' => ['amplifier', 'echo', 'utility']], 'inicial' => false, 'precio' => 1600, 'orden' => 11],
            ['id' => 'null', 'nombre' => 'Null', 'rol' => 'iniciador', 'descripcion' => 'Neutraliza contextos de alta varianza.', 'habilidad' => 'Zona muerta', 'detalle' => 'Apaga la volatilidad y aplica silencio tactico corto.', 'efecto' => ['tipo' => 'bloquear_rng', 'tipoEspecial' => 'anti_rng', 'duracion_turnos' => 1, 'pasiva' => ['id' => 'campo_apagado', 'nombre' => 'Campo apagado', 'descripcion' => 'Abre el combate amortiguando la varianza inicial.'], 'tags' => ['starter', 'anti-rng', 'control']], 'inicial' => false, 'precio' => 1800, 'orden' => 12],
        ];
    }

    /** @return list<array<string,mixed>> */
    private function bonificadoresBase(): array
    {
        return [
            ['id' => 'defensa_reforzada', 'nombre' => 'Defensa reforzada', 'categoria' => 'baja', 'descripcion' => 'Dano final global x0.85.', 'reglas' => ['multiplicador_dano_global' => 0.85], 'orden' => 1],
            ['id' => 'fatiga_suave', 'nombre' => 'Fatiga suave', 'categoria' => 'baja', 'descripcion' => 'Desde turno 4: +5% dano global acumulativo por turno.', 'reglas' => ['desde_turno' => 4, 'dano_global_acumulativo' => 0.05], 'orden' => 2],
            ['id' => 'pulso_estable', 'nombre' => 'Pulso estable', 'categoria' => 'baja', 'descripcion' => '-5 pp critico global.', 'reglas' => ['critico_pp' => -5], 'orden' => 3],
            ['id' => 'cadencia_tactica', 'nombre' => 'Cadencia tactica', 'categoria' => 'baja', 'descripcion' => '-50% relativo a probabilidad de repeticion de accion.', 'reglas' => ['repeticion_accion_relativa' => -0.5], 'orden' => 4],
            ['id' => 'ritmo_acelerado', 'nombre' => 'Ritmo acelerado', 'categoria' => 'media', 'descripcion' => '20% de doble turno, maximo 1 extra.', 'reglas' => ['doble_turno_probabilidad' => 0.2, 'max_extra' => 1], 'orden' => 5],
            ['id' => 'eco_accion_moderado', 'nombre' => 'Eco de accion moderado', 'categoria' => 'media', 'descripcion' => '18% de repetir habilidad al 70% de potencia.', 'reglas' => ['eco_probabilidad' => 0.18, 'potencia' => 0.7], 'orden' => 6],
            ['id' => 'critico_inestable', 'nombre' => 'Critico inestable', 'categoria' => 'media', 'descripcion' => '+10 pp critico y +5 pp fallo de habilidades.', 'reglas' => ['critico_pp' => 10, 'fallo_habilidad_pp' => 5], 'orden' => 7],
            ['id' => 'escudo_intermitente', 'nombre' => 'Escudo intermitente', 'categoria' => 'media', 'descripcion' => '25% de reducir 40% del proximo dano recibido ese turno.', 'reglas' => ['escudo_probabilidad' => 0.25, 'reduccion_dano' => 0.4], 'orden' => 8],
            ['id' => 'alta_volatilidad', 'nombre' => 'Alta volatilidad', 'categoria' => 'alta', 'descripcion' => '+15 pp critico, +10 pp fallo, critico x1.7.', 'reglas' => ['critico_pp' => 15, 'fallo_habilidad_pp' => 10, 'multiplicador_critico' => 1.7], 'orden' => 9],
            ['id' => 'caos_controlado', 'nombre' => 'Caos controlado', 'categoria' => 'alta', 'descripcion' => '25% de repetir accion y 15% de doble turno.', 'reglas' => ['eco_probabilidad' => 0.25, 'potencia' => 0.8, 'doble_turno_probabilidad' => 0.15, 'max_extra' => 1], 'orden' => 10],
        ];
    }

    private function upsertCatalogs(): void
    {
        foreach ($this->personajesBase() as $row) {
            $this->connection->executeStatement(
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
                     orden = EXCLUDED.orden',
                [
                    'id' => $row['id'],
                    'nombre' => $row['nombre'],
                    'rol_sinergia' => $row['rol'],
                    'descripcion' => $row['descripcion'],
                    'habilidad_especial_nombre' => $row['habilidad'],
                    'habilidad_especial_descripcion' => $row['detalle'],
                    'efecto_especial_json' => json_encode($row['efecto'], JSON_THROW_ON_ERROR),
                    'coste_cargas' => 2,
                    'desbloqueado_inicial' => (int) $row['inicial'],
                    'precio_monedas' => $row['precio'],
                    'orden' => $row['orden'],
                ]
            );
        }

        foreach ($this->bonificadoresBase() as $row) {
            $this->connection->executeStatement(
                'INSERT INTO bonificadores_partida (id, nombre, categoria_volatilidad, descripcion, reglas_json, activo, orden)
                 VALUES (:id, :nombre, :categoria_volatilidad, :descripcion, CAST(:reglas_json AS jsonb), TRUE, :orden)
                 ON CONFLICT (id) DO UPDATE
                 SET nombre = EXCLUDED.nombre,
                     categoria_volatilidad = EXCLUDED.categoria_volatilidad,
                     descripcion = EXCLUDED.descripcion,
                     reglas_json = EXCLUDED.reglas_json,
                     activo = TRUE,
                     orden = EXCLUDED.orden',
                [
                    'id' => $row['id'],
                    'nombre' => $row['nombre'],
                    'categoria_volatilidad' => $row['categoria'],
                    'descripcion' => $row['descripcion'],
                    'reglas_json' => json_encode($row['reglas'], JSON_THROW_ON_ERROR),
                    'orden' => $row['orden'],
                ]
            );
        }

        foreach ([
            ['id' => 'sp_leyenda_unica', 'nombre' => 'Leyenda Unica', 'cupo' => 1, 'orden' => 1],
            ['id' => 'sp_gran_maestro', 'nombre' => 'Gran Maestro', 'cupo' => 5, 'orden' => 2],
            ['id' => 'sp_maestro', 'nombre' => 'Maestro', 'cupo' => 25, 'orden' => 3],
            ['id' => 'sp_diamante', 'nombre' => 'Diamante', 'cupo' => 75, 'orden' => 4],
            ['id' => 'sp_platino', 'nombre' => 'Platino', 'cupo' => 150, 'orden' => 5],
            ['id' => 'sp_oro', 'nombre' => 'Oro', 'cupo' => 300, 'orden' => 6],
        ] as $row) {
            $this->connection->executeStatement(
                'INSERT INTO titulos_competitivos (id, nombre, cupo, orden, activo)
                 VALUES (:id, :nombre, :cupo, :orden, TRUE)
                 ON CONFLICT (id) DO UPDATE
                 SET nombre = EXCLUDED.nombre,
                     cupo = EXCLUDED.cupo,
                     orden = EXCLUDED.orden,
                     activo = TRUE',
                $row
            );
        }

        foreach ([
            ['id' => 'skin_dorada', 'name' => 'Skin Dorada', 'item_type' => 'skin', 'price_coins' => 500, 'sort_order' => 1],
            ['id' => 'efecto_victoria', 'name' => 'Efecto de Victoria', 'item_type' => 'effect', 'price_coins' => 300, 'sort_order' => 2],
            ['id' => 'avatar_legendario', 'name' => 'Avatar Legendario', 'item_type' => 'avatar', 'price_coins' => 200, 'sort_order' => 3],
            ['id' => 'skin_platino', 'name' => 'Skin Platino', 'item_type' => 'skin', 'price_coins' => 800, 'sort_order' => 4],
        ] as $row) {
            $this->connection->executeStatement(
                'INSERT INTO store_catalog (id, name, item_type, price_coins, sort_order)
                 VALUES (:id, :name, :item_type, :price_coins, :sort_order)
                 ON CONFLICT (id) DO UPDATE
                 SET name = EXCLUDED.name,
                     item_type = EXCLUDED.item_type,
                     price_coins = EXCLUDED.price_coins,
                     sort_order = EXCLUDED.sort_order',
                $row
            );
        }

        foreach ([
            ['id' => 'win_3_matches', 'title' => 'Gana 3 partidas', 'target_value' => 3, 'reward_xp' => 100, 'reward_coins' => 120, 'sort_order' => 1],
            ['id' => 'use_defense_5', 'title' => 'Usa 5 acciones de defensa', 'target_value' => 5, 'reward_xp' => 50, 'reward_coins' => 80, 'sort_order' => 2],
            ['id' => 'play_3_champions', 'title' => 'Juega con 3 campeones diferentes', 'target_value' => 3, 'reward_xp' => 75, 'reward_coins' => 100, 'sort_order' => 3],
        ] as $row) {
            $this->connection->executeStatement(
                'INSERT INTO mission_catalog (id, title, target_value, reward_xp, reward_coins, sort_order)
                 VALUES (:id, :title, :target_value, :reward_xp, :reward_coins, :sort_order)
                 ON CONFLICT (id) DO UPDATE
                 SET title = EXCLUDED.title,
                     target_value = EXCLUDED.target_value,
                     reward_xp = EXCLUDED.reward_xp,
                     reward_coins = EXCLUDED.reward_coins,
                     sort_order = EXCLUDED.sort_order',
                $row
            );
        }

        foreach ([
            ['queue_type' => 'ranked', 'outcome_key' => 'win', 'global_mmr_delta' => 15, 'champion_mmr_delta' => 14, 'coins' => 140, 'gems' => 5, 'xp' => 130, 'mastery_xp' => 60],
            ['queue_type' => 'ranked', 'outcome_key' => 'loss', 'global_mmr_delta' => -11, 'champion_mmr_delta' => -10, 'coins' => 45, 'gems' => 0, 'xp' => 85, 'mastery_xp' => 30],
            ['queue_type' => 'ranked', 'outcome_key' => 'draw', 'global_mmr_delta' => 0, 'champion_mmr_delta' => 0, 'coins' => 70, 'gems' => 0, 'xp' => 90, 'mastery_xp' => 35],
            ['queue_type' => 'bot', 'outcome_key' => 'win', 'global_mmr_delta' => 11, 'champion_mmr_delta' => 9, 'coins' => 120, 'gems' => 0, 'xp' => 110, 'mastery_xp' => 45],
            ['queue_type' => 'bot', 'outcome_key' => 'loss', 'global_mmr_delta' => -8, 'champion_mmr_delta' => -6, 'coins' => 50, 'gems' => 0, 'xp' => 75, 'mastery_xp' => 25],
            ['queue_type' => 'bot', 'outcome_key' => 'draw', 'global_mmr_delta' => 0, 'champion_mmr_delta' => 0, 'coins' => 70, 'gems' => 0, 'xp' => 90, 'mastery_xp' => 35],
        ] as $row) {
            $this->connection->executeStatement(
                'INSERT INTO match_outcome_rules (queue_type, outcome_key, global_mmr_delta, champion_mmr_delta, coins, gems, xp, mastery_xp)
                 VALUES (:queue_type, :outcome_key, :global_mmr_delta, :champion_mmr_delta, :coins, :gems, :xp, :mastery_xp)
                 ON CONFLICT (queue_type, outcome_key) DO UPDATE
                 SET global_mmr_delta = EXCLUDED.global_mmr_delta,
                     champion_mmr_delta = EXCLUDED.champion_mmr_delta,
                     coins = EXCLUDED.coins,
                     gems = EXCLUDED.gems,
                     xp = EXCLUDED.xp,
                     mastery_xp = EXCLUDED.mastery_xp',
                $row
            );
        }
    }

    private function seedUsersAndProfiles(): void
    {
        $seedUsers = [
            ['id' => '11111111-1111-1111-1111-111111111111', 'username' => 'playerone', 'email' => 'playerone@trueduel.local', 'display_name' => 'Player One', 'rank_label' => 'Bronce', 'mmr_global' => 1210, 'puntos_habilidad' => 1210, 'coins' => 1600, 'gems' => 25, 'experience_total' => 560, 'level' => 4],
            ['id' => '22222222-2222-2222-2222-222222222222', 'username' => 'raven', 'email' => 'raven@trueduel.local', 'display_name' => 'Raven', 'rank_label' => 'Plata', 'mmr_global' => 1450, 'puntos_habilidad' => 1450, 'coins' => 2000, 'gems' => 55, 'experience_total' => 980, 'level' => 6],
            ['id' => '33333333-3333-3333-3333-333333333333', 'username' => 'nova', 'email' => 'nova@trueduel.local', 'display_name' => 'Nova', 'rank_label' => 'Plata', 'mmr_global' => 1410, 'puntos_habilidad' => 1410, 'coins' => 1800, 'gems' => 40, 'experience_total' => 860, 'level' => 5],
            ['id' => '44444444-4444-4444-4444-444444444444', 'username' => 'ember', 'email' => 'ember@trueduel.local', 'display_name' => 'Ember', 'rank_label' => 'Bronce', 'mmr_global' => 1320, 'puntos_habilidad' => 1320, 'coins' => 900, 'gems' => 10, 'experience_total' => 410, 'level' => 3],
            ['id' => '55555555-5555-5555-5555-555555555555', 'username' => 'atlas', 'email' => 'atlas@trueduel.local', 'display_name' => 'Atlas', 'rank_label' => 'Hierro', 'mmr_global' => 1100, 'puntos_habilidad' => 1100, 'coins' => 750, 'gems' => 5, 'experience_total' => 180, 'level' => 2],
            ['id' => '66666666-6666-6666-6666-666666666666', 'username' => 'luna', 'email' => 'luna@trueduel.local', 'display_name' => 'Luna', 'rank_label' => 'Bronce', 'mmr_global' => 1260, 'puntos_habilidad' => 1260, 'coins' => 1200, 'gems' => 18, 'experience_total' => 630, 'level' => 4],
        ];

        foreach ($seedUsers as $seed) {
            $this->connection->executeStatement(
                'INSERT INTO auth_users (id, username, email, password_hash, created_at)
                 VALUES (:id, :username, :email, :password_hash, NOW())
                 ON CONFLICT (id) DO UPDATE
                 SET username = EXCLUDED.username,
                     email = EXCLUDED.email,
                     password_hash = EXCLUDED.password_hash',
                [
                    'id' => $seed['id'],
                    'username' => $seed['username'],
                    'email' => $seed['email'],
                    'password_hash' => password_hash('123456', PASSWORD_BCRYPT),
                ]
            );
            $this->connection->executeStatement(
                "INSERT INTO player_profiles (player_id, display_name, rank_label, mmr_global, region, puntos_habilidad, titulo_competitivo, coins, gems, experience_total, level, total_matches, wins, losses, updated_at)
                 VALUES (:player_id, :display_name, :rank_label, :mmr_global, 'eu-west', :puntos_habilidad, 'Combatiente', :coins, :gems, :experience_total, :level, 0, 0, 0, NOW())
                 ON CONFLICT (player_id) DO UPDATE
                 SET display_name = EXCLUDED.display_name,
                     rank_label = EXCLUDED.rank_label,
                     mmr_global = EXCLUDED.mmr_global,
                     region = EXCLUDED.region,
                     puntos_habilidad = EXCLUDED.puntos_habilidad,
                     coins = EXCLUDED.coins,
                     gems = EXCLUDED.gems,
                     experience_total = EXCLUDED.experience_total,
                     level = EXCLUDED.level",
                [
                    'player_id' => $seed['id'],
                    'display_name' => $seed['display_name'],
                    'rank_label' => $seed['rank_label'],
                    'mmr_global' => $seed['mmr_global'],
                    'puntos_habilidad' => $seed['puntos_habilidad'],
                    'coins' => $seed['coins'],
                    'gems' => $seed['gems'],
                    'experience_total' => $seed['experience_total'],
                    'level' => $seed['level'],
                ]
            );
        }
    }

    private function seedJugadorPersonajes(): void
    {
        $personajes = $this->connection->fetchAllAssociative('SELECT id, desbloqueado_inicial FROM personajes ORDER BY orden ASC, id ASC');
        $players = $this->connection->fetchFirstColumn('SELECT player_id FROM player_profiles');

        foreach ($players as $playerId) {
            if (!is_string($playerId) || $playerId === '') {
                continue;
            }

            $equipoInicial = [];
            foreach ($personajes as $personaje) {
                $desbloqueado = in_array(strtolower((string) ($personaje['desbloqueado_inicial'] ?? 'false')), ['1', 't', 'true', 'yes', 'y'], true);
                $personajeId = (string) ($personaje['id'] ?? '');
                $this->connection->executeStatement(
                    'INSERT INTO jugador_personajes (jugador_id, personaje_id, desbloqueado, nivel_maestria, xp_maestria, desbloqueado_en, actualizado_en)
                     VALUES (:jugador_id, :personaje_id, :desbloqueado, 1, 0, :desbloqueado_en, NOW())
                     ON CONFLICT (jugador_id, personaje_id) DO UPDATE
                     SET desbloqueado = EXCLUDED.desbloqueado,
                         desbloqueado_en = EXCLUDED.desbloqueado_en,
                         actualizado_en = NOW()',
                    [
                        'jugador_id' => $playerId,
                        'personaje_id' => $personajeId,
                        'desbloqueado' => (int) $desbloqueado,
                        'desbloqueado_en' => $desbloqueado ? gmdate('Y-m-d H:i:s') : null,
                    ]
                );
                if ($desbloqueado && count($equipoInicial) < 3) {
                    $equipoInicial[] = $personajeId;
                }
            }

            foreach ($equipoInicial as $indice => $personajeId) {
                $this->connection->executeStatement(
                    'INSERT INTO equipos_jugador (jugador_id, slot, personaje_id, actualizado_en)
                     VALUES (:jugador_id, :slot, :personaje_id, NOW())
                     ON CONFLICT (jugador_id, slot) DO UPDATE
                     SET personaje_id = EXCLUDED.personaje_id,
                         actualizado_en = NOW()',
                    [
                        'jugador_id' => $playerId,
                        'slot' => $indice + 1,
                        'personaje_id' => $personajeId,
                    ]
                );
            }
        }
    }

    private function seedInventory(): void
    {
        foreach ([
            ['player_id' => '11111111-1111-1111-1111-111111111111', 'item_id' => 'skin_dorada', 'item_type' => 'skin', 'quantity' => 1, 'equipped' => 1],
            ['player_id' => '11111111-1111-1111-1111-111111111111', 'item_id' => 'avatar_legendario', 'item_type' => 'avatar', 'quantity' => 1, 'equipped' => 1],
            ['player_id' => '22222222-2222-2222-2222-222222222222', 'item_id' => 'efecto_victoria', 'item_type' => 'effect', 'quantity' => 1, 'equipped' => 1],
        ] as $row) {
            $this->connection->executeStatement(
                'INSERT INTO player_inventory (player_id, item_id, item_type, quantity, equipped, acquired_at, updated_at)
                 VALUES (:player_id, :item_id, :item_type, :quantity, :equipped, NOW(), NOW())
                 ON CONFLICT (player_id, item_id) DO UPDATE
                 SET quantity = EXCLUDED.quantity,
                     equipped = EXCLUDED.equipped,
                     updated_at = NOW()',
                $row
            );
        }
    }

    private function seedDailyMissions(): void
    {
        $today = gmdate('Y-m-d');
        $missions = $this->connection->fetchAllAssociative('SELECT id, target_value, reward_xp, reward_coins FROM mission_catalog ORDER BY sort_order ASC');
        $players = $this->connection->fetchFirstColumn('SELECT player_id FROM player_profiles');

        foreach ($players as $playerId) {
            if (!is_string($playerId) || $playerId === '') {
                continue;
            }

            foreach ($missions as $mission) {
                $missionId = (string) ($mission['id'] ?? '');
                $progress = 0;
                $claimed = false;
                if ($playerId === '11111111-1111-1111-1111-111111111111') {
                    if ($missionId === 'use_defense_5') {
                        $progress = (int) ($mission['target_value'] ?? 5);
                        $claimed = true;
                    } elseif ($missionId === 'win_3_matches') {
                        $progress = 2;
                    } elseif ($missionId === 'play_3_champions') {
                        $progress = 1;
                    }
                }

                $this->connection->executeStatement(
                    'INSERT INTO player_daily_missions (player_id, mission_date, mission_id, target_value, progress_value, reward_xp, reward_coins, claimed, updated_at)
                     VALUES (:player_id, :mission_date, :mission_id, :target_value, :progress_value, :reward_xp, :reward_coins, :claimed, NOW())
                     ON CONFLICT (player_id, mission_date, mission_id) DO UPDATE
                     SET target_value = EXCLUDED.target_value,
                         progress_value = EXCLUDED.progress_value,
                         reward_xp = EXCLUDED.reward_xp,
                         reward_coins = EXCLUDED.reward_coins,
                         claimed = EXCLUDED.claimed,
                         updated_at = NOW()',
                    [
                        'player_id' => $playerId,
                        'mission_date' => $today,
                        'mission_id' => $missionId,
                        'target_value' => (int) ($mission['target_value'] ?? 0),
                        'progress_value' => $progress,
                        'reward_xp' => (int) ($mission['reward_xp'] ?? 0),
                        'reward_coins' => (int) ($mission['reward_coins'] ?? 0),
                        'claimed' => (int) $claimed,
                    ]
                );
            }

            if ($playerId === '11111111-1111-1111-1111-111111111111') {
                $this->connection->executeStatement(
                    'INSERT INTO player_daily_mission_champions (player_id, mission_date, champion_id)
                     VALUES (:player_id, :mission_date, :champion_id)
                     ON CONFLICT (player_id, mission_date, champion_id) DO NOTHING',
                    [
                        'player_id' => $playerId,
                        'mission_date' => $today,
                        'champion_id' => 'vanguard',
                    ]
                );
            }
        }
    }

    private function resetOperationalData(): void
    {
        foreach ([
            'match_settlements',
            'turns',
            'matchmaking_tickets',
            'matches',
            'match_history',
            'player_daily_mission_champions',
            'player_daily_missions',
            'player_inventory',
            'equipos_jugador',
            'jugador_personajes',
            'player_profiles',
            'auth_users',
        ] as $table) {
            $this->truncateIfExists($table);
        }
    }
}
