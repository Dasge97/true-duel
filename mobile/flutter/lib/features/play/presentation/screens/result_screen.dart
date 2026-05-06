import 'package:flutter/material.dart';

import '../controllers/result_controller.dart';

class ResultScreen extends StatelessWidget {
  const ResultScreen({super.key, required this.controller, required this.onContinue, required this.onPlayAgain});

  final ResultController controller;
  final VoidCallback onContinue;
  final VoidCallback onPlayAgain;

  @override
  Widget build(BuildContext context) {
    final rewardSign = controller.mmrDelta >= 0 ? '+' : '';
    return Scaffold(
      appBar: AppBar(title: const Text('Resultado épico')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text(controller.victory ? '¡Victoria legendaria!' : 'Derrota honorable', style: Theme.of(context).textTheme.headlineSmall),
          const SizedBox(height: 16),
          const Text('Recompensas'),
          const SizedBox(height: 8),
          _metricRow('MMR', '$rewardSign${controller.mmrDelta}'),
          _metricRow('XP', '+${controller.xp}'),
          _metricRow('Monedas', '+${controller.coins}'),
          _metricRow('Gemas', controller.gems == null ? 'No disponible' : '+${controller.gems}'),
          const SizedBox(height: 16),
          const Text('Rendimiento'),
          const SizedBox(height: 8),
          _metricRow('Daño infligido', '${controller.damageDealt}'),
          _metricRow('Daño recibido', '${controller.damageTaken}'),
          _metricRow('Turnos', '${controller.turns}'),
          _metricRow('Ataques', '${controller.attackCount}'),
          _metricRow('Defensas', '${controller.defendCount}'),
          _metricRow('Especiales', '${controller.specialCount}'),
          _metricRow('Mitigación', '${controller.mitigationTotal}'),
          const SizedBox(height: 20),
          FilledButton(onPressed: onContinue, child: const Text('Continuar')),
          const SizedBox(height: 8),
          OutlinedButton(onPressed: onPlayAgain, child: const Text('Jugar de nuevo')),
        ],
      ),
    );
  }

  Widget _metricRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        children: [
          Expanded(child: Text(label)),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w700)),
        ],
      ),
    );
  }
}
