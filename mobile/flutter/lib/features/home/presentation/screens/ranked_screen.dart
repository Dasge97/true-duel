import 'package:flutter/material.dart';

import '../controllers/ranked_controller.dart';

class RankedScreen extends StatefulWidget {
  const RankedScreen({
    super.key,
    required this.controller,
    required this.onOpenMatchmaking,
    required this.onOpenMatch,
  });

  final RankedController controller;
  final VoidCallback onOpenMatchmaking;
  final ValueChanged<String> onOpenMatch;

  @override
  State<RankedScreen> createState() => _RankedScreenState();
}

class _RankedScreenState extends State<RankedScreen> {
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
        if (c.isLoading) return const Center(child: CircularProgressIndicator());
        final ticket = c.activeTicket;
        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            const Text('Ranked', style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            if (c.profile != null) ...[
              Text('Rank: ${c.profile!.rank}'),
              Text('MMR: ${c.profile!.mmr}'),
              const SizedBox(height: 8),
            ],
            Text(ticket == null ? 'Sin ticket activo' : 'Estado: ${ticket.status} (${ticket.queue})'),
            const SizedBox(height: 16),
            if (ticket?.status == 'matched' && (ticket?.matchId?.isNotEmpty ?? false))
              FilledButton(
                onPressed: () => widget.onOpenMatch(ticket!.matchId!),
                child: const Text('Ir a partida'),
              )
            else
              FilledButton(
                onPressed: widget.onOpenMatchmaking,
                child: const Text('Entrar al ranked'),
              ),
          ],
        );
      },
    );
  }
}
