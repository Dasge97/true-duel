import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../theme/duel_theme.dart';

// Widget inspirado en TDChampionGlyph del diseño (td-shell.jsx)
// Monograma estilo Mortal Kombat: fondo oscuro, franjas diagonales,
// anillo interior, esquinas doradas y letra central en Bebas Neue.
class TdChampionGlyph extends StatelessWidget {
  const TdChampionGlyph({
    super.key,
    required this.initial,
    this.size = 80,
    this.accent,
    this.subtle = false,
    this.glyph,
  });

  final String initial;
  final double size;
  final Color? accent;
  final bool subtle;
  final String? glyph;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final resolvedAccent = accent ?? td.gold;
    return SizedBox(
      width: size,
      height: size,
      child: CustomPaint(
        painter: _GlyphPainter(accent: resolvedAccent, subtle: subtle),
        child: Stack(
          children: [
            if (!subtle) ...[
              _Corner(top: true,  left: true,  color: resolvedAccent),
              _Corner(top: true,  left: false, color: resolvedAccent),
              _Corner(top: false, left: true,  color: resolvedAccent),
              _Corner(top: false, left: false, color: resolvedAccent),
            ],
            Center(
              child: Text(
                initial,
                style: GoogleFonts.bebasNeue(
                  fontSize: size * 0.55,
                  color: subtle ? td.muted : td.fg,
                  shadows: subtle
                      ? null
                      : [Shadow(color: resolvedAccent.withAlpha(51), blurRadius: 30)],
                ),
              ),
            ),
            if (glyph != null)
              Positioned(
                bottom: 4,
                right: 6,
                child: Text(
                  glyph!,
                  style: GoogleFonts.jetBrainsMono(
                    fontSize: 8,
                    color: td.muted,
                    letterSpacing: 0.8,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _GlyphPainter extends CustomPainter {
  const _GlyphPainter({required this.accent, required this.subtle});

  final Color accent;
  final bool subtle;

  @override
  void paint(Canvas canvas, Size size) {
    // Fondo
    canvas.drawRect(
      Rect.fromLTWH(0, 0, size.width, size.height),
      Paint()..color = const Color(0xFF0E0E12),
    );

    // Franjas diagonales 135° (transparent 0-8px, línea 1px en 8-9px)
    final stripePaint = Paint()
      ..color = const Color(0x06FFFFFF)
      ..strokeWidth = 1
      ..isAntiAlias = false;

    for (double t = -size.height; t < size.width + size.height; t += 9) {
      canvas.drawLine(
        Offset(t, 0),
        Offset(t + size.height, size.height),
        stripePaint,
      );
    }

    // Anillo interior (inset 6%)
    final ri = size.width * 0.06;
    canvas.drawRect(
      Rect.fromLTWH(ri, ri, size.width - ri * 2, size.height - ri * 2),
      Paint()
        ..color = subtle ? const Color(0x1FFFFFFF) : accent.withAlpha(64)
        ..style = PaintingStyle.stroke
        ..strokeWidth = 1,
    );
  }

  @override
  bool shouldRepaint(_GlyphPainter old) =>
      old.accent != accent || old.subtle != subtle;
}

class _Corner extends StatelessWidget {
  const _Corner({required this.top, required this.left, required this.color});

  final bool top;
  final bool left;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Positioned(
      top: top ? 6 : null,
      bottom: top ? null : 6,
      left: left ? 6 : null,
      right: left ? null : 6,
      child: CustomPaint(
        size: const Size(8, 8),
        painter: _CornerPainter(top: top, left: left, color: color),
      ),
    );
  }
}

class _CornerPainter extends CustomPainter {
  const _CornerPainter({required this.top, required this.left, required this.color});

  final bool top;
  final bool left;
  final Color color;

  @override
  void paint(Canvas canvas, Size size) {
    final p = Paint()
      ..color = color
      ..strokeWidth = 1
      ..style = PaintingStyle.stroke;

    final w = size.width;
    final h = size.height;

    if (top && left) {
      // Esquina superior izquierda: borde superior + borde izquierdo
      canvas.drawLine(Offset(0, h), const Offset(0, 0), p);
      canvas.drawLine(const Offset(0, 0), Offset(w, 0), p);
    } else if (top && !left) {
      // Esquina superior derecha: borde superior + borde derecho
      canvas.drawLine(const Offset(0, 0), Offset(w, 0), p);
      canvas.drawLine(Offset(w, 0), Offset(w, h), p);
    } else if (!top && left) {
      // Esquina inferior izquierda: borde izquierdo + borde inferior
      canvas.drawLine(const Offset(0, 0), Offset(0, h), p);
      canvas.drawLine(Offset(0, h), Offset(w, h), p);
    } else {
      // Esquina inferior derecha: borde derecho + borde inferior
      canvas.drawLine(Offset(w, 0), Offset(w, h), p);
      canvas.drawLine(Offset(0, h), Offset(w, h), p);
    }
  }

  @override
  bool shouldRepaint(_CornerPainter old) =>
      old.top != top || old.left != left || old.color != color;
}
