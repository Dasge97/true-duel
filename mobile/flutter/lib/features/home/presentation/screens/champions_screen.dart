import 'package:flutter/material.dart';

import '../../../../core/widgets/async_state_card.dart';
import '../../../../core/widgets/hero_cta.dart';
import '../controllers/champions_controller.dart';

class ChampionsScreen extends StatefulWidget {
  const ChampionsScreen({super.key, required this.controller});

  final ChampionsController controller;

  @override
  State<ChampionsScreen> createState() => _ChampionsScreenState();
}

class _ChampionsScreenState extends State<ChampionsScreen> {
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
        if (c.error != null) return Center(child: AsyncStateCard.error(message: 'Error cargando campeones', onRetry: c.load));
        return Column(
          children: [
            Expanded(
              child: ListView(
                children: c.champions
                    .map(
                      (ch) => ListTile(
                        selected: ch.id == c.selectedId,
                        onTap: () => c.select(ch.id),
                        title: Text(ch.name),
                        subtitle: Text('${ch.role} · MMR ${ch.mmr}'),
                        trailing: Text(ch.owned ? (ch.selected ? 'SELEC' : 'OWNED') : '${ch.priceCoins} C'),
                      ),
                    )
                    .toList(growable: false),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(16),
               child: HeroCta(key: const Key('champions-confirm-cta'), label: 'CONFIRMAR SELECCIÓN', onPressed: c.confirm),
             ),
          ],
        );
      },
    );
  }
}
