import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

// True Duel design system — tokens de diseño de tokens.css
@immutable
class DuelPalette extends ThemeExtension<DuelPalette> {
  const DuelPalette({
    required this.bg,
    required this.surface,
    required this.card,
    required this.card2,
    required this.elev,
    required this.border,
    required this.border2,
    required this.borderStrong,
    required this.fg,
    required this.fg2,
    required this.muted,
    required this.muted2,
    required this.gold,
    required this.gold2,
    required this.goldDeep,
    required this.goldGlow,
    required this.win,
    required this.loss,
    required this.warn,
    required this.info,
    required this.roleIniciador,
    required this.roleAmplificador,
    required this.roleEjecutor,
    required this.roleSoporte,
    required this.roleMago,
    required this.roleCazador,
    required this.roleCentinela,
  });

  // Surfaces
  final Color bg;
  final Color surface;
  final Color card;
  final Color card2;
  final Color elev;
  // Borders
  final Color border;
  final Color border2;
  final Color borderStrong;
  // Text
  final Color fg;
  final Color fg2;
  final Color muted;
  final Color muted2;
  // Accent
  final Color gold;
  final Color gold2;
  final Color goldDeep;
  final Color goldGlow;
  // States
  final Color win;
  final Color loss;
  final Color warn;
  final Color info;
  // Roles
  final Color roleIniciador;
  final Color roleAmplificador;
  final Color roleEjecutor;
  final Color roleSoporte;
  final Color roleMago;
  final Color roleCazador;
  final Color roleCentinela;

  // Aliases de compatibilidad para pantallas no rediseñadas aún
  Color get bgSoft => surface;
  Color get surfaceAlt => surface;
  Color get textPrimary => fg;
  Color get textSecondary => fg2;
  Color get neonGreen => win;
  Color get accentOrange => gold;
  Color get danger => loss;
  Color get success => win;
  Color get warning => warn;
  Color get offline => muted;
  Color get epic => info;

  static const dark = DuelPalette(
    bg: Color(0xFF0A0A0B),
    surface: Color(0xFF14141A),
    card: Color(0xFF1C1C22),
    card2: Color(0xFF23232C),
    elev: Color(0xFF2C2C36),
    border: Color(0x12FFFFFF),
    border2: Color(0x1FFFFFFF),
    borderStrong: Color(0x33FFFFFF),
    fg: Color(0xFFECE7DA),
    fg2: Color(0xFFB7B3A8),
    muted: Color(0xFF7A7872),
    muted2: Color(0xFF4F4D48),
    gold: Color(0xFFE5B567),
    gold2: Color(0xFFC49447),
    goldDeep: Color(0xFF8E6A2C),
    goldGlow: Color(0x2EE5B567),
    win: Color(0xFF7BB893),
    loss: Color(0xFFC76E6E),
    warn: Color(0xFFD9A557),
    info: Color(0xFF6E9DC7),
    roleIniciador: Color(0xFFD6814A),
    roleAmplificador: Color(0xFFC7A04A),
    roleEjecutor: Color(0xFFC0524A),
    roleSoporte: Color(0xFF6BA786),
    roleMago: Color(0xFF7B83C7),
    roleCazador: Color(0xFF8E5A8E),
    roleCentinela: Color(0xFF5A8EC0),
  );

  Color roleColor(String role) {
    switch (role.toLowerCase()) {
      case 'iniciador':    return roleIniciador;
      case 'amplificador': return roleAmplificador;
      case 'ejecutor':     return roleEjecutor;
      case 'soporte':      return roleSoporte;
      case 'mago':         return roleMago;
      case 'cazador':      return roleCazador;
      case 'centinela':    return roleCentinela;
      default:             return muted;
    }
  }

  @override
  DuelPalette copyWith({
    Color? bg, Color? surface, Color? card, Color? card2, Color? elev,
    Color? border, Color? border2, Color? borderStrong,
    Color? fg, Color? fg2, Color? muted, Color? muted2,
    Color? gold, Color? gold2, Color? goldDeep, Color? goldGlow,
    Color? win, Color? loss, Color? warn, Color? info,
    Color? roleIniciador, Color? roleAmplificador, Color? roleEjecutor,
    Color? roleSoporte, Color? roleMago, Color? roleCazador, Color? roleCentinela,
  }) {
    return DuelPalette(
      bg: bg ?? this.bg,
      surface: surface ?? this.surface,
      card: card ?? this.card,
      card2: card2 ?? this.card2,
      elev: elev ?? this.elev,
      border: border ?? this.border,
      border2: border2 ?? this.border2,
      borderStrong: borderStrong ?? this.borderStrong,
      fg: fg ?? this.fg,
      fg2: fg2 ?? this.fg2,
      muted: muted ?? this.muted,
      muted2: muted2 ?? this.muted2,
      gold: gold ?? this.gold,
      gold2: gold2 ?? this.gold2,
      goldDeep: goldDeep ?? this.goldDeep,
      goldGlow: goldGlow ?? this.goldGlow,
      win: win ?? this.win,
      loss: loss ?? this.loss,
      warn: warn ?? this.warn,
      info: info ?? this.info,
      roleIniciador: roleIniciador ?? this.roleIniciador,
      roleAmplificador: roleAmplificador ?? this.roleAmplificador,
      roleEjecutor: roleEjecutor ?? this.roleEjecutor,
      roleSoporte: roleSoporte ?? this.roleSoporte,
      roleMago: roleMago ?? this.roleMago,
      roleCazador: roleCazador ?? this.roleCazador,
      roleCentinela: roleCentinela ?? this.roleCentinela,
    );
  }

  @override
  DuelPalette lerp(ThemeExtension<DuelPalette>? other, double t) {
    if (other is! DuelPalette) return this;
    return DuelPalette(
      bg: Color.lerp(bg, other.bg, t) ?? bg,
      surface: Color.lerp(surface, other.surface, t) ?? surface,
      card: Color.lerp(card, other.card, t) ?? card,
      card2: Color.lerp(card2, other.card2, t) ?? card2,
      elev: Color.lerp(elev, other.elev, t) ?? elev,
      border: Color.lerp(border, other.border, t) ?? border,
      border2: Color.lerp(border2, other.border2, t) ?? border2,
      borderStrong: Color.lerp(borderStrong, other.borderStrong, t) ?? borderStrong,
      fg: Color.lerp(fg, other.fg, t) ?? fg,
      fg2: Color.lerp(fg2, other.fg2, t) ?? fg2,
      muted: Color.lerp(muted, other.muted, t) ?? muted,
      muted2: Color.lerp(muted2, other.muted2, t) ?? muted2,
      gold: Color.lerp(gold, other.gold, t) ?? gold,
      gold2: Color.lerp(gold2, other.gold2, t) ?? gold2,
      goldDeep: Color.lerp(goldDeep, other.goldDeep, t) ?? goldDeep,
      goldGlow: Color.lerp(goldGlow, other.goldGlow, t) ?? goldGlow,
      win: Color.lerp(win, other.win, t) ?? win,
      loss: Color.lerp(loss, other.loss, t) ?? loss,
      warn: Color.lerp(warn, other.warn, t) ?? warn,
      info: Color.lerp(info, other.info, t) ?? info,
      roleIniciador: Color.lerp(roleIniciador, other.roleIniciador, t) ?? roleIniciador,
      roleAmplificador: Color.lerp(roleAmplificador, other.roleAmplificador, t) ?? roleAmplificador,
      roleEjecutor: Color.lerp(roleEjecutor, other.roleEjecutor, t) ?? roleEjecutor,
      roleSoporte: Color.lerp(roleSoporte, other.roleSoporte, t) ?? roleSoporte,
      roleMago: Color.lerp(roleMago, other.roleMago, t) ?? roleMago,
      roleCazador: Color.lerp(roleCazador, other.roleCazador, t) ?? roleCazador,
      roleCentinela: Color.lerp(roleCentinela, other.roleCentinela, t) ?? roleCentinela,
    );
  }
}

class DuelTheme {
  static ThemeData dark() {
    const p = DuelPalette.dark;

    final textTheme = TextTheme(
      // Bebas Neue — headings / display
      displayLarge:  GoogleFonts.bebasNeue(fontSize: 72, height: 0.85, letterSpacing: 0.72),
      displayMedium: GoogleFonts.bebasNeue(fontSize: 48, height: 0.90, letterSpacing: 0.96),
      displaySmall:  GoogleFonts.bebasNeue(fontSize: 32, letterSpacing: 0.64),
      headlineLarge: GoogleFonts.bebasNeue(fontSize: 26, letterSpacing: 1.56),
      headlineMedium:GoogleFonts.bebasNeue(fontSize: 22, letterSpacing: 1.32),
      headlineSmall: GoogleFonts.bebasNeue(fontSize: 18, letterSpacing: 1.44),
      // Inter — cuerpo
      titleLarge:  GoogleFonts.inter(fontSize: 16, fontWeight: FontWeight.w600, letterSpacing: 0.16),
      titleMedium: GoogleFonts.inter(fontSize: 14, fontWeight: FontWeight.w600, letterSpacing: 0.14),
      titleSmall:  GoogleFonts.inter(fontSize: 13, fontWeight: FontWeight.w600),
      bodyLarge:   GoogleFonts.inter(fontSize: 14, fontWeight: FontWeight.w400, height: 1.5),
      bodyMedium:  GoogleFonts.inter(fontSize: 13, fontWeight: FontWeight.w400, height: 1.4),
      bodySmall:   GoogleFonts.inter(fontSize: 12, fontWeight: FontWeight.w400, height: 1.4),
      // JetBrains Mono — etiquetas / eyebrows / datos
      labelLarge:  GoogleFonts.jetBrainsMono(fontSize: 11, fontWeight: FontWeight.w500, letterSpacing: 1.10),
      labelMedium: GoogleFonts.jetBrainsMono(fontSize: 10, fontWeight: FontWeight.w500, letterSpacing: 1.60),
      labelSmall:  GoogleFonts.jetBrainsMono(fontSize: 9,  fontWeight: FontWeight.w500, letterSpacing: 1.62),
    ).apply(bodyColor: p.fg, displayColor: p.fg);

    return ThemeData(
      brightness: Brightness.dark,
      useMaterial3: true,
      scaffoldBackgroundColor: p.bg,
      colorScheme: ColorScheme.dark(
        primary: p.gold,
        onPrimary: const Color(0xFF16110A),
        secondary: p.gold2,
        surface: p.surface,
        error: p.loss,
      ),
      textTheme: textTheme,
      cardTheme: const CardThemeData(
        color: Color(0xFF1C1C22),
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.all(Radius.circular(2)),
          side: BorderSide(color: Color(0x12FFFFFF)),
        ),
        margin: EdgeInsets.zero,
      ),
      dividerTheme: const DividerThemeData(
        color: Color(0x1FFFFFFF),
        thickness: 1,
        space: 0,
      ),
      extensions: const <ThemeExtension<dynamic>>[DuelPalette.dark],
    );
  }
}

extension DuelPaletteBuildContext on BuildContext {
  DuelPalette get duel => Theme.of(this).extension<DuelPalette>() ?? DuelPalette.dark;
  DuelPalette get td => duel;
}

// Helpers de tipografía para uso inline
abstract final class TdText {
  static TextStyle display(double size, {Color? color, double? letterSpacing}) =>
      GoogleFonts.bebasNeue(fontSize: size, color: color, letterSpacing: letterSpacing ?? size * 0.02);

  static TextStyle mono(double size, {double? letterSpacing, FontWeight weight = FontWeight.w500, Color? color}) =>
      GoogleFonts.jetBrainsMono(fontSize: size, letterSpacing: letterSpacing ?? size * 0.14, fontWeight: weight, color: color);

  static TextStyle ui(double size, {FontWeight weight = FontWeight.w400, Color? color, double? height}) =>
      GoogleFonts.inter(fontSize: size, fontWeight: weight, color: color, height: height);

  static TextStyle eyebrow({Color? color}) =>
      GoogleFonts.jetBrainsMono(fontSize: 10, fontWeight: FontWeight.w500, letterSpacing: 1.8, color: color);
}
