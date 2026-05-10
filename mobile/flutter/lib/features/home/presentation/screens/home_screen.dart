import 'dart:math' as math;

import 'package:flutter/material.dart';
import '../../../../core/theme/duel_theme.dart';
import '../../../../core/widgets/async_state_card.dart';
import '../../../../core/widgets/td_button.dart';
import '../controllers/home_controller.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({
    super.key,
    required this.controller,
    required this.onPlay,
    this.onRankingTap,
  });

  final HomeController controller;
  final VoidCallback onPlay;
  final VoidCallback? onRankingTap;

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  String? _claimingMissionId;

  @override
  void initState() {
    super.initState();
    widget.controller.load();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: widget.controller,
      builder: (context, _) {
        final c = widget.controller;
        final td = context.td;

        if (c.isLoading) return const Center(child: AsyncStateCard.loading());
        if (!c.hasAnyData) {
          return Center(
            child: AsyncStateCard.error(
              message: 'Error cargando Home',
              onRetry: c.load,
            ),
          );
        }

        final profile = c.profile;
        final missions = c.missions;
        final xpTotal = profile?.experienceTotal ?? 0;
        final xpToNext = profile?.experienceToNextLevel ?? 0;
        final xpDenominator = xpTotal + xpToNext;
        final xpProgress =
            xpDenominator > 0 ? (xpTotal / xpDenominator).clamp(0.0, 1.0) : 0.0;
        final level = profile?.level ?? 0;
        final mmr = profile?.mmr ?? 0;
        final rank = profile?.rank ?? 'HIERRO';


        return RefreshIndicator(
          color: td.gold,
          backgroundColor: td.card,
          onRefresh: c.load,
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(14, 10, 14, 24),
            children: [
              // ── 1. Tarjeta JUGAR ─────────────────────────────────────────
              _PlayCard(onPlay: widget.onPlay),

              const SizedBox(height: 14),

              // ── 2. Barra XP ───────────────────────────────────────────────
              _XpBar(level: level, xpTotal: xpTotal, xpToNext: xpToNext, xpProgress: xpProgress),

              const SizedBox(height: 10),

              // ── 3. Preview ranking ────────────────────────────────────────
              _RankingCard(rank: rank, mmr: mmr, onTap: widget.onRankingTap),

              const SizedBox(height: 20),

              // ── 4. Misiones diarias ───────────────────────────────────────
              if (missions == null)
                AsyncStateCard.error(
                  message: 'Misiones no disponibles',
                  onRetry: c.load,
                )
              else ...[
                _SectionHeader(
                  title: 'MISIONES DIARIAS',
                  trailing: '${missions.completed} / ${missions.total}',
                ),
                const SizedBox(height: 10),
                ...missions.missions.map((m) {
                  final progress =
                      m.target <= 0 ? 0.0 : (m.progress / m.target).clamp(0.0, 1.0);
                  final canClaim = m.completed && !m.claimed;
                  final isClaiming = _claimingMissionId == m.missionId;
                  return _MissionCard(
                    mission: m,
                    progress: progress,
                    canClaim: canClaim,
                    isClaiming: isClaiming,
                    onClaim: () async {
                      setState(() => _claimingMissionId = m.missionId);
                      try {
                        await c.claim(m.missionId);
                      } finally {
                        if (mounted) setState(() => _claimingMissionId = null);
                      }
                    },
                  );
                }),
              ],

              const SizedBox(height: 20),

              // ── 5. Historial de partidas ──────────────────────────────────
              _SectionHeader(
                title: 'ÚLTIMAS PARTIDAS',
                trailing: 'VER TODAS',
              ),
              const SizedBox(height: 10),

              if (c.history.isEmpty && c.historyError != null)
                AsyncStateCard.error(
                  message: 'Historial no disponible',
                  onRetry: c.load,
                )
              else if (c.history.isEmpty)
                const AsyncStateCard.empty(message: 'Todavía no hay partidas recientes')
              else
                ...c.history.map((h) => _HistoryCard(entry: h)),
            ],
          ),
        );
      },
    );
  }
}

// ── Painter de franjas diagonales ──────────────────────────────────────────────

class _DiagonalStripesPainter extends CustomPainter {
  const _DiagonalStripesPainter();

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = const Color(0x06FFFFFF)
      ..strokeWidth = 1.0
      ..style = PaintingStyle.stroke;

    const spacing = 9.0;
    final diag = size.width + size.height;

    for (double offset = -diag; offset < diag; offset += spacing) {
      canvas.drawLine(
        Offset(offset, 0),
        Offset(offset + size.height, size.height),
        paint,
      );
    }
  }

  @override
  bool shouldRepaint(_DiagonalStripesPainter oldDelegate) => false;
}

// ── Tarjeta JUGAR ──────────────────────────────────────────────────────────────

class _PlayCard extends StatelessWidget {
  const _PlayCard({required this.onPlay});
  final VoidCallback onPlay;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return ClipRect(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 18),
        decoration: BoxDecoration(
          gradient: const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFF1B1A14), Color(0xFF14141A)],
          ),
          border: Border.all(color: const Color(0x668E6A2C)),
        ),
        child: Stack(
          children: [
            Positioned.fill(
              child: CustomPaint(painter: const _DiagonalStripesPainter()),
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'VER MODOS DE JUEGO',
                  style: TdText.eyebrow(color: td.gold),
                ),
                const SizedBox(height: 14),
                Row(
                  children: [
                    _StatPill(label: 'RACHA', value: '--'),
                    const SizedBox(width: 8),
                    _StatPill(label: 'T. PROMEDIO', value: '--'),
                  ],
                ),
                const SizedBox(height: 14),
                TdButton(
                  label: 'Jugar',
                  onPressed: onPlay,
                  variant: TdButtonVariant.primary,
                  fontSize: 28,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _StatPill extends StatelessWidget {
  const _StatPill({required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: td.surface,
        border: Border.all(color: td.border),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(label, style: TdText.mono(9, color: td.muted)),
          const SizedBox(width: 4),
          Text(value, style: TdText.mono(12, color: td.fg, weight: FontWeight.w700)),
        ],
      ),
    );
  }
}

// ── Barra XP ───────────────────────────────────────────────────────────────────

class _XpBar extends StatelessWidget {
  const _XpBar({
    required this.level,
    required this.xpTotal,
    required this.xpToNext,
    required this.xpProgress,
  });

  final int level;
  final int xpTotal;
  final int xpToNext;
  final double xpProgress;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final xpMax = xpTotal + xpToNext;
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: td.card,
        border: Border.all(color: td.border2),
      ),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            crossAxisAlignment: CrossAxisAlignment.baseline,
            textBaseline: TextBaseline.alphabetic,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.baseline,
                textBaseline: TextBaseline.alphabetic,
                children: [
                  Text('NIVEL', style: TdText.eyebrow(color: td.muted)),
                  const SizedBox(width: 6),
                  Text('$level', style: TdText.display(28, color: td.gold)),
                ],
              ),
              Text(
                '$xpTotal / $xpMax XP',
                style: TdText.mono(11, color: td.muted),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Container(
            height: 6,
            decoration: BoxDecoration(
              color: const Color(0xFF0E0E12),
              border: Border.all(color: td.border),
            ),
            child: Align(
              alignment: Alignment.centerLeft,
              child: FractionallySizedBox(
                widthFactor: xpProgress,
                child: Container(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: [td.goldDeep, td.gold],
                    ),
                  ),
                ),
              ),
            ),
          ),
          const SizedBox(height: 6),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('XP TOTAL', style: TdText.mono(9, color: td.muted)),
              Text(
                '· $xpToNext PARA NIVEL ${level + 1}',
                style: TdText.mono(9, color: td.muted),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

// ── Tarjeta Ranking ────────────────────────────────────────────────────────────

class _RankingCard extends StatelessWidget {
  const _RankingCard({
    required this.rank,
    required this.mmr,
    this.onTap,
  });

  final String rank;
  final int mmr;
  final VoidCallback? onTap;

  String _abbrev(String r) {
    final parts = r.toUpperCase().split(' ');
    if (parts.length == 1) {
      return parts[0].substring(0, math.min(2, parts[0].length));
    }
    return '${parts[0][0]}·${parts[1]}';
  }

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final abbrev = _abbrev(rank);

    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: td.card,
          border: Border.all(color: td.border2),
        ),
        child: Row(
          children: [
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: td.surface,
                border: Border.all(color: td.goldDeep),
              ),
              alignment: Alignment.center,
              child: Text(
                abbrev,
                style: TdText.mono(10, color: td.gold, weight: FontWeight.w700),
                textAlign: TextAlign.center,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'RANKING · ${rank.toUpperCase()}',
                    style: TdText.eyebrow(color: td.muted),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    '$mmr MMR',
                    style: TdText.mono(12, color: td.fg),
                  ),
                  const SizedBox(height: 6),
                  Container(
                    height: 4,
                    decoration: BoxDecoration(
                      color: const Color(0xFF0E0E12),
                      border: Border.all(color: td.border),
                    ),
                    child: Align(
                      alignment: Alignment.centerLeft,
                      child: FractionallySizedBox(
                        widthFactor: 0.3,
                        child: Container(
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: [td.goldDeep, td.gold],
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            Icon(Icons.chevron_right, color: td.muted, size: 20),
          ],
        ),
      ),
    );
  }
}

// ── Cabecera de sección ────────────────────────────────────────────────────────

class _SectionHeader extends StatelessWidget {
  const _SectionHeader({required this.title, required this.trailing});

  final String title;
  final String trailing;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      crossAxisAlignment: CrossAxisAlignment.baseline,
      textBaseline: TextBaseline.alphabetic,
      children: [
        Text(title, style: TdText.display(22, color: td.fg)),
        Text(trailing, style: TdText.mono(11, color: td.muted)),
      ],
    );
  }
}

// ── Tarjeta de misión ──────────────────────────────────────────────────────────

class _MissionCard extends StatelessWidget {
  const _MissionCard({
    required this.mission,
    required this.progress,
    required this.canClaim,
    required this.isClaiming,
    required this.onClaim,
  });

  final dynamic mission;
  final double progress;
  final bool canClaim;
  final bool isClaiming;
  final VoidCallback onClaim;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return Container(
      margin: const EdgeInsets.only(bottom: 6),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: td.card,
        border: Border.all(color: td.border2),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      mission.title as String,
                      style: TdText.ui(13, weight: FontWeight.w600, color: td.fg),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      '${mission.progress}/${mission.target}',
                      style: TdText.mono(10, color: td.muted),
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 3),
                decoration: BoxDecoration(
                  color: td.surface,
                  border: Border.all(color: td.border),
                ),
                child: Text(
                  '+${mission.rewardCoins}',
                  style: TdText.mono(
                    11,
                    color: td.gold,
                    weight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          // Progress bar
          Container(
            height: 6,
            decoration: BoxDecoration(
              color: const Color(0xFF0E0E12),
              border: Border.all(color: td.border),
            ),
            child: Align(
              alignment: Alignment.centerLeft,
              child: FractionallySizedBox(
                widthFactor: progress,
                child: Container(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: [td.goldDeep, td.gold],
                    ),
                  ),
                ),
              ),
            ),
          ),
          if (canClaim) ...[
            const SizedBox(height: 8),
            Align(
              alignment: Alignment.centerRight,
              child: GestureDetector(
                onTap: isClaiming ? null : onClaim,
                child: Opacity(
                  opacity: isClaiming ? 0.45 : 1.0,
                  child: isClaiming
                      ? SizedBox(
                          width: 14,
                          height: 14,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            valueColor: AlwaysStoppedAnimation(td.gold),
                          ),
                        )
                      : Text(
                          'RECLAMAR',
                          style: TdText.mono(
                            11,
                            color: td.gold,
                            weight: FontWeight.w700,
                          ),
                        ),
                ),
              ),
            ),
          ] else if (mission.claimed as bool) ...[
            const SizedBox(height: 8),
            Align(
              alignment: Alignment.centerRight,
              child: Text(
                'RECLAMADA',
                style: TdText.mono(10, color: td.muted),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

// ── Tarjeta de historial ───────────────────────────────────────────────────────

class _HistoryCard extends StatelessWidget {
  const _HistoryCard({required this.entry});

  final dynamic entry;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final isWin = (entry.result as String).toLowerCase() == 'win';
    final resultColor = isWin ? td.win : td.loss;
    final resultLabel = isWin ? 'W' : 'L';
    final deltaStr =
        (entry.mmrDelta as int) >= 0 ? '+${entry.mmrDelta}' : '${entry.mmrDelta}';

    return Container(
      margin: const EdgeInsets.only(bottom: 4),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: td.card,
        border: Border.all(color: td.border2),
      ),
      child: Row(
        children: [
          // Resultado W/L
          Container(
            width: 32,
            height: 32,
            decoration: BoxDecoration(
              color: resultColor.withAlpha(26),
              border: Border.all(color: resultColor),
            ),
            alignment: Alignment.center,
            child: Text(
              resultLabel,
              style: TdText.display(18, color: resultColor),
            ),
          ),
          const SizedBox(width: 10),
          // Info
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'vs ${entry.enemy}',
                  style: TdText.ui(13, weight: FontWeight.w600, color: td.fg),
                ),
                Text(
                  '${entry.turns} turnos',
                  style: TdText.mono(10, color: td.muted),
                ),
              ],
            ),
          ),
          // Delta MMR
          Text(
            '$deltaStr MMR',
            style: TdText.display(18, color: resultColor),
          ),
        ],
      ),
    );
  }
}
