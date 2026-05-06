import 'package:flutter/material.dart';

import '../../../../core/theme/duel_theme.dart';
import '../controllers/result_controller.dart';

class ResultScreen extends StatelessWidget {
  const ResultScreen({super.key, required this.controller, required this.onContinue, required this.onPlayAgain});

  final ResultController controller;
  final VoidCallback onContinue;
  final VoidCallback onPlayAgain;

  @override
  Widget build(BuildContext context) {
    final duel = context.duel;
    final rewardSign = controller.mmrDelta >= 0 ? '+' : '';
    final isVictory = controller.victory;
    final isDraw = controller.result == 'draw';
    final accent = isDraw
        ? duel.warning
        : (isVictory ? duel.success : duel.danger);
    final title = isDraw
        ? 'Empate épico'
        : (isVictory ? '¡Victoria legendaria!' : 'Derrota honorable');
    final subtitle = isDraw
        ? 'La batalla terminó igualada'
        : (isVictory ? 'Has dominado el duelo' : 'Toca volver más fuerte');

    return Scaffold(
      appBar: AppBar(title: const Text('Resultado')),
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [duel.bg, duel.bgSoft],
          ),
        ),
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(16),
                color: duel.surface.withOpacity(0.9),
                border: Border.all(color: accent.withOpacity(0.5)),
              ),
              child: Row(
                children: [
                  Container(
                    width: 48,
                    height: 48,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: accent.withOpacity(0.2),
                    ),
                    child: Icon(
                      isDraw ? Icons.handshake_outlined : (isVictory ? Icons.emoji_events_outlined : Icons.shield_outlined),
                      color: accent,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(title, style: Theme.of(context).textTheme.titleLarge),
                        const SizedBox(height: 4),
                        Text(subtitle, style: TextStyle(color: duel.textSecondary)),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            _SectionCard(
              title: 'Recompensas',
              child: Column(
                children: [
                  _metricRow('MMR', '$rewardSign${controller.mmrDelta}', valueColor: controller.mmrDelta >= 0 ? duel.success : duel.danger),
                  _metricRow('XP', '+${controller.xp}', valueColor: duel.accentOrange),
                  _metricRow('Monedas', '+${controller.coins}', valueColor: duel.neonGreen),
                  _metricRow('Gemas', controller.gems == null ? 'No disponible' : '+${controller.gems}', valueColor: duel.textPrimary),
                ],
              ),
            ),
            const SizedBox(height: 12),
            _SectionCard(
              title: 'Rendimiento',
              child: Column(
                children: [
                  _metricRow('Daño infligido', '${controller.damageDealt}'),
                  _metricRow('Daño recibido', '${controller.damageTaken}'),
                  _metricRow('Turnos', '${controller.turns}'),
                  _metricRow('Ataques', '${controller.attackCount}'),
                  _metricRow('Defensas', '${controller.defendCount}'),
                  _metricRow('Especiales', '${controller.specialCount}'),
                  _metricRow('Mitigación', '${controller.mitigationTotal}'),
                ],
              ),
            ),
            const SizedBox(height: 20),
            FilledButton(onPressed: onContinue, child: const Text('Continuar')),
            const SizedBox(height: 8),
            OutlinedButton(onPressed: onPlayAgain, child: const Text('Jugar de nuevo')),
          ],
        ),
      ),
    );
  }

  Widget _metricRow(String label, String value, {Color? valueColor}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        children: [
          Expanded(child: Text(label)),
          Text(
            value,
            style: TextStyle(
              fontWeight: FontWeight.w700,
              color: valueColor,
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  const _SectionCard({
    required this.title,
    required this.child,
  });

  final String title;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    final duel = context.duel;
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(14),
        color: duel.surface.withOpacity(0.88),
        border: Border.all(color: duel.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: const TextStyle(fontWeight: FontWeight.w700)),
          const SizedBox(height: 8),
          child,
        ],
      ),
    );
  }
}
