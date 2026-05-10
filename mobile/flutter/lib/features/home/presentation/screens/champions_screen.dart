import 'package:flutter/material.dart';

import '../../../../core/theme/duel_theme.dart';
import '../../../../core/widgets/async_state_card.dart';
import '../../../../core/widgets/td_button.dart';
import '../../../../core/widgets/td_champion_glyph.dart';
import '../../../../core/widgets/td_role_pill.dart';
import '../../../../features/mvp/data/mvp_api_repository.dart';
import '../controllers/champions_controller.dart';

// ──────────────────────────────────────────────
// Role filter options (champion catalog uses `role` field)
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

class ChampionsScreen extends StatefulWidget {
  const ChampionsScreen({super.key, required this.controller});

  final ChampionsController controller;

  @override
  State<ChampionsScreen> createState() => _ChampionsScreenState();
}

class _ChampionsScreenState extends State<ChampionsScreen> {
  ChampionsController get c => widget.controller;
  String _selectedRole = 'TODOS';

  @override
  void initState() {
    super.initState();
    c.load();
  }

  List<ChampionCatalogEntry> get _filtered {
    if (_selectedRole == 'TODOS') return c.champions;
    return c.champions
        .where((ch) => ch.role.toUpperCase() == _selectedRole)
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
              message: 'Error cargando campeones',
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
    final filtered = _filtered;

    return Column(
      children: [
        // ── Scrollable content ──
        Expanded(
          child: ListView(
            padding: const EdgeInsets.all(14),
            children: [
              // ── CATÁLOGO header ──
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                crossAxisAlignment: CrossAxisAlignment.baseline,
                textBaseline: TextBaseline.alphabetic,
                children: [
                  Text('CAMPEONES', style: TdText.display(22, color: td.fg)),
                  Text(
                    '${filtered.length} CAMPEONES',
                    style: TdText.mono(11, color: td.muted),
                  ),
                ],
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

              // ── Grid ──
              GridView.count(
                crossAxisCount: 3,
                mainAxisSpacing: 6,
                crossAxisSpacing: 6,
                childAspectRatio: 0.72,
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                children: filtered.map((ch) {
                  return GestureDetector(
                    onTap: () => _showDetail(context, ch),
                    child: _TdChampCard(
                      champion: ch,
                      isSelected: ch.id == c.selectedId,
                    ),
                  );
                }).toList(),
              ),
            ],
          ),
        ),

        // ── Sticky confirm button ──
        _StickyConfirm(controller: c),
      ],
    );
  }

  void _showDetail(BuildContext context, ChampionCatalogEntry ch) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _ChampionDetailSheet(
        champion: ch,
        isSelected: ch.id == c.selectedId,
        onSelect: () {
          c.select(ch.id);
          Navigator.of(context).pop();
        },
        onConfirm: () async {
          Navigator.of(context).pop();
          await c.confirm();
        },
      ),
    );
  }
}

// ──────────────────────────────────────────────
// Champion card for catalog grid
// ──────────────────────────────────────────────
class _TdChampCard extends StatelessWidget {
  const _TdChampCard({
    required this.champion,
    required this.isSelected,
  });

  final ChampionCatalogEntry champion;
  final bool isSelected;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final isLocked = !champion.owned;
    final roleCol = td.roleColor(champion.role);

    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFF16161C),
        border: Border.all(
          color: isSelected ? td.goldDeep : td.border,
        ),
      ),
      padding: const EdgeInsets.all(10),
      child: Stack(
        children: [
          Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              // Glyph
              Center(
                child: TdChampionGlyph(
                  initial: champion.name.isNotEmpty
                      ? champion.name[0].toUpperCase()
                      : '?',
                  size: 64,
                  accent: roleCol,
                  subtle: isLocked,
                ),
              ),
              const SizedBox(height: 6),
              // Name
              Text(
                champion.name,
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
              Center(child: TdRolePill(role: champion.role)),
              const SizedBox(height: 6),
              // Divider
              Container(height: 1, color: td.border),
              const SizedBox(height: 6),
              // Bottom row: cost dots + price/mastery
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  // Cost dots — masteryLevel used as a 1-3 cost indicator
                  Row(
                    children: List.generate(3, (i) {
                      final costLevel =
                          (champion.masteryLevel).clamp(1, 3);
                      final filled = (i + 1) <= costLevel;
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
                  if (isLocked)
                    Text(
                      '${champion.priceCoins} C',
                      style: TdText.mono(10, color: td.gold),
                    )
                  else if (isSelected)
                    Text(
                      'SELEC',
                      style: TdText.mono(10, color: td.gold),
                    )
                  else
                    Text(
                      'OWNED',
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
              child: Icon(Icons.lock, size: 12, color: td.muted),
            ),

          // Selected badge (top-left)
          if (isSelected)
            Positioned(
              top: 0,
              left: 0,
              child: Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
                color: td.gold,
                child: Text(
                  'SEL',
                  style: TdText.display(
                    10,
                    color: const Color(0xFF16110A),
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

// ──────────────────────────────────────────────
// Bottom sheet detail for ChampionCatalogEntry
// ──────────────────────────────────────────────
class _ChampionDetailSheet extends StatelessWidget {
  const _ChampionDetailSheet({
    required this.champion,
    required this.isSelected,
    required this.onSelect,
    required this.onConfirm,
  });

  final ChampionCatalogEntry champion;
  final bool isSelected;
  final VoidCallback onSelect;
  final VoidCallback onConfirm;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final roleCol = td.roleColor(champion.role);
    final isLocked = !champion.owned;

    const xpPerLevel = 300;
    final xpCurrent = champion.masteryXp % xpPerLevel;
    final progress = xpCurrent / xpPerLevel;

    return Container(
      constraints: BoxConstraints(
        maxHeight: MediaQuery.of(context).size.height * 0.78,
      ),
      decoration: BoxDecoration(
        color: const Color(0xFF16161C),
        border: Border(top: BorderSide(color: td.goldDeep, width: 1)),
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
                  // Header
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      TdChampionGlyph(
                        initial: champion.name.isNotEmpty
                            ? champion.name[0].toUpperCase()
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
                                    champion.name.toUpperCase(),
                                    style: TdText.display(32, color: td.fg),
                                  ),
                                ),
                                if (isSelected)
                                  Container(
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 6,
                                      vertical: 2,
                                    ),
                                    color: td.gold,
                                    child: Text(
                                      'SEL',
                                      style: TdText.display(
                                        14,
                                        color: const Color(0xFF16110A),
                                      ),
                                    ),
                                  ),
                              ],
                            ),
                            const SizedBox(height: 4),
                            TdRolePill(role: champion.role),
                            const SizedBox(height: 8),
                            // Cost dots
                            Row(
                              children: [
                                Text(
                                  'COSTE',
                                  style: TdText.eyebrow(color: td.muted),
                                ),
                                const SizedBox(width: 8),
                                Row(
                                  children: List.generate(3, (i) {
                                    final costLevel =
                                        (champion.masteryLevel).clamp(1, 3);
                                    final filled = (i + 1) <= costLevel;
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

                  // Mastery
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
                        '${champion.masteryLevel}',
                        style: TdText.display(38, color: td.gold),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
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

                  // MMR info
                  const SizedBox(height: 14),
                  Row(
                    children: [
                      Text('MMR', style: TdText.eyebrow(color: td.muted)),
                      const SizedBox(width: 10),
                      Text(
                        '${champion.mmr}',
                        style: TdText.mono(13, color: td.fg, weight: FontWeight.w700),
                      ),
                    ],
                  ),

                  const SizedBox(height: 20),

                  // Action buttons
                  if (isLocked)
                    TdButton(
                      label: 'BLOQUEADO · ${champion.priceCoins} monedas',
                      variant: TdButtonVariant.ghost,
                      onPressed: null,
                    )
                  else if (isSelected)
                    TdButton(
                      label: 'CONFIRMAR SELECCIÓN',
                      variant: TdButtonVariant.primary,
                      onPressed: onConfirm,
                    )
                  else
                    Row(
                      children: [
                        Expanded(
                          child: TdButton(
                            label: 'SELECCIONAR',
                            variant: TdButtonVariant.ghost,
                            small: true,
                            onPressed: onSelect,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: TdButton(
                            label: 'SELEC. Y CONFIRMAR',
                            variant: TdButtonVariant.primary,
                            onPressed: onConfirm,
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

// ──────────────────────────────────────────────
// Sticky confirm bar
// ──────────────────────────────────────────────
class _StickyConfirm extends StatelessWidget {
  const _StickyConfirm({required this.controller});
  final ChampionsController controller;

  @override
  Widget build(BuildContext context) {
    final td = context.td;

    return Container(
      decoration: BoxDecoration(
        color: td.bg,
        border: Border(top: BorderSide(color: td.border)),
      ),
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
      child: TdButton(
        key: const Key('champions-confirm-cta'),
        label: 'CONFIRMAR SELECCIÓN',
        variant: TdButtonVariant.primary,
        onPressed: controller.selectedId != null ? controller.confirm : null,
      ),
    );
  }
}
