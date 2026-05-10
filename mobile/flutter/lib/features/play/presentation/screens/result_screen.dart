import 'package:flutter/material.dart';

import '../../../../core/theme/duel_theme.dart';
import '../../../../core/widgets/td_button.dart';
import '../controllers/result_controller.dart';

class ResultScreen extends StatelessWidget {
  const ResultScreen({
    super.key,
    required this.controller,
    required this.onContinue,
    required this.onPlayAgain,
  });

  final ResultController controller;
  final VoidCallback onContinue;
  final VoidCallback onPlayAgain;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final c = controller;

    final isVictory = c.victory;
    final isDraw = c.result == 'draw';

    final resultColor = isDraw
        ? td.warn
        : (isVictory ? td.win : td.loss);
    final resultBadge = isDraw ? '=' : (isVictory ? 'W' : 'L');
    final resultEyebrow = isDraw
        ? 'RESULTADO · EMPATE'
        : (isVictory
              ? 'RESULTADO · TURNO ${c.turns}'
              : 'RESULTADO · TURNO ${c.turns}');
    final title = isDraw
        ? 'Empate épico'
        : (isVictory ? '¡Victoria legendaria!' : 'Derrota honorable');
    final subtitle = isDraw
        ? 'La batalla terminó igualada'
        : (isVictory ? 'Has dominado el duelo' : 'Toca volver más fuerte');

    final rewardSign = c.mmrDelta >= 0 ? '+' : '';

    return Scaffold(
      backgroundColor: const Color(0xFF0A0A0B),
      body: SafeArea(
        child: Column(
          children: [
            // ── Hero section (compact to keep performance rows in viewport) ─
            Container(
              width: double.infinity,
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
              decoration: BoxDecoration(
                gradient: RadialGradient(
                  center: Alignment.topCenter,
                  radius: 1.6,
                  colors: [
                    resultColor.withAlpha(40),
                    const Color(0xFF0A0A0B),
                  ],
                ),
                border: Border(
                  bottom: BorderSide(color: td.border),
                ),
              ),
              child: Row(
                children: [
                  // Badge
                  Container(
                    width: 48,
                    height: 48,
                    decoration: BoxDecoration(
                      color: const Color(0xFF0E0E12),
                      border: Border.all(color: resultColor, width: 2),
                    ),
                    child: Center(
                      child: Text(
                        resultBadge,
                        style: TdText.display(30, color: resultColor),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          resultEyebrow,
                          style: TdText.eyebrow(color: resultColor),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          title,
                          style: TdText.display(26),
                        ),
                        Text(
                          subtitle,
                          style: TdText.ui(12, color: td.fg2),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            // ── Scrollable body ───────────────────────────────────────
            Expanded(
              child: ListView(
                padding: const EdgeInsets.all(10),
                children: [
                  // Rewards header
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text('Recompensas', style: TdText.display(22)),
                    ],
                  ),
                  const SizedBox(height: 6),

                  // Rewards grid row 1
                  Row(
                    children: [
                      Expanded(
                        child: _RewardCard(
                          label: 'MMR Δ',
                          value: '$rewardSign${c.mmrDelta}',
                          valueColor:
                              c.mmrDelta >= 0 ? td.win : td.loss,
                        ),
                      ),
                      const SizedBox(width: 6),
                      Expanded(
                        child: _RewardCard(
                          label: 'XP',
                          value: '+${c.xp}',
                          valueColor: td.fg,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),

                  // Rewards grid row 2
                  Row(
                    children: [
                      Expanded(
                        child: _RewardCard(
                          label: 'MONEDAS',
                          value: '+${c.coins}',
                          valueColor: td.gold,
                          icon: Icons.toll_outlined,
                        ),
                      ),
                      const SizedBox(width: 6),
                      Expanded(
                        child: _RewardCard(
                          label: 'GEMAS',
                          value: c.gems == null
                              ? 'No disponible'
                              : '+${c.gems}',
                          valueColor: const Color(0xFF5A8EC0),
                          icon: Icons.diamond_outlined,
                        ),
                      ),
                    ],
                  ),

                  // SP row — solo ranked
                  if (c.isRanked) ...[
                    const SizedBox(height: 6),
                    _RewardCard(
                      label: 'SP COMPETITIVO',
                      value: c.spDelta == null
                          ? '--'
                          : (c.spDelta! >= 0 ? '+${c.spDelta}' : '${c.spDelta}'),
                      valueColor: c.spDelta == null
                          ? td.muted
                          : (c.spDelta! >= 0 ? td.win : td.loss),
                      icon: Icons.military_tech_outlined,
                    ),
                  ],

                  const SizedBox(height: 8),

                  // Performance header
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text('Rendimiento', style: TdText.display(22)),
                      Text(
                        '${c.turns} TURNOS',
                        style: TdText.mono(11, color: td.muted),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),

                  // Performance table
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 14,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      border: Border.all(color: td.border2),
                    ),
                    child: Column(
                      children: [
                        _StatRow(
                          label: 'Daño infligido',
                          value: '${c.damageDealt}',
                        ),
                        _StatRow(
                          label: 'Daño recibido',
                          value: '${c.damageTaken}',
                        ),
                        _StatRow(
                          label: 'Turnos',
                          value: '${c.turns}',
                        ),
                        _StatRow(
                          label: 'Ataques',
                          value: '${c.attackCount}',
                        ),
                        _StatRow(
                          label: 'Defensas',
                          value: '${c.defendCount}',
                        ),
                        _StatRow(
                          label: 'Especiales',
                          value: '${c.specialCount}',
                        ),
                        _StatRow(
                          label: 'Mitigación',
                          value: '${c.mitigationTotal}',
                          last: true,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            // ── Fixed bottom action bar ───────────────────────────────
            Container(
              padding: const EdgeInsets.fromLTRB(12, 12, 12, 14),
              decoration: BoxDecoration(
                color: const Color(0xFF0F0F13),
                border: Border(top: BorderSide(color: td.border2)),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: FilledButton(
                      onPressed: onContinue,
                      style: FilledButton.styleFrom(
                        backgroundColor: td.card2,
                        foregroundColor: td.fg,
                        shape: const RoundedRectangleBorder(),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                      ),
                      child: Text(
                        'Continuar',
                        style: TdText.display(18),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    flex: 1,
                    child: FilledButton(
                      onPressed: onPlayAgain,
                      style: FilledButton.styleFrom(
                        backgroundColor: td.gold,
                        foregroundColor: const Color(0xFF16110A),
                        shape: const RoundedRectangleBorder(),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                      ),
                      child: Text(
                        'Jugar de nuevo',
                        style: TdText.display(
                          18,
                          color: const Color(0xFF16110A),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Reward card ───────────────────────────────────────────────────────────────

class _RewardCard extends StatelessWidget {
  const _RewardCard({
    required this.label,
    required this.value,
    required this.valueColor,
    this.icon,
  });

  final String label;
  final String value;
  final Color valueColor;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
      decoration: BoxDecoration(
        color: td.card,
        border: Border.all(color: td.border2),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              if (icon != null) ...[
                Icon(icon, size: 11, color: valueColor),
                const SizedBox(width: 4),
              ],
              Text(
                label,
                style: TdText.eyebrow(color: td.muted),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: TdText.display(20, color: valueColor),
          ),
        ],
      ),
    );
  }
}

// ── Stat row ──────────────────────────────────────────────────────────────────

class _StatRow extends StatelessWidget {
  const _StatRow({
    required this.label,
    required this.value,
    this.last = false,
  });

  final String label;
  final String value;
  final bool last;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 6),
      decoration: last
          ? null
          : BoxDecoration(
              border: Border(
                bottom: BorderSide(color: td.border),
              ),
            ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: TdText.mono(11, color: td.muted),
          ),
          Text(
            value,
            style: TdText.display(20),
          ),
        ],
      ),
    );
  }
}
