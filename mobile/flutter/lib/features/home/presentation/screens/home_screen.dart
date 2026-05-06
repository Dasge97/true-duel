import 'package:flutter/material.dart';

import '../../../../core/widgets/async_state_card.dart';
import '../../../../core/widgets/hero_cta.dart';
import '../../../../core/widgets/stat_chip.dart';
import '../controllers/home_controller.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key, required this.controller, required this.onPlay});

  final HomeController controller;
  final VoidCallback onPlay;

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
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
        if (!c.hasAnyData) return Center(child: AsyncStateCard.error(message: 'Error cargando Home', onRetry: c.load));
        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            if (c.profile == null)
              AsyncStateCard.error(message: 'Perfil no disponible', onRetry: c.load)
            else ...[
              Text('¡Bienvenido, ${c.profile!.name}!', style: Theme.of(context).textTheme.titleLarge),
              const SizedBox(height: 12),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  StatChip(label: 'Rango', value: c.profile!.rank),
                  StatChip(label: 'MMR', value: '${c.profile!.mmr}'),
                  StatChip(label: 'Nivel', value: '${c.profile!.level}'),
                  StatChip(label: 'Monedas', value: '${c.profile!.coins}'),
                ],
              ),
            ],
            const SizedBox(height: 16),
            HeroCta(label: 'JUGAR AHORA', onPressed: widget.onPlay),
            const SizedBox(height: 16),
            if (c.missions == null)
              AsyncStateCard.error(message: 'Misiones no disponibles', onRetry: c.load)
            else ...[
              Text('Misiones ${c.missions!.completed}/${c.missions!.total}'),
              const SizedBox(height: 8),
              ...c.missions!.missions.map((m) => ListTile(title: Text(m.title), subtitle: Text('${m.rewardXp} XP · ${m.rewardCoins} C'))),
            ],
            const SizedBox(height: 12),
            Text('Últimas partidas', style: Theme.of(context).textTheme.titleMedium),
            if (c.history.isEmpty && c.historyError != null)
              AsyncStateCard.error(message: 'Historial no disponible', onRetry: c.load)
            else
              ...c.history.map((h) => ListTile(title: Text('${h.result.toUpperCase()} vs ${h.enemy}'), subtitle: Text('Turnos ${h.turns} · MMR ${h.mmrDelta}'))),
          ],
        );
      },
    );
  }
}
