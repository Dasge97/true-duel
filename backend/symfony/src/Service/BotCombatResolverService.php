<?php

declare(strict_types=1);

namespace App\Service;

final class BotCombatResolverService
{
    public function __construct(
        private readonly CombatRulesEngine $combatRulesEngine,
        private readonly CombatStateMapper $combatStateMapper,
    ) {
    }

    /**
     * Resuelve un turno 3v3: primero las acciones del jugador (slot 0→2), luego las del bot.
     * Auto-redirige si el objetivo ya está muerto.
     *
     * @param array<string,mixed> $estado
     * @param list<array{slot:int,action:string,targetSlot:int|null}> $accionesJugador
     * @param list<array{slot:int,action:string,targetSlot:int|null}> $accionesBot
     * @return array<string,mixed>
     */
    public function resolverTurnoBot(array $estado, array $accionesJugador, array $accionesBot): array
    {
        $snapshot        = $this->combatStateMapper->readBotState($estado);
        $turno           = $snapshot['turnNo'] + 1;
        $playerChampions = $snapshot['playerChampions'];
        $enemyChampions  = $snapshot['enemyChampions'];
        $playerSynergies = $snapshot['playerSynergies'];
        $enemySynergies  = $snapshot['enemySynergies'];
        $modifier        = $snapshot['modifier'];

        $playerEvents = $this->resolveActions(
            $accionesJugador,
            $playerChampions,
            $enemyChampions,
            $playerSynergies,
            $enemySynergies,
            $modifier,
            $turno
        );
        $playerChampions = $playerEvents['actorChampions'];
        $enemyChampions  = $playerEvents['targetChampions'];

        $botEvents = $this->resolveActions(
            $accionesBot,
            $enemyChampions,
            $playerChampions,
            $enemySynergies,
            $playerSynergies,
            $modifier,
            $turno
        );
        $enemyChampions  = $botEvents['actorChampions'];
        $playerChampions = $botEvents['targetChampions'];

        $playerChampions = $this->applyEndOfTurnEffects($playerChampions);
        $enemyChampions  = $this->applyEndOfTurnEffects($enemyChampions);

        $estadoNuevo = $this->combatStateMapper->writeBotState(
            $estado,
            $turno,
            $playerChampions,
            $enemyChampions,
            $playerSynergies,
            $enemySynergies,
            $modifier
        );

        return [
            'estado'        => $estadoNuevo,
            'playerEvents'  => $playerEvents['events'],
            'botEvents'     => $botEvents['events'],
            'damageDealt'   => array_sum(array_column($playerEvents['events'], 'damage')),
            'damageTaken'   => array_sum(array_column($botEvents['events'], 'damage')),
        ];
    }

    /**
     * Resuelve las acciones de un bando contra el otro (actor → target).
     * Devuelve los arrays de campeones actualizados y los eventos por acción.
     *
     * @param list<array{slot:int,action:string,targetSlot:int|null}> $acciones
     * @param list<array<string,mixed>> $actorChampions
     * @param list<array<string,mixed>> $targetChampions
     * @param array<string,mixed> $actorSynergies
     * @param array<string,mixed> $targetSynergies
     * @param array<string,mixed> $modifier
     * @return array{actorChampions:list<array<string,mixed>>,targetChampions:list<array<string,mixed>>,events:list<array<string,mixed>>}
     */
    private function resolveActions(
        array $acciones,
        array $actorChampions,
        array $targetChampions,
        array $actorSynergies,
        array $targetSynergies,
        array $modifier,
        int $turno
    ): array {
        $events = [];

        foreach ($acciones as $accion) {
            $actorSlot  = (int) ($accion['slot'] ?? 0);
            $targetSlot = isset($accion['targetSlot']) ? (int) $accion['targetSlot'] : null;
            $action     = (string) ($accion['action'] ?? 'attack');

            if (!isset($actorChampions[$actorSlot]) || $actorChampions[$actorSlot]['hp'] <= 0) {
                continue;
            }

            $effectiveTarget = $this->findLivingTarget($targetChampions, $targetSlot);
            if ($effectiveTarget === null) {
                break;
            }

            $actorChamp  = $actorChampions[$actorSlot];
            $targetChamp = $targetChampions[$effectiveTarget];

            $resultado = $this->combatRulesEngine->resolverAccion(
                $action,
                $actorChamp['id'],
                $actorChamp['charges'],
                $actorChamp['spentCharges'],
                $actorChamp['effects'],
                $targetChamp['effects'],
                $actorSynergies,
                $modifier,
                $turno,
                $targetChamp['hp']
            );

            $accionAplicada = (string) $resultado['accionAplicada'];

            $actorChampions[$actorSlot]['charges']      = (int) $resultado['cargas'];
            $actorChampions[$actorSlot]['spentCharges'] = $actorChamp['spentCharges'] + (int) ($resultado['cargasGastadas'] ?? 0);
            $actorChampions[$actorSlot]['effects']      = $resultado['efectosActor'];
            $actorChampions[$actorSlot]['guarding']     = ($accionAplicada === 'defend');
            $targetChampions[$effectiveTarget]['effects'] = $resultado['efectosObjetivo'];

            $finalDamage = 0;

            if ($accionAplicada !== 'defend') {
                $finalDamage = $this->applyDamagePipeline(
                    (int) $resultado['dano'],
                    $targetChampions[$effectiveTarget],
                    $targetSynergies,
                    $modifier,
                    $turno
                );

                $targetChampions[$effectiveTarget]['hp'] = max(
                    0,
                    $targetChampions[$effectiveTarget]['hp'] - $finalDamage
                );

                $danoPropio = (int) ($resultado['danoPropio'] ?? 0);
                if ($danoPropio > 0) {
                    $actorChampions[$actorSlot]['hp'] = max(0, $actorChamp['hp'] - $danoPropio);
                }
            }

            $events[] = [
                'actorSlot'  => $actorSlot,
                'targetSlot' => $effectiveTarget,
                'action'     => $accionAplicada,
                'damage'     => $finalDamage,
            ];
        }

        return [
            'actorChampions'  => array_values($actorChampions),
            'targetChampions' => array_values($targetChampions),
            'events'          => $events,
        ];
    }

    /**
     * Aplica el pipeline de daño al campeón objetivo.
     * Consume el flag guarding si está activo.
     *
     * @param array<string,mixed> $targetChamp (por referencia conceptual — se modifica el array del llamador)
     * @param array<string,mixed> $targetSynergies
     * @param array<string,mixed> $modifier
     */
    private function applyDamagePipeline(
        int $danoRaw,
        array &$targetChamp,
        array $targetSynergies,
        array $modifier,
        int $turno
    ): int {
        $effects = $targetChamp['effects'];

        if ((bool) $targetChamp['guarding']) {
            $danoRaw = $this->combatRulesEngine->aplicarMitigacionDefensa(
                $danoRaw,
                'defend',
                $targetChamp['id'],
                $targetSynergies,
                $effects
            );
            $targetChamp['guarding'] = false;
        }

        if ((int) ($effects['mitigacion_turnos'] ?? 0) > 0) {
            $danoRaw = (int) floor($danoRaw * 0.75);
        }
        if ((int) ($effects['fortificado_turnos'] ?? 0) > 0) {
            $danoRaw = (int) floor($danoRaw * 0.8);
        }

        $bloqueoRng = (int) ($effects['bloqueo_rng_turnos'] ?? 0) > 0;
        $danoRaw    = $this->combatRulesEngine->aplicarEscudoIntermitente($danoRaw, $modifier, $bloqueoRng);
        $danoRaw    = $this->combatRulesEngine->aplicarBonificadorDano($danoRaw, $turno, $modifier);
        $danoRaw    = $this->combatRulesEngine->limitarDano($danoRaw);

        [$finalDamage, $newEffects] = $this->combatRulesEngine->absorberEscudo($danoRaw, $effects);
        $targetChamp['effects'] = $newEffects;

        return $finalDamage;
    }

    /**
     * Aplica efectos de fin de turno a todos los campeones vivos del equipo.
     *
     * @param list<array<string,mixed>> $champions
     * @return list<array<string,mixed>>
     */
    private function applyEndOfTurnEffects(array $champions): array
    {
        foreach ($champions as &$champ) {
            if ($champ['hp'] <= 0) {
                continue;
            }
            [$champ['hp'], $champ['effects']] = $this->combatRulesEngine->aplicarCuracionYLimpieza($champ['hp'], $champ['effects']);
            [$champ['hp'], $champ['effects']] = $this->combatRulesEngine->aplicarEfectosFinTurno($champ['hp'], $champ['effects']);
            $champ['effects'] = $this->combatRulesEngine->consumirTurnoEfectos($champ['effects']);
            $champ['hp']      = max(0, $champ['hp']);
        }
        unset($champ);

        return array_values($champions);
    }

    /**
     * Busca el primer slot de objetivo vivo, empezando por el preferido.
     * Si el preferido está muerto, busca el siguiente en orden circular.
     *
     * @param list<array<string,mixed>> $champions
     */
    private function findLivingTarget(array $champions, ?int $preferred): ?int
    {
        if ($preferred !== null && isset($champions[$preferred]) && $champions[$preferred]['hp'] > 0) {
            return $preferred;
        }

        $start = $preferred ?? 0;
        $count = count($champions);
        for ($offset = 0; $offset < $count; $offset++) {
            $slot = ($start + $offset) % $count;
            if (isset($champions[$slot]) && $champions[$slot]['hp'] > 0) {
                return $slot;
            }
        }

        return null;
    }
}
