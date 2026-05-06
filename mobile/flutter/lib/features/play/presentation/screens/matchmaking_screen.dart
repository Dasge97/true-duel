import 'package:flutter/material.dart';

import '../controllers/queue_controller.dart';

class MatchmakingScreen extends StatefulWidget {
  const MatchmakingScreen({
    super.key,
    required this.controller,
    required this.championId,
    required this.championName,
    required this.onOpenMatch,
  });

  final QueueController controller;
  final String championId;
  final String championName;
  final ValueChanged<String> onOpenMatch;

  @override
  State<MatchmakingScreen> createState() => _MatchmakingScreenState();
}

class _MatchmakingScreenState extends State<MatchmakingScreen> {
  @override
  void initState() {
    super.initState();
    widget.controller.loadActiveTicket();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Matchmaking')),
      body: AnimatedBuilder(
        animation: widget.controller,
        builder: (context, _) {
          final c = widget.controller;
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Text('Campeón: ${widget.championName}'),
              const SizedBox(height: 12),
              DropdownButtonFormField<String>(
                initialValue: c.selectedMode,
                onChanged: c.modeLocked ? null : (value) {
                  if (value != null) c.selectMode(value);
                },
                items: QueueController.queueModes
                    .map((mode) => DropdownMenuItem<String>(value: mode, child: Text(mode)))
                    .toList(growable: false),
              ),
              const SizedBox(height: 12),
              Text(c.statusLabel),
              if (c.errorCode != null) ...[
                const SizedBox(height: 8),
                Text('Error: ${c.errorCode}'),
              ],
              const SizedBox(height: 16),
              if (c.canOpenMatch)
                FilledButton(
                  onPressed: () => widget.onOpenMatch(c.matchId!),
                  child: const Text('Ir a partida'),
                )
              else if (c.modeLocked)
                OutlinedButton(
                  onPressed: c.isBusy ? null : c.cancel,
                  child: const Text('Cancelar cola'),
                )
              else
                FilledButton(
                  onPressed: c.isBusy ? null : () => c.enqueue(championId: widget.championId),
                  child: const Text('Entrar en cola'),
                ),
            ],
          );
        },
      ),
    );
  }
}
