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

  Future<void> _act(String action) async {
    final settlement = await widget.controller.act(action);
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

          final canSpecial = c.playerCharges >= 2;
          final specialDisabled = c.inputLocked || !canSpecial;
          final combatError = c.errorCode;

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
                  Positioned.fill(
                    child: IgnorePointer(
                      child: Opacity(
                        opacity: 0.18,
                        child: DecoratedBox(
                          decoration: BoxDecoration(
                            gradient: RadialGradient(
                              center: const Alignment(0, 0.2),
                              radius: 1,
                              colors: [duel.neonGreen.withOpacity(0.12), Colors.transparent],
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 6, 16, 12),
                    child: Column(
                      children: [
                        const SizedBox.shrink(key: Key('combat-hud')),
                        _BattleSideCard(
                          title: 'ENEMY',
                          name: c.enemyName,
                          hp: c.enemyHp,
                          maxHp: 100,
                          defending: c.rivalDefending,
                          charges: 0,
                          lastAction: c.lastRivalAction,
                          hostile: true,
                        ),
                        const SizedBox(height: 18),
                        _TurnPill(turn: c.currentTurn),
                        Expanded(
                          child: Center(
                            child: Text(
                              'VS',
                              style: TextStyle(
                                fontSize: 58,
                                fontWeight: FontWeight.w900,
                                color: duel.textSecondary.withOpacity(0.25),
                              ),
                            ),
                          ),
                        ),
                        _BattleSideCard(
                          title: 'YOU',
                          name: c.championName,
                          hp: c.playerHp,
                          maxHp: 100,
                          defending: c.playerDefending,
                          charges: c.playerCharges,
                          lastAction: null,
                          hostile: false,
                        ),
                        const SizedBox(height: 12),
                        Row(
                          children: [
                            Expanded(
                              child: _ActionButton(
                                label: 'ATTACK',
                                icon: Icons.sports_martial_arts,
                                onPressed: c.inputLocked ? null : () => _act('attack'),
                                color: const Color(0xFFC20B1A),
                              ),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: _ActionButton(
                                label: 'DEFEND',
                                icon: Icons.shield_outlined,
                                onPressed: c.inputLocked ? null : () => _act('defend'),
                                color: const Color(0xFF2455D9),
                              ),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: _ActionButton(
                                label: 'SPECIAL',
                                icon: Icons.bolt_outlined,
                                onPressed: specialDisabled ? null : () => _act('special'),
                                color: const Color(0xFF2A3445),
                                subtitle: canSpecial ? null : 'Requiere 2 cargas',
                              ),
                            ),
                          ],
                        ),
                        if (c.lastFeedback.isNotEmpty || combatError != null) ...[
                          const SizedBox(height: 8),
                          Text(
                            combatError != null ? 'Error: $combatError' : c.lastFeedback,
                            style: TextStyle(color: combatError != null ? duel.danger : duel.textSecondary, fontSize: 12),
                            textAlign: TextAlign.center,
                          ),
                        ],
                      ],
                    ),
                  ),
                  AnimatedPositioned(
                    duration: const Duration(milliseconds: 240),
                    curve: Curves.easeOutCubic,
                    right: _showLog ? 10 : -220,
                    top: 250,
                    child: SizedBox(
                      width: 230,
                      child: _CombatLogCard(
                        events: c.recentEvents,
                      ),
                    ),
                  ),
                  Positioned(
                    right: 0,
                    top: 318,
                    child: GestureDetector(
                      onTap: () => setState(() => _showLog = !_showLog),
                      child: Container(
                        width: 34,
                        height: 88,
                        decoration: BoxDecoration(
                          color: duel.surface,
                          borderRadius: const BorderRadius.only(
                            topLeft: Radius.circular(14),
                            bottomLeft: Radius.circular(14),
                          ),
                          border: Border.all(color: duel.border),
                        ),
                        child: Icon(
                          _showLog ? Icons.chevron_right : Icons.receipt_long_outlined,
                          size: 18,
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

class _BattleSideCard extends StatelessWidget {
  const _BattleSideCard({
    required this.title,
    required this.name,
    required this.hp,
    required this.maxHp,
    required this.defending,
    required this.charges,
    required this.lastAction,
    required this.hostile,
  });

  final String title;
  final String name;
  final int hp;
  final int maxHp;
  final bool defending;
  final int charges;
  final String? lastAction;
  final bool hostile;

  @override
  Widget build(BuildContext context) {
    final duel = context.duel;
    final hpValue = hp < 0 ? 0 : hp;
    final maxValue = maxHp <= 0 ? 1 : maxHp;
    final progress = (hpValue / maxValue).clamp(0.0, 1.0);

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: duel.surface.withOpacity(0.85),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: hostile ? duel.danger.withOpacity(0.4) : duel.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text(title, style: TextStyle(color: hostile ? duel.danger : duel.neonGreen, fontWeight: FontWeight.w700)),
              const Spacer(),
              if (lastAction != null) ...[
                Text('Last Action', style: TextStyle(color: duel.textSecondary, fontSize: 12)),
                const SizedBox(width: 6),
                Text(lastAction!.isEmpty ? '-' : lastAction!, style: const TextStyle(fontWeight: FontWeight.w700)),
              ],
            ],
          ),
          const SizedBox(height: 6),
          Text(name, style: const TextStyle(fontSize: 30, fontWeight: FontWeight.w800)),
          const SizedBox(height: 10),
          Row(
            children: [
              Icon(Icons.favorite_border, size: 16, color: hostile ? duel.danger : duel.textPrimary),
              const SizedBox(width: 6),
              Text('$hpValue', style: const TextStyle(fontWeight: FontWeight.w700)),
              Text(' / $maxValue', style: TextStyle(color: duel.textSecondary)),
            ],
          ),
          const SizedBox(height: 8),
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: LinearProgressIndicator(
              value: progress,
              minHeight: 10,
              backgroundColor: duel.surfaceAlt,
              valueColor: AlwaysStoppedAnimation<Color>(hostile ? duel.danger : Colors.indigoAccent),
            ),
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Icon(Icons.shield_outlined, size: 16, color: duel.textSecondary),
              const SizedBox(width: 4),
              Text('Defense: ${defending ? 1 : 0}', style: TextStyle(color: duel.textSecondary)),
              const SizedBox(width: 14),
              Icon(Icons.bolt_outlined, size: 16, color: duel.warning),
              const SizedBox(width: 4),
              Text('Charges: $charges', style: TextStyle(color: duel.textSecondary)),
            ],
          ),
        ],
      ),
    );
  }
}

class _TurnPill extends StatelessWidget {
  const _TurnPill({required this.turn});

  final int turn;

  @override
  Widget build(BuildContext context) {
    final duel = context.duel;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 10),
      decoration: BoxDecoration(
        color: duel.surface,
        borderRadius: BorderRadius.circular(28),
        border: Border.all(color: duel.border),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 8,
            height: 8,
            decoration: BoxDecoration(color: Colors.indigoAccent, borderRadius: BorderRadius.circular(8)),
          ),
          const SizedBox(width: 8),
          Text('Turn $turn', style: const TextStyle(fontWeight: FontWeight.w700)),
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
    final disabled = onPressed == null;
    return SizedBox(
      height: 102,
      child: ElevatedButton(
        onPressed: onPressed,
        style: ElevatedButton.styleFrom(
          backgroundColor: color,
          foregroundColor: Colors.white,
          disabledBackgroundColor: color.withOpacity(0.5),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 10),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 24),
            const SizedBox(height: 8),
            Text(label, style: const TextStyle(fontWeight: FontWeight.w800)),
            if (subtitle != null) ...[
              const SizedBox(height: 4),
              Text(
                subtitle!,
                style: TextStyle(fontSize: 11, color: disabled ? Colors.white70 : Colors.amberAccent),
                textAlign: TextAlign.center,
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _CombatLogCard extends StatelessWidget {
  const _CombatLogCard({required this.events});

  final List<String> events;

  @override
  Widget build(BuildContext context) {
    final duel = context.duel;
    final lines = events.isEmpty ? const ['Combat started!'] : events;
    return Container(
      key: const Key('combat-recent-log'),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: duel.surface.withOpacity(0.92),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: duel.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('COMBAT LOG', style: TextStyle(color: duel.textSecondary, fontWeight: FontWeight.w700)),
          const SizedBox(height: 8),
          ...lines.take(4).map(
                (event) => Padding(
                  padding: const EdgeInsets.only(bottom: 6),
                  child: Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
                    decoration: BoxDecoration(
                      color: duel.surfaceAlt,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(event, style: TextStyle(color: duel.textSecondary)),
                  ),
                ),
              ),
        ],
      ),
    );
  }
}
