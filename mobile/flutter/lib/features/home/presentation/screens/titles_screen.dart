import 'dart:math' as math;

import 'package:flutter/material.dart';

import '../../../../core/theme/duel_theme.dart';

// ─── Data ─────────────────────────────────────────────────────────────────────

class _TitleEntry {
  const _TitleEntry({
    required this.id,
    required this.name,
    required this.sp,
    required this.color,
  });

  final String id;
  final String name;
  final int sp;
  final Color color;
}

const _kTitles = [
  _TitleEntry(id: 'novato',       name: 'Novato',       sp: 0,     color: Color(0xFF7A7872)),
  _TitleEntry(id: 'iniciado',     name: 'Iniciado',     sp: 500,   color: Color(0xFFB07A4A)),
  _TitleEntry(id: 'guardian',     name: 'Guardián',     sp: 1200,  color: Color(0xFFB07A4A)),
  _TitleEntry(id: 'duelista',     name: 'Duelista',     sp: 2000,  color: Color(0xFFC9C2B0)),
  _TitleEntry(id: 'campeon',      name: 'Campeón',      sp: 3500,  color: Color(0xFFE5B567)),
  _TitleEntry(id: 'elite',        name: 'Élite',        sp: 5500,  color: Color(0xFFE5B567)),
  _TitleEntry(id: 'gran_maestro', name: 'Gran Maestro', sp: 8500,  color: Color(0xFFD67A7A)),
  _TitleEntry(id: 'leyenda',      name: 'Leyenda',      sp: 14000, color: Color(0xFFA77ACE)),
];

int _currentTitleIdx(int sp) {
  var idx = 0;
  for (var i = 0; i < _kTitles.length; i++) {
    if (sp >= _kTitles[i].sp) idx = i;
  }
  return idx;
}

// ─── Screen ───────────────────────────────────────────────────────────────────

class TitlesScreen extends StatelessWidget {
  const TitlesScreen({super.key, required this.currentSp});

  final int currentSp;

  @override
  Widget build(BuildContext context) {
    final curIdx = _currentTitleIdx(currentSp);
    final cur  = _kTitles[curIdx];
    final hasNext = curIdx < _kTitles.length - 1;
    final next = hasNext ? _kTitles[curIdx + 1] : null;
    final prev = curIdx > 0 ? _kTitles[curIdx - 1] : null;
    final progress = next != null
        ? ((currentSp - cur.sp) / (next.sp - cur.sp)).clamp(0.0, 1.0)
        : 1.0;

    final td = context.td;
    return Scaffold(
      backgroundColor: td.bg,
      body: SafeArea(
        child: Column(
          children: [
            _TopBar(),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(14, 16, 14, 28),
                children: [
                  _HeroCard(cur: cur),
                  const SizedBox(height: 16),
                  _ProgressCard(
                    currentSp: currentSp,
                    prev: prev,
                    next: next,
                    progress: progress,
                  ),
                  const SizedBox(height: 16),
                  _EscaleraSection(curIdx: curIdx, currentSp: currentSp),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ─── Top bar ──────────────────────────────────────────────────────────────────

class _TopBar extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        border: Border(bottom: BorderSide(color: td.border)),
      ),
      child: Row(
        children: [
          GestureDetector(
            onTap: () => Navigator.of(context).maybePop(),
            child: Container(
              width: 32,
              height: 32,
              decoration: BoxDecoration(border: Border.all(color: td.border2)),
              child: Icon(Icons.chevron_left, color: td.fg, size: 18),
            ),
          ),
          const SizedBox(width: 12),
          Text('TÍTULOS', style: TdText.display(22)),
          const Spacer(),
          Text('S2', style: TdText.mono(10, letterSpacing: 1.4, color: td.gold)),
        ],
      ),
    );
  }
}

// ─── Hero card ────────────────────────────────────────────────────────────────

class _HeroCard extends StatelessWidget {
  const _HeroCard({required this.cur});

  final _TitleEntry cur;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final name = cur.name.toUpperCase();
    return Container(
      clipBehavior: Clip.hardEdge,
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF1F1A12), Color(0xFF15151A)],
          stops: [0.0, 1.0],
        ),
        border: Border.all(color: td.gold.withAlpha(102)),
      ),
      child: Stack(
        children: [
          Positioned.fill(child: CustomPaint(painter: _GoldStripesPainter())),
          Padding(
            padding: const EdgeInsets.fromLTRB(18, 22, 18, 20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('TÍTULO ACTUAL · EQUIPADO', style: TdText.eyebrow(color: td.gold)),
                const SizedBox(height: 6),
                Text(
                  name.replaceAll(' ', '\n'),
                  style: TdText.display(38).copyWith(height: 0.95),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ─── Progress card ────────────────────────────────────────────────────────────

class _ProgressCard extends StatelessWidget {
  const _ProgressCard({
    required this.currentSp,
    required this.prev,
    required this.next,
    required this.progress,
  });

  final int currentSp;
  final _TitleEntry? prev;
  final _TitleEntry? next;
  final double progress;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: td.card,
        border: Border.all(color: td.border2),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('PROGRESO DE TÍTULO', style: TdText.eyebrow(color: td.muted)),
          const SizedBox(height: 4),
          Row(
            crossAxisAlignment: CrossAxisAlignment.baseline,
            textBaseline: TextBaseline.alphabetic,
            children: [
              Text(_fmt(currentSp), style: TdText.display(28)),
              const SizedBox(width: 6),
              Text('SP', style: TdText.display(16, color: td.muted)),
            ],
          ),
          if (next != null) ...[
            const SizedBox(height: 14),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (prev != null)
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('ANTERIOR', style: TdText.mono(9, letterSpacing: 1.4, color: td.muted)),
                      Text(prev!.name, style: TdText.ui(12, color: td.fg2)),
                    ],
                  ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text('SIGUIENTE', style: TdText.mono(9, letterSpacing: 1.4, color: td.gold)),
                    Text(next!.name, style: TdText.ui(12, color: td.fg)),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 8),
            SizedBox(
              height: 32,
              child: CustomPaint(
                painter: _ProgressBarPainter(progress: progress, td: td),
                size: const Size(double.infinity, 32),
              ),
            ),
            const SizedBox(height: 8),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                if (prev != null)
                  Text('${_fmt(prev!.sp)} SP', style: TdText.mono(10, color: td.muted)),
                Text('${_fmt(next!.sp - currentSp)} SP RESTANTES', style: TdText.mono(10, color: td.gold)),
                Text('${_fmt(next!.sp)} SP', style: TdText.mono(10, color: td.fg2)),
              ],
            ),
          ] else ...[
            const SizedBox(height: 8),
            Text('HAS ALCANZADO EL RANGO MÁXIMO', style: TdText.mono(11, letterSpacing: 1.0, color: td.gold)),
          ],
        ],
      ),
    );
  }
}

// ─── Progress bar painter ─────────────────────────────────────────────────────

class _ProgressBarPainter extends CustomPainter {
  const _ProgressBarPainter({required this.progress, required this.td});

  final double progress;
  final DuelPalette td;

  @override
  void paint(Canvas canvas, Size size) {
    const trackY = 16.0;
    const trackH = 3.0;

    // Background track
    canvas.drawRect(
      Rect.fromLTWH(0, trackY - trackH / 2, size.width, trackH),
      Paint()..color = const Color(0xFF0E0E12),
    );
    canvas.drawRect(
      Rect.fromLTWH(0, trackY - trackH / 2, size.width, trackH),
      Paint()
        ..color = td.border
        ..style = PaintingStyle.stroke
        ..strokeWidth = 1,
    );

    // Fill
    final fillW = size.width * progress.clamp(0.0, 1.0);
    if (fillW > 0) {
      canvas.drawRect(
        Rect.fromLTWH(0, trackY - trackH / 2, fillW, trackH),
        Paint()
          ..shader = LinearGradient(
            colors: [td.goldDeep, td.gold],
          ).createShader(Rect.fromLTWH(0, 0, size.width, trackH)),
      );
    }

    // Previous diamond (left)
    _drawDiamond(canvas, Offset(0, trackY), 6.5, td.goldDeep, const Color(0xFF15151A));

    // Next diamond (right)
    _drawDiamond(canvas, Offset(size.width, trackY), 6.5, td.borderStrong, const Color(0xFF15151A));

    // Current marker — gold square with "V" (rendered via separate overlay)
    final curX = (size.width * progress).clamp(11.0, size.width - 11.0);
    final markerRect = Rect.fromCenter(
      center: Offset(curX, trackY),
      width: 22,
      height: 22,
    );
    // Glow ring
    canvas.drawRect(
      markerRect.inflate(4),
      Paint()
        ..color = td.gold.withAlpha(46)
        ..style = PaintingStyle.stroke
        ..strokeWidth = 4,
    );
    canvas.drawRect(markerRect, Paint()..color = td.gold);
  }

  void _drawDiamond(Canvas canvas, Offset center, double half, Color border, Color fill) {
    final path = Path()
      ..moveTo(center.dx, center.dy - half)
      ..lineTo(center.dx + half, center.dy)
      ..lineTo(center.dx, center.dy + half)
      ..lineTo(center.dx - half, center.dy)
      ..close();
    canvas.drawPath(path, Paint()..color = fill);
    canvas.drawPath(
      path,
      Paint()
        ..color = border
        ..style = PaintingStyle.stroke
        ..strokeWidth = 1.5,
    );
  }

  @override
  bool shouldRepaint(_ProgressBarPainter old) => old.progress != progress;
}

// ─── Escalera ─────────────────────────────────────────────────────────────────

class _EscaleraSection extends StatelessWidget {
  const _EscaleraSection({required this.curIdx, required this.currentSp});

  final int curIdx;
  final int currentSp;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text('ESCALERA', style: TdText.display(22)),
            Text(
              '${curIdx + 1} / ${_kTitles.length}',
              style: TdText.mono(10, letterSpacing: 1.0, color: td.muted),
            ),
          ],
        ),
        const SizedBox(height: 12),
        for (int i = 0; i < _kTitles.length; i++)
          _TitleRow(
            entry: _kTitles[i],
            index: i,
            curIdx: curIdx,
            currentSp: currentSp,
            isFirst: i == 0,
            isLast: i == _kTitles.length - 1,
          ),
      ],
    );
  }
}

class _TitleRow extends StatelessWidget {
  const _TitleRow({
    required this.entry,
    required this.index,
    required this.curIdx,
    required this.currentSp,
    required this.isFirst,
    required this.isLast,
  });

  final _TitleEntry entry;
  final int index;
  final int curIdx;
  final int currentSp;
  final bool isFirst;
  final bool isLast;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final reached = index <= curIdx;
    final isCur = index == curIdx;

    final nodeSz = isCur ? 14.0 : 9.0;
    final nodeBg = isCur ? td.gold : (reached ? td.goldDeep : const Color(0xFF15151A));
    final nodeBorder = isCur ? td.gold : (reached ? td.goldDeep : td.border2);
    final topRailColor = reached ? td.gold : td.border2;
    final botRailColor = (reached && index < curIdx) ? td.gold : td.border2;

    final nameColor = isCur ? td.gold : (reached ? td.fg : td.muted);

    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Rail column
          SizedBox(
            width: 22,
            child: Column(
              children: [
                if (!isFirst)
                  Expanded(
                    child: Center(child: Container(width: 1, color: topRailColor)),
                  )
                else
                  const SizedBox(height: 8),
                // Diamond node
                Transform.rotate(
                  angle: math.pi / 4,
                  child: Container(
                    width: nodeSz,
                    height: nodeSz,
                    decoration: BoxDecoration(
                      color: nodeBg,
                      border: Border.all(color: nodeBorder, width: isCur ? 2 : 1),
                    ),
                  ),
                ),
                if (!isLast)
                  Expanded(
                    child: Center(child: Container(width: 1, color: botRailColor)),
                  )
                else
                  const SizedBox(height: 8),
              ],
            ),
          ),
          // Content
          Expanded(
            child: Container(
              margin: const EdgeInsets.only(bottom: 4),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              decoration: BoxDecoration(
                gradient: isCur
                    ? LinearGradient(colors: [td.gold.withAlpha(26), Colors.transparent])
                    : null,
                border: Border.all(
                  color: isCur ? td.goldDeep : Colors.transparent,
                ),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Flexible(
                              child: Text(
                                entry.name.toUpperCase(),
                                style: TdText.display(16, color: nameColor),
                              ),
                            ),
                            if (isCur) ...[
                              const SizedBox(width: 8),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                                decoration: BoxDecoration(border: Border.all(color: td.goldDeep)),
                                child: Text('ACTUAL', style: TdText.mono(9, letterSpacing: 1.4, color: td.gold)),
                              ),
                            ],
                          ],
                        ),
                        const SizedBox(height: 2),
                        Text(
                          reached
                              ? '${_fmt(entry.sp)} SP'
                              : '${_fmt(entry.sp)} SP  ·  FALTAN ${_fmt(entry.sp - currentSp)}',
                          style: TdText.mono(10, color: reached ? td.muted : td.muted2),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    width: 10,
                    height: 10,
                    color: entry.color.withAlpha(reached ? 255 : 89),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}


// ─── Painters ─────────────────────────────────────────────────────────────────

class _GoldStripesPainter extends CustomPainter {
  const _GoldStripesPainter({this.opacity = 10});

  final int opacity;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = Color.fromARGB(opacity, 229, 181, 103)
      ..strokeWidth = 1;
    for (double t = -size.height; t < size.width + size.height; t += 25) {
      canvas.drawLine(Offset(t, 0), Offset(t + size.height, size.height), paint);
    }
  }

  @override
  bool shouldRepaint(_GoldStripesPainter old) => old.opacity != opacity;
}

// ─── Utility ──────────────────────────────────────────────────────────────────

String _fmt(int v) {
  if (v >= 1000) {
    final t = v ~/ 1000;
    final r = v % 1000;
    return r == 0 ? '$t 000' : '$t ${r.toString().padLeft(3, '0')}';
  }
  return v.toString();
}
