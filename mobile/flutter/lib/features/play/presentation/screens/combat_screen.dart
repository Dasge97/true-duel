import 'package:flutter/material.dart';

import '../../../../core/theme/duel_theme.dart';
import '../controllers/combat_controller.dart';
import '../controllers/result_controller.dart';
import 'result_screen.dart';

class CombatScreen extends StatefulWidget {
  const CombatScreen({
    super.key,
    required this.controller,
    required this.onContinue,
    required this.onPlayAgain,
  });

  final CombatController controller;
  final VoidCallback onContinue;
  final VoidCallback onPlayAgain;

  @override
  State<CombatScreen> createState() => _CombatScreenState();
}

class _CombatScreenState extends State<CombatScreen> {
  bool _showLog = false;

  @override
  void initState() {
    super.initState();
    widget.controller.load();
  }

  Future<void> _submit() async {
    final settlement = await widget.controller.submitTurn();
    if (!mounted || settlement == null) return;
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ResultScreen(
          controller: ResultController.fromSettlement(settlement),
          onContinue: widget.onContinue,
          onPlayAgain: widget.onPlayAgain,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: AnimatedBuilder(
        animation: widget.controller,
        builder: (context, _) {
          final c = widget.controller;
          final duel = context.duel;

          if (c.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          return Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [duel.bg, duel.bgSoft],
              ),
            ),
            child: SafeArea(
              child: Stack(
                children: [
                  // Fondo radial decorativo
                  Positioned.fill(
                    child: IgnorePointer(
                      child: Opacity(
                        opacity: 0.18,
                        child: DecoratedBox(
                          decoration: BoxDecoration(
                            gradient: RadialGradient(
                              center: const Alignment(0, 0.2),
                              radius: 1,
                              colors: [
                                duel.neonGreen.withOpacity(0.12),
                                Colors.transparent,
                              ],
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                  Column(
                    children: [
                      // Header
                      Padding(
                        padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
                        child: Row(
                          children: [
                            _TurnPill(turn: c.currentTurn),
                            const Spacer(),
                            if (c.lastFeedback.isNotEmpty || c.errorCode != null)
                              Text(
                                c.errorCode != null
                                    ? 'Error: ${c.errorCode}'
                                    : c.lastFeedback,
                                style: TextStyle(
                                  color: c.errorCode != null
                                      ? duel.danger
                                      : duel.textSecondary,
                                  fontSize: 11,
                                ),
                              ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 10),
                      // Equipo enemigo
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        child: Row(
                          children: List.generate(c.enemyChampions.length, (i) {
                            final champ = c.enemyChampions[i];
                            final isBeingTargeted = c.pendingActions.values
                                .any((a) => a.targetSlot == i);
                            return Expanded(
                              child: Padding(
                                padding: EdgeInsets.only(
                                  left: i == 0 ? 0 : 4,
                                  right: i == c.enemyChampions.length - 1
                                      ? 0
                                      : 4,
                                ),
                                child: _EnemyChampionCard(
                                  champ: champ,
                                  slot: i,
                                  isTargeted: isBeingTargeted,
                                  onTap: champ.isAlive && !c.inputLocked
                                      ? () => c.setTarget(i)
                                      : null,
                                ),
                              ),
                            );
                          }),
                        ),
                      ),
                      const SizedBox(height: 6),
                      // Separador VS
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            'VS',
                            style: TextStyle(
                              fontSize: 22,
                              fontWeight: FontWeight.w900,
                              color: duel.textSecondary.withOpacity(0.3),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 6),
                      // Equipo del jugador
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        child: Row(
                          children: List.generate(c.playerChampions.length, (i) {
                            final champ = c.playerChampions[i];
                            final pending = c.pendingActions[i];
                            final isActive = c.activeSlot == i;
                            return Expanded(
                              child: Padding(
                                padding: EdgeInsets.only(
                                  left: i == 0 ? 0 : 4,
                                  right: i == c.playerChampions.length - 1
                                      ? 0
                                      : 4,
                                ),
                                child: _PlayerChampionCard(
                                  champ: champ,
                                  slot: i,
                                  isActive: isActive,
                                  pending: pending,
                                  onTap: champ.isAlive && !c.inputLocked
                                      ? () => c.selectSlot(i)
                                      : null,
                                  onClearAction: pending != null && !c.inputLocked
                                      ? () => c.clearSlotAction(i)
                                      : null,
                                ),
                              ),
                            );
                          }),
                        ),
                      ),
                      const SizedBox(height: 10),
                      // Botones de acción (para el slot activo)
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        child: _ActionPicker(
                          controller: c,
                          onAction: (action) => c.assignAction(action),
                        ),
                      ),
                      const SizedBox(height: 10),
                      // Botón finalizar turno
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        child: SizedBox(
                          width: double.infinity,
                          height: 52,
                          child: ElevatedButton(
                            onPressed: c.isReadyToSubmit ? _submit : null,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: duel.neonGreen,
                              foregroundColor: Colors.black,
                              disabledBackgroundColor:
                                  duel.neonGreen.withOpacity(0.3),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(14),
                              ),
                            ),
                            child: const Text(
                              'FINALIZAR TURNO',
                              style: TextStyle(
                                fontWeight: FontWeight.w900,
                                fontSize: 15,
                                letterSpacing: 1.2,
                              ),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 8),
                    ],
                  ),
                  // Log deslizable
                  AnimatedPositioned(
                    duration: const Duration(milliseconds: 240),
                    curve: Curves.easeOutCubic,
                    right: _showLog ? 10 : -220,
                    top: 200,
                    child: SizedBox(
                      width: 230,
                      child: _CombatLogCard(events: c.recentEvents),
                    ),
                  ),
                  Positioned(
                    right: 0,
                    top: 265,
                    child: GestureDetector(
                      onTap: () => setState(() => _showLog = !_showLog),
                      child: Container(
                        width: 34,
                        height: 56,
                        decoration: BoxDecoration(
                          color: context.duel.surface,
                          borderRadius: const BorderRadius.only(
                            topLeft: Radius.circular(14),
                            bottomLeft: Radius.circular(14),
                          ),
                          border: Border.all(color: context.duel.border),
                        ),
                        child: Icon(
                          _showLog
                              ? Icons.chevron_right
                              : Icons.receipt_long_outlined,
                          size: 16,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

// ─── Enemy champion card ───────────────────────────────────────────────────────

class _EnemyChampionCard extends StatelessWidget {
  const _EnemyChampionCard({
    required this.champ,
    required this.slot,
    required this.isTargeted,
    required this.onTap,
  });

  final CombatChampion champ;
  final int slot;
  final bool isTargeted;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final duel = context.duel;
    final isDead = !champ.isAlive;
    final progress = (champ.hp / 100).clamp(0.0, 1.0);

    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: duel.surface.withOpacity(isDead ? 0.4 : 0.9),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isTargeted
                ? duel.danger.withOpacity(0.9)
                : duel.danger.withOpacity(isDead ? 0.15 : 0.35),
            width: isTargeted ? 2 : 1,
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    champ.displayName,
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: isDead
                          ? duel.textSecondary.withOpacity(0.4)
                          : duel.textPrimary,
                    ),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                if (isTargeted)
                  Icon(Icons.gps_fixed, size: 10, color: duel.danger),
              ],
            ),
            const SizedBox(height: 4),
            Text(
              isDead ? 'KO' : '${champ.hp}',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w900,
                color: isDead
                    ? duel.textSecondary.withOpacity(0.4)
                    : duel.danger,
              ),
            ),
            const SizedBox(height: 4),
            ClipRRect(
              borderRadius: BorderRadius.circular(6),
              child: LinearProgressIndicator(
                value: isDead ? 0 : progress,
                minHeight: 6,
                backgroundColor: duel.surfaceAlt,
                valueColor: AlwaysStoppedAnimation<Color>(
                  isDead ? duel.surfaceAlt : duel.danger,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ─── Player champion card ──────────────────────────────────────────────────────

class _PlayerChampionCard extends StatelessWidget {
  const _PlayerChampionCard({
    required this.champ,
    required this.slot,
    required this.isActive,
    required this.pending,
    required this.onTap,
    required this.onClearAction,
  });

  final CombatChampion champ;
  final int slot;
  final bool isActive;
  final SlotAction? pending;
  final VoidCallback? onTap;
  final VoidCallback? onClearAction;

  @override
  Widget build(BuildContext context) {
    final duel = context.duel;
    final isDead = !champ.isAlive;
    final progress = (champ.hp / 100).clamp(0.0, 1.0);

    Color borderColor;
    if (isDead) {
      borderColor = duel.border.withOpacity(0.3);
    } else if (isActive) {
      borderColor = duel.neonGreen;
    } else if (pending != null) {
      borderColor = _actionColor(pending!.action).withOpacity(0.6);
    } else {
      borderColor = duel.border;
    }

    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: duel.surface.withOpacity(isDead ? 0.4 : 0.92),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: borderColor,
            width: isActive ? 2 : 1,
          ),
          boxShadow: isActive
              ? [
                  BoxShadow(
                    color: duel.neonGreen.withOpacity(0.25),
                    blurRadius: 8,
                    spreadRadius: 1,
                  ),
                ]
              : null,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              champ.displayName,
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w700,
                color:
                    isDead ? duel.textSecondary.withOpacity(0.4) : duel.textPrimary,
              ),
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: 2),
            Text(
              isDead ? 'KO' : '${champ.hp}',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w900,
                color: isDead
                    ? duel.textSecondary.withOpacity(0.4)
                    : Colors.indigoAccent,
              ),
            ),
            const SizedBox(height: 4),
            ClipRRect(
              borderRadius: BorderRadius.circular(6),
              child: LinearProgressIndicator(
                value: isDead ? 0 : progress,
                minHeight: 6,
                backgroundColor: duel.surfaceAlt,
                valueColor: AlwaysStoppedAnimation<Color>(
                  isDead ? duel.surfaceAlt : Colors.indigoAccent,
                ),
              ),
            ),
            const SizedBox(height: 4),
            // Cargas y acción asignada
            if (!isDead) ...[
              Row(
                children: [
                  Icon(Icons.bolt_outlined, size: 10, color: duel.warning),
                  const SizedBox(width: 2),
                  Text(
                    '${champ.charges}',
                    style: TextStyle(fontSize: 10, color: duel.warning),
                  ),
                  const Spacer(),
                  if (pending != null)
                    GestureDetector(
                      onTap: onClearAction,
                      child: _ActionBadge(action: pending!),
                    ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }

  Color _actionColor(String action) {
    switch (action) {
      case 'attack':
        return const Color(0xFFC20B1A);
      case 'defend':
        return const Color(0xFF2455D9);
      case 'special':
        return const Color(0xFF8B5CF6);
      default:
        return Colors.grey;
    }
  }
}

class _ActionBadge extends StatelessWidget {
  const _ActionBadge({required this.action});

  final SlotAction action;

  @override
  Widget build(BuildContext context) {
    final duel = context.duel;
    final color = _color(action.action);
    final label = _label();
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
      decoration: BoxDecoration(
        color: color.withOpacity(0.2),
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: color.withOpacity(0.5)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 9,
              fontWeight: FontWeight.w800,
              color: color,
            ),
          ),
          Icon(Icons.close, size: 9, color: duel.textSecondary),
        ],
      ),
    );
  }

  String _label() {
    final abbr = action.action == 'attack'
        ? 'ATK'
        : action.action == 'defend'
            ? 'DEF'
            : 'SPE';
    if (action.action == 'defend' || action.targetSlot == null) return abbr;
    return '$abbr→E${action.targetSlot}';
  }

  Color _color(String act) {
    switch (act) {
      case 'attack':
        return const Color(0xFFC20B1A);
      case 'defend':
        return const Color(0xFF2455D9);
      case 'special':
        return const Color(0xFF8B5CF6);
      default:
        return Colors.grey;
    }
  }
}

// ─── Action picker ─────────────────────────────────────────────────────────────

class _ActionPicker extends StatelessWidget {
  const _ActionPicker({
    required this.controller,
    required this.onAction,
  });

  final CombatController controller;
  final ValueChanged<String> onAction;

  @override
  Widget build(BuildContext context) {
    final c = controller;
    final duel = context.duel;
    final activeSlot = c.activeSlot;
    final activeChamp =
        (activeSlot != null && activeSlot < c.playerChampions.length)
            ? c.playerChampions[activeSlot]
            : null;
    final disabled = c.inputLocked || activeChamp == null || !activeChamp.isAlive;
    final canSpecial = !disabled && activeChamp.charges >= 2;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (activeChamp != null && activeChamp.isAlive)
          Padding(
            padding: const EdgeInsets.only(bottom: 6),
            child: Text(
              'Acción para ${activeChamp.displayName}',
              style: TextStyle(color: duel.textSecondary, fontSize: 11),
            ),
          ),
        Row(
          children: [
            Expanded(
              child: _ActionButton(
                label: 'ATTACK',
                icon: Icons.sports_martial_arts,
                onPressed: disabled ? null : () => onAction('attack'),
                color: const Color(0xFFC20B1A),
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: _ActionButton(
                label: 'DEFEND',
                icon: Icons.shield_outlined,
                onPressed: disabled ? null : () => onAction('defend'),
                color: const Color(0xFF2455D9),
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: _ActionButton(
                label: 'SPECIAL',
                icon: Icons.bolt_outlined,
                onPressed: (disabled || !canSpecial) ? null : () => onAction('special'),
                color: const Color(0xFF8B5CF6),
                subtitle: canSpecial
                    ? null
                    : 'Req. 2⚡',
              ),
            ),
          ],
        ),
      ],
    );
  }
}

// ─── Shared widgets ────────────────────────────────────────────────────────────

class _TurnPill extends StatelessWidget {
  const _TurnPill({required this.turn});
  final int turn;

  @override
  Widget build(BuildContext context) {
    final duel = context.duel;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
      decoration: BoxDecoration(
        color: duel.surface,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: duel.border),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 7,
            height: 7,
            decoration: BoxDecoration(
              color: Colors.indigoAccent,
              borderRadius: BorderRadius.circular(7),
            ),
          ),
          const SizedBox(width: 7),
          Text('Turno $turn',
              style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
        ],
      ),
    );
  }
}

class _ActionButton extends StatelessWidget {
  const _ActionButton({
    required this.label,
    required this.icon,
    required this.onPressed,
    required this.color,
    this.subtitle,
  });

  final String label;
  final IconData icon;
  final VoidCallback? onPressed;
  final Color color;
  final String? subtitle;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 80,
      child: ElevatedButton(
        onPressed: onPressed,
        style: ElevatedButton.styleFrom(
          backgroundColor: color,
          foregroundColor: Colors.white,
          disabledBackgroundColor: color.withOpacity(0.35),
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 8),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 20),
            const SizedBox(height: 4),
            Text(label,
                style: const TextStyle(
                    fontWeight: FontWeight.w800, fontSize: 11)),
            if (subtitle != null) ...[
              const SizedBox(height: 2),
              Text(subtitle!,
                  style: const TextStyle(
                      fontSize: 9, color: Colors.white70),
                  textAlign: TextAlign.center),
            ],
          ],
        ),
      ),
    );
  }
}

class _CombatLogCard extends StatelessWidget {
  const _CombatLogCard({required this.events});
  final List<Map<String, dynamic>> events;

  @override
  Widget build(BuildContext context) {
    final duel = context.duel;
    final lines = events.isEmpty
        ? ['¡Combate iniciado!']
        : events.reversed
            .take(3)
            .map((e) {
              final pe = (e['playerEvents'] as List<dynamic>? ?? []);
              if (pe.isEmpty) return '';
              final parts = pe.map((ev) {
                final m = ev as Map<String, dynamic>;
                final act = m['action'] as String? ?? '?';
                final dmg = m['damage'] as int? ?? 0;
                final ts = m['targetSlot'];
                if (act == 'defend') return 'S${m['actorSlot']}:DEF';
                return 'S${m['actorSlot']}→E$ts $dmg dmg';
              }).join(' | ');
              return 'T${e['turn']}: $parts';
            })
            .where((s) => s.isNotEmpty)
            .toList();

    return Container(
      key: const Key('combat-recent-log'),
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: duel.surface.withOpacity(0.93),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: duel.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('LOG',
              style: TextStyle(
                  color: duel.textSecondary,
                  fontWeight: FontWeight.w700,
                  fontSize: 11)),
          const SizedBox(height: 6),
          ...lines.map(
            (line) => Padding(
              padding: const EdgeInsets.only(bottom: 4),
              child: Container(
                width: double.infinity,
                padding:
                    const EdgeInsets.symmetric(horizontal: 6, vertical: 5),
                decoration: BoxDecoration(
                  color: duel.surfaceAlt,
                  borderRadius: BorderRadius.circular(7),
                ),
                child: Text(line,
                    style: TextStyle(
                        color: duel.textSecondary, fontSize: 10)),
              ),
            ),
          ),
          if (lines.isEmpty)
            Text('Sin eventos',
                style: TextStyle(color: duel.textSecondary, fontSize: 10)),
        ],
      ),
    );
  }
}
