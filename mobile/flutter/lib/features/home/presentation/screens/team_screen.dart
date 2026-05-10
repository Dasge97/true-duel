import 'package:flutter/material.dart';

import '../../../../core/theme/duel_theme.dart';
import '../../../../core/widgets/async_state_card.dart';
import '../../../../core/widgets/td_button.dart';
import '../../../../core/widgets/td_champion_glyph.dart';
import '../../../../core/widgets/td_role_pill.dart';
import '../controllers/team_controller.dart';
import '../widgets/personaje_card.dart';
import '../widgets/personaje_detail_sheet.dart';
import '../../../../features/mvp/data/mvp_api_repository.dart';

// ──────────────────────────────────────────────
// Role filter options
// ──────────────────────────────────────────────
const _kRoleFilters = [
  'TODOS',
  'INICIADOR',
  'AMPLIFICADOR',
  'FINALIZADOR',
  'SOPORTE',
  'MAGO',
  'CAZADOR',
  'CENTINELA',
];

class TeamScreen extends StatefulWidget {
  const TeamScreen({super.key, required this.controller});

  final TeamController controller;

  @override
  State<TeamScreen> createState() => _TeamScreenState();
}

class _TeamScreenState extends State<TeamScreen> {
  TeamController get c => widget.controller;
  String _selectedRole = 'TODOS';

  @override
  void initState() {
    super.initState();
    c.load();
  }

  List<PersonajeEntry> get _filteredCatalog {
    if (_selectedRole == 'TODOS') return c.catalog;
    return c.catalog
        .where(
          (p) => p.rolSinergia.toUpperCase() == _selectedRole,
        )
        .toList();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: c,
      builder: (context, _) {
        if (c.isLoading) {
          return const Center(child: AsyncStateCard.loading());
        }
        if (c.error != null) {
          return Center(
            child: AsyncStateCard.error(
              message: 'Error cargando equipo',
              onRetry: c.load,
            ),
          );
        }
        return _buildContent(context);
      },
    );
  }

  Widget _buildContent(BuildContext context) {
    final td = context.td;
    final filtered = _filteredCatalog;

    return Column(
      children: [
        // Scrollable main content
        Expanded(
          child: ListView(
            padding: const EdgeInsets.all(14),
            children: [
              // ── EQUIPO section header ──
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                crossAxisAlignment: CrossAxisAlignment.baseline,
                textBaseline: TextBaseline.alphabetic,
                children: [
                  Text('EQUIPO', style: TdText.display(22, color: td.fg)),
                  Text(
                    'SLOT ${c.activeSlot + 1} ACTIVO',
                    style: TdText.mono(11, color: td.muted),
                  ),
                ],
              ),
              const SizedBox(height: 8),

              // ── Slot row ──
              Row(
                children: List.generate(3, (i) {
                  final slotId = c.slots[i];
                  final personaje = slotId != null
                      ? c.catalog.where((p) => p.id == slotId).firstOrNull
                      : null;
                  return Expanded(
                    child: Padding(
                      padding: EdgeInsets.only(
                        left: i == 0 ? 0 : 3,
                        right: i == 2 ? 0 : 3,
                      ),
                      child: _TdChampSlot(
                        slotIndex: i,
                        personaje: personaje,
                        isActive: c.activeSlot == i,
                        onTap: () => c.selectSlot(i),
                      ),
                    ),
                  );
                }),
              ),

              const SizedBox(height: 14),

              // ── Role filter chips ──
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: Row(
                  children: _kRoleFilters.map((role) {
                    final isActive = _selectedRole == role;
                    return Padding(
                      padding: const EdgeInsets.only(right: 6),
                      child: GestureDetector(
                        onTap: () => setState(() => _selectedRole = role),
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 5,
                          ),
                          decoration: BoxDecoration(
                            color: isActive ? td.gold : Colors.transparent,
                            border: Border.all(
                              color: isActive ? td.gold : td.border2,
                            ),
                          ),
                          child: Text(
                            role,
                            style: TdText.mono(
                              10,
                              weight: isActive
                                  ? FontWeight.w700
                                  : FontWeight.w500,
                              color: isActive
                                  ? const Color(0xFF16110A)
                                  : td.muted,
                            ),
                          ),
                        ),
                      ),
                    );
                  }).toList(),
                ),
              ),

              const SizedBox(height: 14),

              // ── CATÁLOGO header ──
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                crossAxisAlignment: CrossAxisAlignment.baseline,
                textBaseline: TextBaseline.alphabetic,
                children: [
                  Text('CATÁLOGO', style: TdText.display(22, color: td.fg)),
                  Text(
                    '${filtered.length} CAMPEONES',
                    style: TdText.mono(11, color: td.muted),
                  ),
                ],
              ),
              const SizedBox(height: 8),

              // ── Grid ──
              GridView.count(
                crossAxisCount: 3,
                mainAxisSpacing: 6,
                crossAxisSpacing: 6,
                childAspectRatio: 0.72,
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                children: filtered.map((p) {
                  final slotIdx = c.slots.indexWhere((s) => s == p.id);
                  final inSlot = slotIdx != -1;
                  return GestureDetector(
                    onTap: () => showPersonajeDetail(
                      context: context,
                      personaje: p,
                      currentSlot: inSlot ? slotIdx : null,
                      onToggle: (_) => c.toggleChampion(p.id),
                    ),
                    child: PersonajeCard(
                      personaje: p,
                      slotIndex: inSlot ? slotIdx : null,
                      isActive: c.activeSlot == slotIdx && inSlot,
                    ),
                  );
                }).toList(),
              ),
            ],
          ),
        ),

        // ── Sticky save button ──
        _StickyBar(controller: c),
      ],
    );
  }
}

// ──────────────────────────────────────────────
// Slot widget
// ──────────────────────────────────────────────
class _TdChampSlot extends StatelessWidget {
  const _TdChampSlot({
    required this.slotIndex,
    required this.personaje,
    required this.isActive,
    required this.onTap,
  });

  final int slotIndex;
  final PersonajeEntry? personaje;
  final bool isActive;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final isEmpty = personaje == null;
    final roleCol = isEmpty ? null : td.roleColor(personaje!.rolSinergia);

    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(10),
        decoration: BoxDecoration(
          gradient: isEmpty
              ? null
              : const LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [Color(0xFF1B1B22), Color(0xFF14141A)],
                ),
          border: Border.all(
            color: isActive
                ? td.goldDeep
                : isEmpty
                    ? td.border2
                    : td.border,
          ),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Slot label
            Text(
              'SLOT ${slotIndex + 1}',
              style: TdText.mono(
                9,
                color: isActive ? td.gold : td.muted,
              ),
            ),
            const SizedBox(height: 6),
            // Content area: empty or filled
            if (isEmpty)
              SizedBox(
                height: 70,
                child: Center(
                  child: Text('+', style: TdText.display(32, color: td.muted2)),
                ),
              )
            else ...[
              TdChampionGlyph(
                initial: personaje!.nombre.isNotEmpty
                    ? personaje!.nombre[0].toUpperCase()
                    : '?',
                size: 48,
                accent: roleCol,
              ),
              const SizedBox(height: 4),
              Text(
                personaje!.nombre,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                textAlign: TextAlign.center,
                style: TdText.ui(11, weight: FontWeight.w600, color: td.fg),
              ),
              const SizedBox(height: 2),
              Center(child: TdRolePill(role: personaje!.rolSinergia)),
            ],
          ],
        ),
      ),
    );
  }
}

// ──────────────────────────────────────────────
// Sticky bottom bar
// ──────────────────────────────────────────────
class _StickyBar extends StatelessWidget {
  const _StickyBar({required this.controller});
  final TeamController controller;

  @override
  Widget build(BuildContext context) {
    final td = context.td;

    return Container(
      decoration: BoxDecoration(
        color: td.bg,
        border: Border(top: BorderSide(color: td.border)),
      ),
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (controller.saveError != null)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Text(
                'Error al guardar: ${controller.saveError}',
                style: TdText.ui(12, color: td.loss),
                textAlign: TextAlign.center,
              ),
            ),
          if (controller.savedOk)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Text(
                '¡Equipo guardado!',
                style: TdText.ui(
                  13,
                  weight: FontWeight.w600,
                  color: td.win,
                ),
                textAlign: TextAlign.center,
              ),
            ),
          TdButton(
            label: controller.isValid
                ? 'GUARDAR EQUIPO'
                : 'SELECCIONA 3 CAMPEONES',
            variant: TdButtonVariant.primary,
            loading: controller.isSaving,
            onPressed: controller.isValid && !controller.isSaving
                ? controller.save
                : null,
          ),
        ],
      ),
    );
  }
}
