import 'dart:math' as math;

import 'package:flutter/material.dart';
import '../../../../core/widgets/td_champion_glyph.dart';

import '../../../../core/theme/duel_theme.dart';
import '../../../../core/widgets/async_state_card.dart';
import '../../../mvp/data/mvp_api_repository.dart';
import '../controllers/profile_controller.dart';
import 'logros_screen.dart';
import 'titles_screen.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key, required this.controller});

  final ProfileController controller;

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
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
        if (c.isLoading) return const Center(child: AsyncStateCard.loading());
        if (c.error != null || c.profile == null) {
          return Center(child: AsyncStateCard.error(message: 'Error cargando perfil', onRetry: c.load));
        }
        return _ProfileBody(profile: c.profile!, onRefresh: c.load);
      },
    );
  }
}

class _ProfileBody extends StatelessWidget {
  const _ProfileBody({required this.profile, required this.onRefresh});

  final ProfileResult profile;
  final Future<void> Function() onRefresh;

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: onRefresh,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          _MegaHeader(profile: profile),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 20, 16, 0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _CombatStats(profile: profile),
                const SizedBox(height: 16),
                _XpCard(profile: profile),
                const SizedBox(height: 20),
                _MedallasSection(),
                const SizedBox(height: 20),
                _PersonajesMasUsados(),
                const SizedBox(height: 28),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ─── Header ───────────────────────────────────────────────────────────────────

class _MegaHeader extends StatelessWidget {
  const _MegaHeader({required this.profile});
  final ProfileResult profile;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final letter = profile.name.isNotEmpty ? profile.name[0].toUpperCase() : '?';

    return Container(
      clipBehavior: Clip.hardEdge,
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF1B1A14), Color(0xFF14141A)],
        ),
        border: Border(bottom: BorderSide(color: td.goldDeep)),
      ),
      child: Stack(
        children: [
          Positioned.fill(child: CustomPaint(painter: _ProfileStripesPainter())),
          Padding(
            padding: const EdgeInsets.fromLTRB(18, 22, 18, 22),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                TdChampionGlyph(initial: letter, size: 80, accent: td.gold),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'DUELISTA · NV ${profile.level}',
                        style: TdText.eyebrow(color: td.muted),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        profile.name.toUpperCase(),
                        style: TdText.display(28),
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          Text('SP ', style: TdText.eyebrow(color: td.muted)),
                          Text('${profile.sp}', style: TdText.display(22, color: td.fg)),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _ProfileStripesPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = const Color(0x08E5B567)
      ..strokeWidth = 1;
    for (double t = -size.height; t < size.width + size.height; t += 25) {
      canvas.drawLine(Offset(t, 0), Offset(t + size.height, size.height), paint);
    }
  }

  @override
  bool shouldRepaint(_ProfileStripesPainter old) => false;
}

// ─── Estadísticas de combate ──────────────────────────────────────────────────

class _StatsTabButton extends StatelessWidget {
  const _StatsTabButton({required this.icon, required this.label, this.gold = false, this.onTap});
  final IconData icon;
  final String label;
  final bool gold;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final color = gold ? td.gold : td.fg2;
    final border = gold ? td.goldDeep : td.border2;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 6),
        decoration: BoxDecoration(
          color: td.surface,
          border: Border.all(color: border),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 11, color: color),
            const SizedBox(width: 5),
            Text(label, style: TdText.mono(10, color: color, weight: FontWeight.w700)),
          ],
        ),
      ),
    );
  }
}

class _CombatStats extends StatelessWidget {
  const _CombatStats({required this.profile});
  final ProfileResult profile;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final winRate = profile.matches > 0 ? profile.wins / profile.matches : 0.0;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            Text('ESTADÍSTICAS', style: TdText.display(22, color: td.fg)),
            Row(
              children: [
                _StatsTabButton(
                  icon: Icons.grid_view,
                  label: 'LOGROS',
                  gold: false,
                  onTap: () => Navigator.of(context).push(
                    MaterialPageRoute(builder: (_) => const LogrosScreen()),
                  ),
                ),
                const SizedBox(width: 6),
                _StatsTabButton(
                  icon: Icons.star,
                  label: 'TÍTULOS',
                  gold: true,
                  onTap: () => Navigator.of(context).push(
                    MaterialPageRoute(builder: (_) => TitlesScreen(currentSp: profile.sp)),
                  ),
                ),
              ],
            ),
          ],
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(child: _StatCard(label: 'PARTIDAS', value: '${profile.matches}', color: td.fg2)),
            const SizedBox(width: 8),
            Expanded(child: _StatCard(label: 'VICTORIAS', value: '${profile.wins}', color: td.win)),
            const SizedBox(width: 8),
            Expanded(child: _StatCard(label: 'DERROTAS', value: '${profile.losses}', color: td.loss)),
          ],
        ),
        const SizedBox(height: 12),
        _WinRateBar(winRate: winRate),
      ],
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({required this.label, required this.value, this.color});
  final String label;
  final String value;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final effectiveColor = color ?? td.fg;

    return Container(
      padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 10),
      decoration: BoxDecoration(
        color: td.card,
        border: Border.all(color: td.border),
      ),
      child: Column(
        children: [
          Text(value, style: TdText.display(24, color: effectiveColor)),
          const SizedBox(height: 4),
          Text(label, style: TdText.mono(10, letterSpacing: 0.8, color: td.muted)),
        ],
      ),
    );
  }
}

class _WinRateBar extends StatelessWidget {
  const _WinRateBar({required this.winRate});
  final double winRate;

  Color _winColor(DuelPalette td) {
    if (winRate >= 0.6) return td.win;
    if (winRate >= 0.45) return td.warn;
    return td.loss;
  }

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final color = _winColor(td);
    final pct = '${(winRate * 100).round()}%';

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: td.card,
        border: Border.all(color: td.border),
      ),
      child: Row(
        children: [
          // Ring circular
          SizedBox(
            width: 64,
            height: 64,
            child: CustomPaint(
              painter: _RingPainter(value: winRate, color: color),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text('WIN', style: TdText.mono(8, letterSpacing: 0.8, color: color)),
                  Text(pct, style: TdText.display(16, color: color)),
                ],
              ),
            ),
          ),
          const SizedBox(width: 16),
          // Columnas de racha y récord
          Expanded(
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('RACHA ACTUAL', style: TdText.mono(9, letterSpacing: 0.6, color: td.muted)),
                      const SizedBox(height: 4),
                      Text('--', style: TdText.display(20, color: td.fg)),
                    ],
                  ),
                ),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('RÉCORD', style: TdText.mono(9, letterSpacing: 0.6, color: td.muted)),
                      const SizedBox(height: 4),
                      Text('--', style: TdText.display(20, color: td.fg)),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _RingPainter extends CustomPainter {
  const _RingPainter({required this.value, required this.color});
  final double value;
  final Color color;

  @override
  void paint(Canvas canvas, Size size) {
    final cx = size.width / 2;
    final cy = size.height / 2;
    final radius = math.min(cx, cy) - 5;
    const strokeWidth = 5.0;
    final rect = Rect.fromCircle(center: Offset(cx, cy), radius: radius);

    final bgPaint = Paint()
      ..color = color.withAlpha(38)
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth;

    final fgPaint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth
      ..strokeCap = StrokeCap.round;

    canvas.drawArc(rect, -math.pi / 2, 2 * math.pi, false, bgPaint);
    final sweep = 2 * math.pi * value.clamp(0.0, 1.0);
    canvas.drawArc(rect, -math.pi / 2, sweep, false, fgPaint);
  }

  @override
  bool shouldRepaint(_RingPainter old) => old.value != value || old.color != color;
}


// ─── XP / Nivel ───────────────────────────────────────────────────────────────

class _XpCard extends StatelessWidget {
  const _XpCard({required this.profile});
  final ProfileResult profile;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final xpTotal = profile.experienceTotal;
    final xpToNext = profile.experienceToNextLevel;
    final denom = xpTotal + xpToNext;
    final progress = denom > 0 ? (xpTotal / denom).clamp(0.0, 1.0) : 0.0;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: td.card,
        border: Border.all(color: td.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'PROGRESO · NIVEL ${profile.level}',
                style: TdText.eyebrow(color: td.muted),
              ),
              Text(
                '$xpTotal/${xpTotal + xpToNext} XP',
                style: TdText.mono(10, color: td.muted),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Container(
            height: 4,
            color: td.surface,
            child: FractionallySizedBox(
              alignment: Alignment.centerLeft,
              widthFactor: progress,
              child: Container(
                decoration: BoxDecoration(
                  gradient: LinearGradient(colors: [td.gold, td.goldDeep]),
                ),
              ),
            ),
          ),
          const SizedBox(height: 6),
          Text(
            '$xpToNext XP PARA NIVEL ${profile.level + 1}',
            style: TdText.mono(10, letterSpacing: 0.6, color: td.muted),
          ),
        ],
      ),
    );
  }
}

// ─── Medallas ─────────────────────────────────────────────────────────────────

class _MedallaData {
  const _MedallaData({
    required this.initial,
    required this.date,
    required this.name,
    required this.desc,
  });
  final String initial;
  final String date;
  final String name;
  final String desc;
}

const _kMedallas = [
  _MedallaData(initial: 'V', date: '01 MAR', name: 'PRIMERA VICTORIA', desc: 'Gana tu primera partida.'),
  _MedallaData(initial: '5', date: '03 MAY', name: 'RACHA DE 5', desc: 'Gana 5 partidas seguidas.'),
  _MedallaData(initial: 'S', date: '10 MAR', name: 'SINERGISTA', desc: 'Usa los 3 roles en un equipo.'),
  _MedallaData(initial: '7', date: '15 ABR', name: 'RACHA DE 7', desc: 'Gana 7 partidas seguidas.'),
  _MedallaData(initial: 'A', date: '02 MAY', name: 'ASCENDIDO', desc: 'Sube de rango por primera vez.'),
];

class _MedallasSection extends StatefulWidget {
  @override
  State<_MedallasSection> createState() => _MedallasSectionState();
}

class _MedallasSectionState extends State<_MedallasSection> {
  OverlayEntry? _overlayEntry;
  int? _activeIndex;
  final List<GlobalKey> _keys = List.generate(_kMedallas.length, (_) => GlobalKey());

  void _showTooltip(int index) {
    _dismiss();
    final box = _keys[index].currentContext?.findRenderObject() as RenderBox?;
    if (box == null) return;
    final pos = box.localToGlobal(Offset.zero);
    final size = box.size;

    _overlayEntry = OverlayEntry(
      builder: (_) => Stack(
        children: [
          Positioned.fill(
            child: GestureDetector(
              onTap: _dismiss,
              behavior: HitTestBehavior.opaque,
            ),
          ),
          Positioned(
            left: 16,
            right: 16,
            top: pos.dy + size.height + 8,
            child: Material(
              color: Colors.transparent,
              child: _MedallaTooltip(data: _kMedallas[index]),
            ),
          ),
        ],
      ),
    );

    Overlay.of(context).insert(_overlayEntry!);
    setState(() => _activeIndex = index);
  }

  void _dismiss() {
    _overlayEntry?.remove();
    _overlayEntry = null;
    if (mounted) setState(() => _activeIndex = null);
  }

  @override
  void dispose() {
    _overlayEntry?.remove();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final td = context.td;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text('MEDALLAS', style: TdText.display(22, color: td.fg)),
            Text(
              'EXPUESTAS · ${_kMedallas.length} / ${_kMedallas.length}',
              style: TdText.mono(10, color: td.muted),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceAround,
          children: List.generate(_kMedallas.length, (i) {
            return GestureDetector(
              onTap: () => _activeIndex == i ? _dismiss() : _showTooltip(i),
              child: Container(
                key: _keys[i],
                child: _MedallaCircle(data: _kMedallas[i]),
              ),
            );
          }),
        ),
        const SizedBox(height: 8),
        Text(
          'DETALLE · MANTÉN PARA EQUIPAR',
          style: TdText.mono(9, letterSpacing: 0.6, color: td.muted),
        ),
      ],
    );
  }
}

class _MedallaCircle extends StatelessWidget {
  const _MedallaCircle({required this.data});
  final _MedallaData data;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return Container(
      width: 54,
      height: 54,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        gradient: RadialGradient(
          colors: [td.gold, td.goldDeep],
          center: const Alignment(-0.3, -0.3),
        ),
        border: Border.all(color: const Color(0xFF8E6A2C), width: 2),
      ),
      alignment: Alignment.center,
      child: Text(
        data.initial,
        style: TdText.display(22, color: const Color(0xFF1A140A)),
      ),
    );
  }
}

class _MedallaTooltip extends StatelessWidget {
  const _MedallaTooltip({required this.data});
  final _MedallaData data;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFF1A1A22),
        border: Border.all(color: td.border2),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(data.date, style: TdText.mono(9, letterSpacing: 1.0, color: td.muted)),
          const SizedBox(height: 2),
          Text(data.name, style: TdText.display(16, color: td.fg)),
          const SizedBox(height: 2),
          Text(data.desc, style: TdText.ui(12, color: td.fg2)),
        ],
      ),
    );
  }
}

// ─── Personajes más usados ────────────────────────────────────────────────────

const _kTopChampions = [
  ('V', 'VANGUARD'),
  ('B', 'BULWARK'),
  ('M', 'MIRA'),
];

class _PersonajesMasUsados extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    final td = context.td;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          crossAxisAlignment: CrossAxisAlignment.baseline,
          textBaseline: TextBaseline.alphabetic,
          children: [
            Text('PERSONAJES MÁS USADOS', style: TdText.display(18, color: td.fg)),
            Text('ÚLT. 30 DÍAS', style: TdText.mono(10, color: td.muted)),
          ],
        ),
        const SizedBox(height: 10),
        Row(
          children: List.generate(_kTopChampions.length, (i) {
            final (initial, name) = _kTopChampions[i];
            return Expanded(
              child: Padding(
                padding: EdgeInsets.only(right: i < 2 ? 6 : 0),
                child: _TopChampCard(
                  ranking: '0${i + 1}',
                  initial: initial,
                  name: name,
                ),
              ),
            );
          }),
        ),
      ],
    );
  }
}

class _TopChampCard extends StatelessWidget {
  const _TopChampCard({
    required this.ranking,
    required this.initial,
    required this.name,
  });
  final String ranking;
  final String initial;
  final String name;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
      decoration: BoxDecoration(
        color: td.card,
        border: Border.all(color: td.border),
      ),
      child: Column(
        children: [
          Text(ranking, style: TdText.mono(10, color: td.muted)),
          const SizedBox(height: 8),
          TdChampionGlyph(initial: initial, size: 48, accent: td.gold),
          const SizedBox(height: 6),
          Text(
            name,
            style: TdText.mono(10, letterSpacing: 0.8, color: td.fg),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
}
