import 'dart:math' as math;

import 'package:flutter/material.dart';

import '../../../../core/theme/duel_theme.dart';
import '../../../../core/widgets/async_state_card.dart';
import '../../../../core/widgets/td_button.dart';
import '../../../mvp/data/mvp_api_repository.dart';
import '../controllers/shop_controller.dart';

class ShopScreen extends StatefulWidget {
  const ShopScreen({super.key, required this.controller});

  final ShopController controller;

  @override
  State<ShopScreen> createState() => _ShopScreenState();
}

class _ShopScreenState extends State<ShopScreen> {
  @override
  void initState() {
    super.initState();
    widget.controller.load();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: widget.controller,
      builder: (context, _) {
        final c = widget.controller;

        if (c.isLoading) {
          return const Center(child: AsyncStateCard.loading());
        }
        if (c.error != null || c.store == null) {
          return Center(
            child: AsyncStateCard.error(
              message: 'Error cargando tienda',
              onRetry: c.load,
            ),
          );
        }

        final store = c.store!;

        return ListView(
          padding: const EdgeInsets.all(14),
          children: [
            _WalletCard(wallet: store.wallet),
            const SizedBox(height: 14),
            _FeaturedOffer(),
            const SizedBox(height: 14),
            _CatalogHeader(),
            const SizedBox(height: 8),
            ...store.items.map(
              (item) => _ShopItemRow(
                key: Key('shop-item-${item.id}'),
                item: item,
                isMutating: c.isMutating,
                onAction: () => c.buyOrEquip(item),
              ),
            ),
          ],
        );
      },
    );
  }
}

// ---------------------------------------------------------------------------
// Wallet card
// ---------------------------------------------------------------------------

class _WalletCard extends StatelessWidget {
  const _WalletCard({required this.wallet});

  final Wallet wallet;

  @override
  Widget build(BuildContext context) {
    final td = context.td;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: td.card,
        border: Border.all(color: td.border2),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('CARTERA', style: TdText.eyebrow(color: td.muted)),
              const SizedBox(height: 6),
              Row(
                children: [
                  // Monedas
                  _CoinIcon(color: td.gold),
                  const SizedBox(width: 6),
                  Text(
                    _formatNumber(wallet.coins),
                    style: TdText.display(22, color: td.fg),
                  ),
                  const SizedBox(width: 16),
                  // Gemas
                  _GemIcon(color: const Color(0xFF5A8EC0)),
                  const SizedBox(width: 6),
                  Text(
                    _formatNumber(wallet.gems),
                    style: TdText.display(22, color: td.fg),
                  ),
                ],
              ),
            ],
          ),
          TdButton(
            label: '+ GEMAS',
            onPressed: () {},
            variant: TdButtonVariant.ghost,
            small: true,
            width: 100,
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Featured offer card
// ---------------------------------------------------------------------------

class _FeaturedOffer extends StatelessWidget {
  const _FeaturedOffer();

  @override
  Widget build(BuildContext context) {
    final td = context.td;

    return Container(
      padding: const EdgeInsets.all(18),
      clipBehavior: Clip.hardEdge,
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          transform: GradientRotation(135 * math.pi / 180),
          colors: [Color(0xFF1F1A12), Color(0xFF14141A)],
        ),
        border: Border.all(color: const Color(0x4DE5B567)),
      ),
      child: Stack(
        children: [
          Positioned.fill(child: CustomPaint(painter: _DiagonalStripesPainter())),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('OFERTA DESTACADA', style: TdText.eyebrow(color: td.gold)),
              const SizedBox(height: 4),
              Text('PASE TEMPORADA 2', style: TdText.display(36, color: td.fg)),
              const SizedBox(height: 4),
              Text(
                '40 niveles de recompensa exclusivos esta temporada',
                style: TdText.ui(12, color: td.fg2),
              ),
              const SizedBox(height: 14),
              Row(
                children: [
                  _GemIcon(color: const Color(0xFF5A8EC0), size: 10),
                  const SizedBox(width: 6),
                  Text('1 000', style: TdText.display(22, color: td.fg)),
                  const SizedBox(width: 12),
                  Text(
                    '1 400',
                    style: TdText.mono(11, color: td.muted).copyWith(
                      decoration: TextDecoration.lineThrough,
                      decorationColor: td.muted,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    '−30%',
                    style: TdText.mono(10, letterSpacing: 1.4, color: td.gold),
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Catalog header
// ---------------------------------------------------------------------------

class _CatalogHeader extends StatelessWidget {
  const _CatalogHeader();

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text('CATÁLOGO', style: TdText.display(22, color: td.fg)),
      ],
    );
  }
}

// ---------------------------------------------------------------------------
// Shop item row
// ---------------------------------------------------------------------------

class _ShopItemRow extends StatelessWidget {
  const _ShopItemRow({
    super.key,
    required this.item,
    required this.isMutating,
    required this.onAction,
  });

  final StoreItem item;
  final bool isMutating;
  final VoidCallback onAction;

  @override
  Widget build(BuildContext context) {
    final td = context.td;

    return Container(
      margin: const EdgeInsets.only(bottom: 6),
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: const Color(0xFF16161C),
        border: Border.all(color: td.border),
      ),
      child: Row(
        children: [
          // Miniatura 56×56
          _ItemThumbnail(item: item),
          const SizedBox(width: 12),
          // Info
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item.name,
                  style: TdText.ui(13, weight: FontWeight.w600, color: td.fg),
                ),
                const SizedBox(height: 2),
                Text(
                  item.type.toUpperCase(),
                  style: TdText.mono(10, letterSpacing: 1.0, color: td.muted),
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    _CoinIcon(color: td.gold),
                    const SizedBox(width: 4),
                    Text(
                      _formatNumber(item.priceCoins),
                      style: TdText.display(16, color: td.fg),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          // CTA
          _ItemCta(
            key: Key('shop-item-cta-${item.id}'),
            item: item,
            isMutating: isMutating,
            onAction: onAction,
          ),
        ],
      ),
    );
  }
}

class _ItemThumbnail extends StatelessWidget {
  const _ItemThumbnail({required this.item});

  final StoreItem item;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final initial = item.name.isNotEmpty ? item.name[0].toUpperCase() : '?';
    final color = _colorForType(td, item.type);

    return Container(
      width: 56,
      height: 56,
      decoration: BoxDecoration(
        color: td.surface,
        border: Border.all(color: td.border2),
      ),
      child: Center(
        child: Text(initial, style: TdText.display(26, color: color)),
      ),
    );
  }

  Color _colorForType(DuelPalette td, String type) {
    switch (type.toLowerCase()) {
      case 'skin':
        return td.info;
      case 'booster':
        return td.win;
      case 'season_pass':
      case 'pass':
        return td.gold;
      default:
        return td.gold;
    }
  }
}

class _ItemCta extends StatelessWidget {
  const _ItemCta({
    super.key,
    required this.item,
    required this.isMutating,
    required this.onAction,
  });

  final StoreItem item;
  final bool isMutating;
  final VoidCallback onAction;

  @override
  Widget build(BuildContext context) {
    final td = context.td;

    if (item.equipped) {
      return Text(
        'Equipado',
        style: TdText.mono(10, letterSpacing: 1.6, color: td.gold),
      );
    }

    if (item.owned) {
      return _ShopCtaButton(
        label: 'Equipar',
        onTap: isMutating ? null : onAction,
        bg: Colors.transparent,
        fg: td.fg,
        borderColor: td.borderStrong,
        width: 80,
      );
    }

    return _ShopCtaButton(
      label: 'Comprar ${item.priceCoins}',
      onTap: isMutating ? null : onAction,
      bg: td.gold,
      fg: const Color(0xFF16110A),
      borderColor: td.goldDeep,
      width: 100,
    );
  }
}

class _ShopCtaButton extends StatelessWidget {
  const _ShopCtaButton({
    required this.label,
    required this.onTap,
    required this.bg,
    required this.fg,
    required this.borderColor,
    required this.width,
  });

  final String label;
  final VoidCallback? onTap;
  final Color bg;
  final Color fg;
  final Color borderColor;
  final double width;

  @override
  Widget build(BuildContext context) {
    return Opacity(
      opacity: onTap == null ? 0.45 : 1.0,
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          width: width,
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
          decoration: BoxDecoration(
            color: bg,
            border: Border.all(color: borderColor),
          ),
          child: Text(
            label,
            textAlign: TextAlign.center,
            style: TdText.mono(11, letterSpacing: 0.8, color: fg),
          ),
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Icon helpers
// ---------------------------------------------------------------------------

class _CoinIcon extends StatelessWidget {
  const _CoinIcon({required this.color});

  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 8,
      height: 8,
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(4),
      ),
    );
  }
}

class _GemIcon extends StatelessWidget {
  const _GemIcon({required this.color, this.size = 8});

  final Color color;
  final double size;

  @override
  Widget build(BuildContext context) {
    return Transform.rotate(
      angle: math.pi / 4,
      child: Container(
        width: size,
        height: size,
        color: color,
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Diagonal stripes painter (oferta destacada)
// ---------------------------------------------------------------------------

class _DiagonalStripesPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = const Color(0x08E5B567)
      ..strokeWidth = 20
      ..style = PaintingStyle.stroke;

    const step = 36.0;
    for (double x = -size.height; x < size.width + size.height; x += step) {
      canvas.drawLine(Offset(x, 0), Offset(x + size.height, size.height), paint);
    }
  }

  @override
  bool shouldRepaint(_DiagonalStripesPainter oldDelegate) => false;
}

// ---------------------------------------------------------------------------
// Utility
// ---------------------------------------------------------------------------

String _formatNumber(int value) {
  if (value >= 1000) {
    final thousands = value ~/ 1000;
    final remainder = value % 1000;
    if (remainder == 0) return '$thousands 000';
    return '$thousands ${remainder.toString().padLeft(3, '0')}';
  }
  return value.toString();
}
