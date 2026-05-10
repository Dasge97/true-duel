import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../theme/duel_theme.dart';

enum TdButtonVariant { primary, ghost, dark, danger }

// Botón inspirado en .td-btn (tokens.css)
class TdButton extends StatelessWidget {
  const TdButton({
    super.key,
    required this.label,
    this.onPressed,
    this.variant = TdButtonVariant.primary,
    this.small = false,
    this.leading,
    this.loading = false,
    this.width = double.infinity,
    this.fontSize,
  });

  final String label;
  final VoidCallback? onPressed;
  final TdButtonVariant variant;
  final bool small;
  final Widget? leading;
  final bool loading;
  final double width;
  final double? fontSize;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final disabled = onPressed == null || loading;

    final (bg, fg, border, shadow) = switch (variant) {
      TdButtonVariant.primary => (
          td.gold,
          const Color(0xFF16110A),
          Border.all(color: td.goldDeep),
          [BoxShadow(color: td.goldGlow, blurRadius: 20, offset: const Offset(0, 8))],
        ),
      TdButtonVariant.ghost => (
          Colors.transparent,
          td.fg,
          Border.all(color: td.borderStrong),
          <BoxShadow>[],
        ),
      TdButtonVariant.dark => (
          const Color(0xFF1B1B22),
          td.fg,
          Border.all(color: td.border2),
          <BoxShadow>[],
        ),
      TdButtonVariant.danger => (
          Colors.transparent,
          td.loss,
          Border.all(color: td.loss.withAlpha(102)),
          <BoxShadow>[],
        ),
    };

    final fontSize = this.fontSize ?? (small ? 13.0 : 18.0);
    final vPad = small ? 8.0 : 14.0;
    final hPad = small ? 12.0 : 20.0;

    return SizedBox(
      width: width,
      child: Opacity(
        opacity: disabled ? 0.45 : 1.0,
        child: GestureDetector(
          onTap: disabled ? null : onPressed,
          child: Container(
            padding: EdgeInsets.symmetric(horizontal: hPad, vertical: vPad),
            decoration: BoxDecoration(color: bg, border: border, boxShadow: shadow),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                if (leading != null) ...[leading!, const SizedBox(width: 8)],
                if (loading)
                  SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      valueColor: AlwaysStoppedAnimation(fg),
                    ),
                  )
                else
                  Flexible(
                    child: Text(
                      label.toUpperCase(),
                      overflow: TextOverflow.ellipsis,
                      style: GoogleFonts.bebasNeue(
                        fontSize: fontSize,
                        letterSpacing: small ? 1.3 : 1.44,
                        color: fg,
                      ),
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
