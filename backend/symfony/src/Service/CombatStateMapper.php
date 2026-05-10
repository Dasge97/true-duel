<?php

declare(strict_types=1);

namespace App\Service;

final class CombatStateMapper
{
    public function __construct(
        private readonly CombatRulesEngine $combatRulesEngine,
    ) {
    }

    /** @param mixed $value @return list<array<string,mixed>> */
    public function normalizeChampions(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $champ) {
            if (!is_array($champ)) {
                continue;
            }
            $out[] = [
                'id'          => (string) ($champ['id'] ?? 'vanguard'),
                'hp'          => max(0, (int) ($champ['hp'] ?? $this->combatRulesEngine->maxHealth())),
                'charges'     => max(0, (int) ($champ['charges'] ?? 0)),
                'spentCharges' => max(0, (int) ($champ['spentCharges'] ?? 0)),
                'effects'     => $this->combatRulesEngine->normalizarEfectos($champ['effects'] ?? []),
                'guarding'    => (bool) ($champ['guarding'] ?? false),
            ];
        }

        return $out;
    }

    /** @param array<string,mixed> $state @return array<string,mixed> */
    public function readBotState(array $state): array
    {
        return [
            'turnNo'          => (int) ($state['turnNo'] ?? 0),
            'playerChampions' => $this->normalizeChampions($state['playerChampions'] ?? []),
            'enemyChampions'  => $this->normalizeChampions($state['enemyChampions'] ?? []),
            'playerSynergies' => $this->combatRulesEngine->normalizarSinergias($state['playerSynergies'] ?? []),
            'enemySynergies'  => $this->combatRulesEngine->normalizarSinergias($state['enemySynergies'] ?? []),
            'modifier'        => $this->combatRulesEngine->normalizarBonificador($state['modifier'] ?? []),
        ];
    }

    /** @param array<string,mixed> $state @return array<string,mixed> */
    public function readPvpState(array $state, bool $actorIsP1): array
    {
        $actorKey    = $actorIsP1 ? 'p1Champions' : 'p2Champions';
        $targetKey   = $actorIsP1 ? 'p2Champions' : 'p1Champions';
        $actorSynKey = $actorIsP1 ? 'p1Synergies' : 'p2Synergies';
        $targetSynKey = $actorIsP1 ? 'p2Synergies' : 'p1Synergies';

        return [
            'turnNo'           => (int) ($state['turnNo'] ?? 0),
            'actorChampions'   => $this->normalizeChampions($state[$actorKey] ?? []),
            'targetChampions'  => $this->normalizeChampions($state[$targetKey] ?? []),
            'actorSynergies'   => $this->combatRulesEngine->normalizarSinergias($state[$actorSynKey] ?? []),
            'targetSynergies'  => $this->combatRulesEngine->normalizarSinergias($state[$targetSynKey] ?? []),
            'modifier'         => $this->combatRulesEngine->normalizarBonificador($state['modifier'] ?? []),
        ];
    }

    /**
     * @param array<string,mixed> $state
     * @param list<array<string,mixed>> $playerChampions
     * @param list<array<string,mixed>> $enemyChampions
     * @return array<string,mixed>
     */
    public function writeBotState(
        array $state,
        int $turnNo,
        array $playerChampions,
        array $enemyChampions,
        array $playerSynergies,
        array $enemySynergies,
        array $modifier,
    ): array {
        $state['turnNo']          = $turnNo;
        $state['playerChampions'] = $playerChampions;
        $state['enemyChampions']  = $enemyChampions;
        $state['playerSynergies'] = $playerSynergies;
        $state['enemySynergies']  = $enemySynergies;
        $state['modifier']        = $modifier;

        return $state;
    }

    /**
     * @param array<string,mixed> $state
     * @param list<array<string,mixed>> $actorChampions
     * @param list<array<string,mixed>> $targetChampions
     * @return array<string,mixed>
     */
    public function writePvpState(
        array $state,
        bool $actorIsP1,
        int $turnNo,
        array $actorChampions,
        array $targetChampions,
        array $actorSynergies,
        array $targetSynergies,
        array $modifier,
    ): array {
        $actorKey    = $actorIsP1 ? 'p1Champions' : 'p2Champions';
        $targetKey   = $actorIsP1 ? 'p2Champions' : 'p1Champions';
        $actorSynKey = $actorIsP1 ? 'p1Synergies' : 'p2Synergies';
        $targetSynKey = $actorIsP1 ? 'p2Synergies' : 'p1Synergies';

        $state['turnNo']       = $turnNo;
        $state[$actorKey]      = $actorChampions;
        $state[$targetKey]     = $targetChampions;
        $state[$actorSynKey]   = $actorSynergies;
        $state[$targetSynKey]  = $targetSynergies;
        $state['modifier']     = $modifier;

        return $state;
    }
}
