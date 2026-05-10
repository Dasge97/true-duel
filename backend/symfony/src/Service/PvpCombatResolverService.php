<?php

declare(strict_types=1);

namespace App\Service;

final class PvpCombatResolverService
{
    public function __construct(
        private readonly CombatRulesEngine $combatRulesEngine,
        private readonly CombatStateMapper $combatStateMapper,
    ) {
    }

    /**
     * Resuelve las 3 acciones del actor (P1 o P2) contra el equipo rival.
     * Turno alternado: el actor actúa, el rival actúa en su propio turno.
     *
     * @param array<string,mixed> $estado
     * @param list<array{slot:int,action:string,targetSlot:int|null}> $acciones
     * @return array<string,mixed>
     */
    public function resolverTurnoPvp(array $estado, bool $actorEsP1, array $acciones): array
    {
        $snapshot        = $this->combatStateMapper->readPvpState($estado, $actorEsP1);
        $turno           = $snapshot['turnNo'] + 1;
        $actorChampions  = $snapshot['actorChampions'];
        $targetChampions = $snapshot['targetChampions'];
        $actorSynergies  = $snapshot['actorSynergies'];
        $targetSynergies = $snapshot['targetSynergies'];
        $modifier        = $snapshot['modifier'];

        $events      = [];
        $damageDealt = 0;

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

            $actorChampions[$actorSlot]['charges']        = (int) $resultado['cargas'];
            $actorChampions[$actorSlot]['spentCharges']   = $actorChamp['spentCharges'] + (int) ($resultado['cargasGastadas'] ?? 0);
            $actorChampions[$actorSlot]['effects']        = $resultado['efectosActor'];
            $actorChampions[$actorSlot]['guarding']       = ($accionAplicada === 'defend');
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

                $damageDealt += $finalDamage;
            }

            $events[] = [
                'actorSlot'  => $actorSlot,
                'targetSlot' => $effectiveTarget,
                'action'     => $accionAplicada,
                'damage'     => $finalDamage,
            ];
        }

        $actorChampions  = $this->applyEndOfTurnEffects($actorChampions);
        $targetChampions = array_values($targetChampions);

        $estadoNuevo = $this->combatStateMapper->writePvpState(
            $estado,
            $actorEsP1,
            $turno,
            $actorChampions,
            $targetChampions,
            $actorSynergies,
            $targetSynergies,
            $modifier
        );

        return [
            'estado'      => $estadoNuevo,
            'events'      => $events,
            'damageDealt' => $damageDealt,
        ];
    }

    /**
     * @param array<string,mixed> $targetChamp (modificado por referencia — guarding y effects pueden cambiar)
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

    /** @param list<array<string,mixed>> $champions @return list<array<string,mixed>> */
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

    /** @param list<array<string,mixed>> $champions */
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
