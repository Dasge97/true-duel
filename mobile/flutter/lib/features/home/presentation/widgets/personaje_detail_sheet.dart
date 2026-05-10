import 'package:flutter/material.dart';

import '../../../../../core/theme/duel_theme.dart';
import '../../../../../core/widgets/td_button.dart';
import '../../../../../core/widgets/td_champion_glyph.dart';
import '../../../../../core/widgets/td_role_pill.dart';
import '../../../../../features/mvp/data/mvp_api_repository.dart';

void showPersonajeDetail({
  required BuildContext context,
  required PersonajeEntry personaje,
  int? currentSlot,
  required void Function(String id) onToggle,
}) {
  showModalBottomSheet(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (_) => _PersonajeDetailSheet(
      personaje: personaje,
      currentSlot: currentSlot,
      onToggle: onToggle,
    ),
  );
}

class _PersonajeDetailSheet extends StatelessWidget {
  const _PersonajeDetailSheet({
    required this.personaje,
    this.currentSlot,
    required this.onToggle,
  });

  final PersonajeEntry personaje;
  final int? currentSlot;
  final void Function(String id) onToggle;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final roleCol = td.roleColor(personaje.rolSinergia);
    final isLocked = !personaje.desbloqueado;
    final inSlot = currentSlot != null;

    const xpPerLevel = 300;
    final xpCurrent = personaje.xpMaestria % xpPerLevel;
    final progress = xpCurrent / xpPerLevel;

    return Container(
      constraints: BoxConstraints(
        maxHeight: MediaQuery.of(context).size.height * 0.78,
      ),
      decoration: BoxDecoration(
        color: const Color(0xFF16161C),
        border: Border(
          top: BorderSide(color: td.goldDeep, width: 1),
        ),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Drag handle
          Padding(
            padding: const EdgeInsets.only(top: 12, bottom: 4),
            child: Center(
              child: Container(
                width: 36,
                height: 3,
                decoration: BoxDecoration(
                  color: td.borderStrong,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
          ),

          Flexible(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Header row
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      TdChampionGlyph(
                        initial: personaje.nombre.isNotEmpty
                            ? personaje.nombre[0].toUpperCase()
                            : '?',
                        size: 72,
                        accent: roleCol,
                        subtle: isLocked,
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              crossAxisAlignment: CrossAxisAlignment.center,
                              children: [
                                Expanded(
                                  child: Text(
                                    personaje.nombre.toUpperCase(),
                                    style: TdText.display(32, color: td.fg),
                                  ),
                                ),
                                if (inSlot)
                                  Container(
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 6,
                                      vertical: 2,
                                    ),
                                    color: td.gold,
                                    child: Text(
                                      'S${currentSlot! + 1}',
                                      style: TdText.display(
                                        14,
                                        color: const Color(0xFF16110A),
                                      ),
                                    ),
                                  ),
                              ],
                            ),
                            const SizedBox(height: 4),
                            TdRolePill(role: personaje.rolSinergia),
                            const SizedBox(height: 8),
                            // Cost
                            Row(
                              children: [
                                Text(
                                  'COSTE',
                                  style: TdText.eyebrow(color: td.muted),
                                ),
                                const SizedBox(width: 8),
                                Row(
                                  children: List.generate(3, (i) {
                                    final filled =
                                        (i + 1) <= personaje.costeCargas;
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
                              ],
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),

                  const SizedBox(height: 14),
                  Container(height: 1, color: td.border),
                  const SizedBox(height: 14),

                  // Description
                  if (personaje.descripcion.isNotEmpty) ...[
                    Text(
                      personaje.descripcion,
                      style: TdText.ui(13, color: td.fg2, height: 1.5),
                    ),
                    const SizedBox(height: 14),
                  ],

                  // Mastery section
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'MAESTRÍA',
                        style: TdText.eyebrow(color: td.muted),
                      ),
                      Text(
                        '$xpCurrent/$xpPerLevel XP',
                        style: TdText.mono(11, color: td.muted),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      Text(
                        '${personaje.nivelMaestria}',
                        style: TdText.display(38, color: td.gold),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            // Progress bar
                            Container(
                              height: 6,
                              decoration: BoxDecoration(
                                color: const Color(0xFF0E0E12),
                                border: Border.all(color: td.border),
                              ),
                              child: FractionallySizedBox(
                                alignment: Alignment.centerLeft,
                                widthFactor: progress.clamp(0.0, 1.0),
                                child: Container(
                                  decoration: BoxDecoration(
                                    gradient: LinearGradient(
                                      colors: [td.goldDeep, td.gold],
                                    ),
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'NV. MAESTRÍA',
                              style: TdText.eyebrow(color: td.muted),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),

                  // Tags
                  if (personaje.tags.isNotEmpty) ...[
                    const SizedBox(height: 14),
                    Wrap(
                      spacing: 6,
                      runSpacing: 6,
                      children: personaje.tags
                          .map(
                            (t) => Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 7,
                                vertical: 3,
                              ),
                              decoration: BoxDecoration(
                                border: Border.all(color: td.border2),
                              ),
                              child: Text(
                                t,
                                style: TdText.mono(
                                  9,
                                  letterSpacing: 1.26,
                                  color: td.fg2,
                                ),
                              ),
                            ),
                          )
                          .toList(),
                    ),
                  ],

                  const SizedBox(height: 20),

                  // Action buttons
                  Row(
                    children: [
                      if (inSlot && !isLocked) ...[
                        Expanded(
                          child: TdButton(
                            label: 'QUITAR DEL SLOT',
                            variant: TdButtonVariant.ghost,
                            small: true,
                            onPressed: () {
                              onToggle(personaje.id);
                              Navigator.of(context).pop();
                            },
                          ),
                        ),
                        const SizedBox(width: 8),
                      ],
                      if (!isLocked)
                        Expanded(
                          child: TdButton(
                            label: inSlot
                                ? 'QUITAR DEL EQUIPO'
                                : 'AÑADIR AL EQUIPO',
                            variant: inSlot
                                ? TdButtonVariant.danger
                                : TdButtonVariant.primary,
                            onPressed: () {
                              onToggle(personaje.id);
                              Navigator.of(context).pop();
                            },
                          ),
                        )
                      else
                        Expanded(
                          child: TdButton(
                            label: 'BLOQUEADO · ${personaje.precioMonedas}',
                            variant: TdButtonVariant.ghost,
                            onPressed: null,
                          ),
                        ),
                    ],
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
