import 'package:flutter/material.dart';
import 'package:juego_mobile/core/theme/duel_theme.dart';
import '../controllers/mochila_controller.dart';

class MochilaScreen extends StatefulWidget {
  const MochilaScreen({super.key, required this.controller});

  final MochilaController controller;

  @override
  State<MochilaScreen> createState() => _MochilaScreenState();
}

class _MochilaScreenState extends State<MochilaScreen> {
  int _selectedFilter = 0;

  static const _filters = [
    'TODOS',
    'SKINS',
    'MARCOS',
    'BANNERS',
    'EMOTES',
    'TÍTULOS',
  ];

  @override
  Widget build(BuildContext context) {
    final td = context.td;

    return AnimatedBuilder(
      animation: widget.controller,
      builder: (context, _) {
        return Scaffold(
          backgroundColor: td.bg,
          body: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // ── Header card ─────────────────────────────────────────────
              Container(
                margin: const EdgeInsets.all(14),
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: td.card,
                  border: Border.all(color: td.border2),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    // Left: title + item count
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'MOCHILA',
                          style: TdText.eyebrow(color: td.gold),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          '0 ÍTEMS',
                          style: TdText.display(32, color: td.fg),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          '—',
                          style: TdText.mono(10, color: td.muted),
                        ),
                      ],
                    ),
                    // Right: wallet summary
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        _CurrencyRow(
                          type: _CurrencyType.coin,
                          amount: 0,
                          td: td,
                        ),
                        const SizedBox(height: 6),
                        _CurrencyRow(
                          type: _CurrencyType.gem,
                          amount: 0,
                          td: td,
                        ),
                      ],
                    ),
                  ],
                ),
              ),

              // ── Filter chips ─────────────────────────────────────────────
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 14),
                child: Row(
                  children: List.generate(_filters.length, (i) {
                    final active = i == _selectedFilter;
                    return Padding(
                      padding: EdgeInsets.only(right: i < _filters.length - 1 ? 6 : 0),
                      child: GestureDetector(
                        onTap: () => setState(() => _selectedFilter = i),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                          decoration: BoxDecoration(
                            color: active ? td.gold : Colors.transparent,
                            border: Border.all(
                              color: active ? td.gold : td.border2,
                            ),
                          ),
                          child: Text(
                            _filters[i],
                            style: TdText.mono(
                              10,
                              letterSpacing: 1.44,
                              color: active ? const Color(0xFF16110A) : td.muted,
                            ),
                          ),
                        ),
                      ),
                    );
                  }),
                ),
              ),

              const SizedBox(height: 8),

              // ── Empty state ──────────────────────────────────────────────
              Expanded(
                child: Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        width: 64,
                        height: 64,
                        decoration: BoxDecoration(
                          color: td.surface,
                          border: Border.all(color: td.border2),
                        ),
                        child: Icon(
                          Icons.inventory_2_outlined,
                          size: 32,
                          color: td.muted2,
                        ),
                      ),
                      const SizedBox(height: 16),
                      Text(
                        'INVENTARIO VACÍO',
                        style: TdText.display(24, color: td.muted),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'Los ítems que obtengas\naparecerán aquí.',
                        style: TdText.mono(11, color: td.muted2),
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 16),
                      Text(
                        'PRÓXIMAMENTE · CONECTAR BD',
                        style: TdText.eyebrow(color: td.muted2),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

// ── Currency indicator types ─────────────────────────────────────────────────

enum _CurrencyType { coin, gem }

class _CurrencyRow extends StatelessWidget {
  const _CurrencyRow({
    required this.type,
    required this.amount,
    required this.td,
  });

  final _CurrencyType type;
  final int amount;
  final DuelPalette td;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        _CurrencyDot(type: type, td: td),
        const SizedBox(width: 4),
        Text(
          '$amount',
          style: TdText.mono(11, weight: FontWeight.w700, color: td.fg),
        ),
      ],
    );
  }
}

class _CurrencyDot extends StatelessWidget {
  const _CurrencyDot({required this.type, required this.td});

  final _CurrencyType type;
  final DuelPalette td;

  @override
  Widget build(BuildContext context) {
    if (type == _CurrencyType.coin) {
      return Container(
        width: 10,
        height: 10,
        decoration: BoxDecoration(
          color: td.gold,
          shape: BoxShape.circle,
        ),
      );
    }
    // gem — rotated diamond
    return Transform.rotate(
      angle: 0.785398, // 45 degrees in radians
      child: Container(
        width: 8,
        height: 8,
        color: td.info,
      ),
    );
  }
}
