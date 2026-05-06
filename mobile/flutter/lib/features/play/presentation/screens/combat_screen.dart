import 'package:flutter/material.dart';

import '../controllers/combat_controller.dart';
import '../controllers/result_controller.dart';
import 'result_screen.dart';

class CombatScreen extends StatefulWidget {
  const CombatScreen({
    super.key,
    required this.controller,
    required this.onContinue,
    required this.onPlayAgain,
  });

  final CombatController controller;
  final VoidCallback onContinue;
  final VoidCallback onPlayAgain;

  @override
  State<CombatScreen> createState() => _CombatScreenState();
}

class _CombatScreenState extends State<CombatScreen> {
  @override
  void initState() {
    super.initState();
    widget.controller.load();
  }

  Future<void> _act(String action) async {
    final settlement = await widget.controller.act(action);
    if (!mounted || settlement == null) return;
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ResultScreen(
          controller: ResultController.fromSettlement(settlement),
          onContinue: widget.onContinue,
          onPlayAgain: widget.onPlayAgain,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Combate')),
      body: AnimatedBuilder(
        animation: widget.controller,
        builder: (context, _) {
          final c = widget.controller;
          if (c.isLoading) return const Center(child: CircularProgressIndicator());
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              const SizedBox.shrink(key: Key('combat-hud')),
              Text('Rival: ${c.enemyName}'),
              Text('Turno ${c.currentTurn}'),
              const SizedBox(height: 8),
              Text('${c.championName} HP: ${c.playerHp}'),
              Text('${c.enemyName} HP: ${c.enemyHp}'),
              Text('Cargas: ${c.playerCharges}/2'),
              Text('Defensa propia: ${c.playerDefending ? 'Activa' : 'Inactiva'}'),
              Text('Defensa rival: ${c.rivalDefending ? 'Activa' : 'Inactiva'}'),
              Text('Última acción rival: ${c.lastRivalAction}'),
              const SizedBox(height: 8),
              if (c.lastFeedback.isNotEmpty) Text(c.lastFeedback),
              if (c.errorCode != null) Text('Error: ${c.errorCode}'),
              const SizedBox(height: 12),
              Wrap(
                spacing: 8,
                children: [
                  FilledButton(onPressed: c.inputLocked ? null : () => _act('attack'), child: const Text('Atacar')),
                  FilledButton(onPressed: c.inputLocked ? null : () => _act('defend'), child: const Text('Defender')),
                  FilledButton(onPressed: c.inputLocked ? null : () => _act('special'), child: const Text('Especial')),
                ],
              ),
              const SizedBox(height: 16),
              const Text('Registro reciente (6)'),
              const SizedBox(height: 8),
              Column(
                key: const Key('combat-recent-log'),
                children: c.recentEvents.map((event) => ListTile(dense: true, title: Text(event))).toList(growable: false),
              ),
            ],
          );
        },
      ),
    );
  }
}
