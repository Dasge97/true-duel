import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../theme/duel_theme.dart';

// Pill de rol inspirado en .td-role (td-shell.jsx)
class TdRolePill extends StatelessWidget {
  const TdRolePill({super.key, required this.role});

  final String role;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final color = td.roleColor(role);
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(width: 6, height: 6, color: color),
        const SizedBox(width: 5),
        Text(
          role.toLowerCase(),
          style: GoogleFonts.jetBrainsMono(
            fontSize: 9,
            letterSpacing: 1.62,
            color: td.fg2,
          ),
        ),
      ],
    );
  }
}
