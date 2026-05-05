import 'package:flutter/material.dart';

@immutable
class DuelPalette extends ThemeExtension<DuelPalette> {
  const DuelPalette({
    required this.bg,
    required this.bgSoft,
    required this.surface,
    required this.surfaceAlt,
    required this.border,
    required this.textPrimary,
    required this.textSecondary,
    required this.neonGreen,
    required this.accentOrange,
    required this.danger,
  });

  final Color bg;
  final Color bgSoft;
  final Color surface;
  final Color surfaceAlt;
  final Color border;
  final Color textPrimary;
  final Color textSecondary;
  final Color neonGreen;
  final Color accentOrange;
  final Color danger;

  static const dark = DuelPalette(
    bg: Color(0xFF050B16),
    bgSoft: Color(0xFF0A1322),
    surface: Color(0xFF1A2433),
    surfaceAlt: Color(0xFF111A29),
    border: Color(0xFF2A3547),
    textPrimary: Color(0xFFF3F6FC),
    textSecondary: Color(0xFF9BA9BD),
    neonGreen: Color(0xFF7CB342),
    accentOrange: Color(0xFFFFA000),
    danger: Color(0xFFFF5252),
  );

  @override
  DuelPalette copyWith({
    Color? bg,
    Color? bgSoft,
    Color? surface,
    Color? surfaceAlt,
    Color? border,
    Color? textPrimary,
    Color? textSecondary,
    Color? neonGreen,
    Color? accentOrange,
    Color? danger,
  }) {
    return DuelPalette(
      bg: bg ?? this.bg,
      bgSoft: bgSoft ?? this.bgSoft,
      surface: surface ?? this.surface,
      surfaceAlt: surfaceAlt ?? this.surfaceAlt,
      border: border ?? this.border,
      textPrimary: textPrimary ?? this.textPrimary,
      textSecondary: textSecondary ?? this.textSecondary,
      neonGreen: neonGreen ?? this.neonGreen,
      accentOrange: accentOrange ?? this.accentOrange,
      danger: danger ?? this.danger,
    );
  }

  @override
  DuelPalette lerp(ThemeExtension<DuelPalette>? other, double t) {
    if (other is! DuelPalette) return this;
    return DuelPalette(
      bg: Color.lerp(bg, other.bg, t) ?? bg,
      bgSoft: Color.lerp(bgSoft, other.bgSoft, t) ?? bgSoft,
      surface: Color.lerp(surface, other.surface, t) ?? surface,
      surfaceAlt: Color.lerp(surfaceAlt, other.surfaceAlt, t) ?? surfaceAlt,
      border: Color.lerp(border, other.border, t) ?? border,
      textPrimary: Color.lerp(textPrimary, other.textPrimary, t) ?? textPrimary,
      textSecondary: Color.lerp(textSecondary, other.textSecondary, t) ?? textSecondary,
      neonGreen: Color.lerp(neonGreen, other.neonGreen, t) ?? neonGreen,
      accentOrange: Color.lerp(accentOrange, other.accentOrange, t) ?? accentOrange,
      danger: Color.lerp(danger, other.danger, t) ?? danger,
    );
  }
}

class DuelTheme {
  static ThemeData dark() {
    const palette = DuelPalette.dark;
    return ThemeData(
      brightness: Brightness.dark,
      useMaterial3: true,
      scaffoldBackgroundColor: palette.bg,
      colorScheme: const ColorScheme.dark().copyWith(
        primary: palette.neonGreen,
        secondary: palette.accentOrange,
        surface: palette.surface,
        error: palette.danger,
      ),
      textTheme: const TextTheme(
        headlineLarge: TextStyle(fontSize: 34, fontWeight: FontWeight.w800),
        headlineMedium: TextStyle(fontSize: 28, fontWeight: FontWeight.w800),
        titleLarge: TextStyle(fontSize: 24, fontWeight: FontWeight.w700),
        titleMedium: TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
        bodyLarge: TextStyle(fontSize: 16, fontWeight: FontWeight.w500),
        bodyMedium: TextStyle(fontSize: 14, fontWeight: FontWeight.w500),
      ).apply(
        bodyColor: palette.textPrimary,
        displayColor: palette.textPrimary,
      ),
      cardTheme: CardThemeData(
        color: palette.surface,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: BorderSide(color: palette.border),
        ),
      ),
      extensions: const <ThemeExtension<dynamic>>[palette],
    );
  }
}

extension DuelPaletteBuildContext on BuildContext {
  DuelPalette get duel => Theme.of(this).extension<DuelPalette>() ?? DuelPalette.dark;
}
