<?php

declare(strict_types=1);

namespace App\Service;

final class CombatStateMapper
{
    public function __construct(
        private readonly CombatRulesEngine $combatRulesEngine,
    ) {
    }

    /** @param array<string,mixed> $state @return array<string,mixed> */
    public function readBotState(array $state): array
    {
        return [
            'turnNo' => (int) ($state['turnNo'] ?? 0),
            'playerHp' => (int) ($state['playerHp'] ?? $this->combatRulesEngine->maxHealth()),
            'enemyHp' => (int) ($state['enemyHp'] ?? $this->combatRulesEngine->maxHealth()),
            'playerCharges' => (int) ($state['playerCharges'] ?? 0),
            'enemyCharges' => (int) ($state['enemyCharges'] ?? 0),
            'playerChampionId' => (string) ($state['playerChampionId'] ?? 'vanguard'),
            'enemyChampionId' => (string) ($state['enemyChampionId'] ?? 'vanguard'),
            'playerEffects' => $this->combatRulesEngine->normalizarEfectos($state['efectosJugador'] ?? []),
            'enemyEffects' => $this->combatRulesEngine->normalizarEfectos($state['efectosRival'] ?? []),
            'playerSynergies' => $this->combatRulesEngine->normalizarSinergias($state['sinergiasJugador'] ?? []),
            'enemySynergies' => $this->combatRulesEngine->normalizarSinergias($state['sinergiasRival'] ?? []),
            'modifier' => $this->combatRulesEngine->normalizarBonificador($state['bonificadorPartida'] ?? []),
            'playerSpentCharges' => (int) ($state['cargasGastadasJugador'] ?? 0),
            'enemySpentCharges' => (int) ($state['cargasGastadasRival'] ?? 0),
            'playerPassive' => is_array($state['playerPassive'] ?? null) ? $state['playerPassive'] : null,
            'enemyPassive' => is_array($state['enemyPassive'] ?? null) ? $state['enemyPassive'] : null,
        ];
    }

    /**
     * @param array<string,mixed> $state
     * @param array<string,mixed> $values
     * @return array<string,mixed>
     */
    public function writeBotState(array $state, array $values): array
    {
        $state['turnNo'] = (int) ($values['turnNo'] ?? $state['turnNo'] ?? 0);
        $state['playerHp'] = (int) ($values['playerHp'] ?? $state['playerHp'] ?? $this->combatRulesEngine->maxHealth());
        $state['enemyHp'] = (int) ($values['enemyHp'] ?? $state['enemyHp'] ?? $this->combatRulesEngine->maxHealth());
        $state['playerCharges'] = (int) ($values['playerCharges'] ?? $state['playerCharges'] ?? 0);
        $state['enemyCharges'] = (int) ($values['enemyCharges'] ?? $state['enemyCharges'] ?? 0);
        $state['efectosJugador'] = is_array($values['playerEffects'] ?? null) ? $values['playerEffects'] : ($state['efectosJugador'] ?? []);
        $state['efectosRival'] = is_array($values['enemyEffects'] ?? null) ? $values['enemyEffects'] : ($state['efectosRival'] ?? []);
        $state['sinergiasJugador'] = is_array($values['playerSynergies'] ?? null) ? $values['playerSynergies'] : ($state['sinergiasJugador'] ?? []);
        $state['sinergiasRival'] = is_array($values['enemySynergies'] ?? null) ? $values['enemySynergies'] : ($state['sinergiasRival'] ?? []);
        $state['bonificadorPartida'] = is_array($values['modifier'] ?? null) ? $values['modifier'] : ($state['bonificadorPartida'] ?? []);
        $state['cargasGastadasJugador'] = (int) ($values['playerSpentCharges'] ?? $state['cargasGastadasJugador'] ?? 0);
        $state['cargasGastadasRival'] = (int) ($values['enemySpentCharges'] ?? $state['cargasGastadasRival'] ?? 0);
        $state['ultimoTipoAtaqueJugador'] = (string) ($values['lastPlayerAttackType'] ?? $state['ultimoTipoAtaqueJugador'] ?? 'basico');
        $state['ultimoTipoAtaqueRival'] = (string) ($values['lastEnemyAttackType'] ?? $state['ultimoTipoAtaqueRival'] ?? 'basico');
        $state['playerActiveEffects'] = $this->combatRulesEngine->activeEffects(is_array($state['efectosJugador'] ?? null) ? $state['efectosJugador'] : []);
        $state['enemyActiveEffects'] = $this->combatRulesEngine->activeEffects(is_array($state['efectosRival'] ?? null) ? $state['efectosRival'] : []);

        return $state;
    }

    /** @param array<string,mixed> $state @return array<string,mixed> */
    public function readPvpState(array $state, bool $actorIsP1): array
    {
        $prefix = $actorIsP1 ? 'p1' : 'p2';
        $targetPrefix = $actorIsP1 ? 'p2' : 'p1';

        return [
            'turnNo' => (int) ($state['turnNo'] ?? 0),
            'actorHp' => (int) ($state[$prefix . 'Hp'] ?? $this->combatRulesEngine->maxHealth()),
            'targetHp' => (int) ($state[$targetPrefix . 'Hp'] ?? $this->combatRulesEngine->maxHealth()),
            'actorCharges' => (int) ($state[$prefix . 'Charges'] ?? 0),
            'actorGuarding' => (bool) ($state[$prefix . 'Guarding'] ?? false),
            'targetGuarding' => (bool) ($state[$targetPrefix . 'Guarding'] ?? false),
            'actorChampionId' => (string) ($state[$prefix . 'ChampionId'] ?? 'vanguard'),
            'targetChampionId' => (string) ($state[$targetPrefix . 'ChampionId'] ?? 'vanguard'),
            'actorEffects' => $this->combatRulesEngine->normalizarEfectos($state[$prefix . 'Efectos'] ?? []),
            'targetEffects' => $this->combatRulesEngine->normalizarEfectos($state[$targetPrefix . 'Efectos'] ?? []),
            'actorSynergies' => $this->combatRulesEngine->normalizarSinergias($state[$prefix . 'Sinergias'] ?? []),
            'targetSynergies' => $this->combatRulesEngine->normalizarSinergias($state[$targetPrefix . 'Sinergias'] ?? []),
            'actorSpentCharges' => (int) ($state[$prefix . 'CargasGastadas'] ?? 0),
            'modifier' => $this->combatRulesEngine->normalizarBonificador($state['bonificadorPartida'] ?? []),
            'actorPassive' => is_array($state[$prefix . 'Passive'] ?? null) ? $state[$prefix . 'Passive'] : null,
            'targetPassive' => is_array($state[$targetPrefix . 'Passive'] ?? null) ? $state[$targetPrefix . 'Passive'] : null,
        ];
    }

    /**
     * @param array<string,mixed> $state
     * @param array<string,mixed> $values
     * @return array<string,mixed>
     */
    public function writePvpState(array $state, bool $actorIsP1, array $values): array
    {
        $prefix = $actorIsP1 ? 'p1' : 'p2';
        $targetPrefix = $actorIsP1 ? 'p2' : 'p1';

        $state['turnNo'] = (int) ($values['turnNo'] ?? $state['turnNo'] ?? 0);
        $state[$prefix . 'Hp'] = (int) ($values['actorHp'] ?? $state[$prefix . 'Hp'] ?? $this->combatRulesEngine->maxHealth());
        $state[$targetPrefix . 'Hp'] = (int) ($values['targetHp'] ?? $state[$targetPrefix . 'Hp'] ?? $this->combatRulesEngine->maxHealth());
        $state[$prefix . 'Charges'] = (int) ($values['actorCharges'] ?? $state[$prefix . 'Charges'] ?? 0);
        $state[$prefix . 'Guarding'] = (bool) ($values['actorGuarding'] ?? $state[$prefix . 'Guarding'] ?? false);
        $state[$targetPrefix . 'Guarding'] = (bool) ($values['targetGuarding'] ?? $state[$targetPrefix . 'Guarding'] ?? false);
        $state[$prefix . 'Efectos'] = is_array($values['actorEffects'] ?? null) ? $values['actorEffects'] : ($state[$prefix . 'Efectos'] ?? []);
        $state[$targetPrefix . 'Efectos'] = is_array($values['targetEffects'] ?? null) ? $values['targetEffects'] : ($state[$targetPrefix . 'Efectos'] ?? []);
        $state['bonificadorPartida'] = is_array($values['modifier'] ?? null) ? $values['modifier'] : ($state['bonificadorPartida'] ?? []);
        $state[$prefix . 'CargasGastadas'] = (int) ($values['actorSpentCharges'] ?? $state[$prefix . 'CargasGastadas'] ?? 0);
        $state['ultimoTipoAtaque'] = (string) ($values['lastAttackType'] ?? $state['ultimoTipoAtaque'] ?? 'basico');
        $state[$prefix . 'ActiveEffects'] = $this->combatRulesEngine->activeEffects(is_array($state[$prefix . 'Efectos'] ?? null) ? $state[$prefix . 'Efectos'] : []);
        $state[$targetPrefix . 'ActiveEffects'] = $this->combatRulesEngine->activeEffects(is_array($state[$targetPrefix . 'Efectos'] ?? null) ? $state[$targetPrefix . 'Efectos'] : []);

        return $state;
    }
}
