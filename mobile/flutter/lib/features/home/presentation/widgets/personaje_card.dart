import 'package:flutter/material.dart';

import '../../../../../core/theme/duel_theme.dart';
import '../../../../../core/widgets/td_champion_glyph.dart';
import '../../../../../core/widgets/td_role_pill.dart';
import '../../../../../features/mvp/data/mvp_api_repository.dart';

// Legacy helper kept for files that still import it directly.
Color roleColor(String role) {
  return DuelPalette.dark.roleColor(role);
}

class PersonajeCard extends StatelessWidget {
  const PersonajeCard({
    super.key,
    required this.personaje,
    this.slotIndex,
    this.isActive = false,
    this.onTap,
  });

  final PersonajeEntry personaje;
  final int? slotIndex;
  final bool isActive;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final isLocked = !personaje.desbloqueado;
    final roleCol = td.roleColor(personaje.rolSinergia);
    final inSlot = slotIndex != null;

    return GestureDetector(
      onTap: onTap,
      child: Container(
        decoration: BoxDecoration(
          color: const Color(0xFF16161C),
          border: Border.all(
            color: inSlot ? td.goldDeep : td.border,
          ),
        ),
        padding: const EdgeInsets.all(10),
        child: Stack(
          children: [
            // Main content
            Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                // Glyph
                Center(
                  child: TdChampionGlyph(
                    initial: personaje.nombre.isNotEmpty
                        ? personaje.nombre[0].toUpperCase()
                        : '?',
                    size: 64,
                    accent: roleCol,
                    subtle: isLocked,
                  ),
                ),
                const SizedBox(height: 6),
                // Name
                Text(
                  personaje.nombre,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  textAlign: TextAlign.center,
                  style: TdText.ui(
                    12,
                    weight: FontWeight.w600,
                    color: isLocked ? td.muted : td.fg,
                  ),
                ),
                const SizedBox(height: 4),
                // Role pill
                Center(child: TdRolePill(role: personaje.rolSinergia)),
                const SizedBox(height: 6),
                // Divider
                Container(height: 1, color: td.border),
                const SizedBox(height: 6),
                // Bottom row: cost dots + price/mastery
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    // Cost dots: 3 small rects
                    Row(
                      children: List.generate(3, (i) {
                        final filled = (i + 1) <= personaje.costeCargas;
                        return Container(
                          width: 5,
                          height: 8,
                          margin: i < 2
                              ? const EdgeInsets.only(right: 2)
                              : EdgeInsets.zero,
                          color: filled ? td.gold : td.muted2,
                        );
                      }),
                    ),
                    // Price or mastery
                    if (isLocked)
                      Text(
                        '${personaje.precioMonedas}',
                        style: TdText.mono(10, color: td.gold),
                      )
                    else
                      Text(
                        'M·${personaje.nivelMaestria}',
                        style: TdText.mono(10, color: td.fg2),
                      ),
                  ],
                ),
              ],
            ),

            // Lock overlay icon (top-right)
            if (isLocked)
              Positioned(
                top: 0,
                right: 0,
                child: Icon(
                  Icons.lock,
                  size: 12,
                  color: td.muted,
                ),
              ),

            // Slot badge (top-left)
            if (inSlot)
              Positioned(
                top: 0,
                left: 0,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
                  color: td.gold,
                  child: Text(
                    'S${slotIndex! + 1}',
                    style: TdText.display(11, color: const Color(0xFF16110A)),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
