import 'package:flutter/material.dart';

import '../../combat/data/combat_api_client.dart';

class QueueScreen extends StatefulWidget {
  const QueueScreen({
    super.key,
    required this.apiClient,
    required this.token,
    required this.playerId,
    required this.championId,
  });

  final CombatApiClient apiClient;
  final String token;
  final String playerId;
  final String championId;

  @override
  State<QueueScreen> createState() => _QueueScreenState();
}

class _QueueScreenState extends State<QueueScreen> {
  bool _loading = false;
  String _status = 'Pulsa para entrar en cola';

  Future<void> _enqueue(String queueType) async {
    setState(() {
      _loading = true;
      _status = 'Entrando en cola $queueType...';
    });

    try {
      final response = await widget.apiClient.enqueuePlayer(
        token: widget.token,
        queue: queueType,
        playerId: widget.playerId,
        championId: widget.championId,
        region: 'eu-west',
      );
      setState(() {
        _status = response.matchId == null
            ? 'Ticket ${response.ticketId} · ETA ${response.etaSec}s'
            : 'Match encontrado: ${response.matchId}';
      });
    } on CombatApiException catch (e) {
      setState(() {
        _status = 'Error cola: ${e.statusCode}';
      });
    } finally {
      setState(() {
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Queue')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(_status),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: _loading ? null : () => _enqueue('normal'),
              child: const Text('Cola normal'),
            ),
            const SizedBox(height: 8),
            FilledButton(
              onPressed: _loading ? null : () => _enqueue('ranked'),
              child: const Text('Cola ranked'),
            ),
          ],
        ),
      ),
    );
  }
}
