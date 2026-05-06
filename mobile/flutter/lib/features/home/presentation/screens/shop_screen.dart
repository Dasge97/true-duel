import 'package:flutter/material.dart';

import '../../../../core/widgets/async_state_card.dart';
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
        if (c.isLoading) return const Center(child: AsyncStateCard.loading());
        if (c.error != null || c.store == null) return Center(child: AsyncStateCard.error(message: 'Error cargando tienda', onRetry: c.load));
        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Text('Monedas: ${c.store!.wallet.coins} · Gemas: ${c.store!.wallet.gems}'),
            const SizedBox(height: 12),
            ...c.store!.items.map(
              (item) => ListTile(
                title: Text(item.name),
                subtitle: Text(item.type),
                trailing: FilledButton(
                  key: Key('shop-item-cta-${item.id}'),
                  onPressed: c.isMutating ? null : () => c.buyOrEquip(item),
                  child: Text(item.owned ? (item.equipped ? 'Equipado' : 'Equipar') : 'Comprar ${item.priceCoins}'),
                ),
              ),
            ),
          ],
        );
      },
    );
  }
}
