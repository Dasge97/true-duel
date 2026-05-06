import 'package:flutter/material.dart';

class AsyncStateCard extends StatelessWidget {
  const AsyncStateCard.loading({super.key, this.message = 'Cargando...'}) : _state = _AsyncState.loading, onRetry = null;
  const AsyncStateCard.error({super.key, required this.message, required this.onRetry}) : _state = _AsyncState.error;
  const AsyncStateCard.empty({super.key, required this.message}) : _state = _AsyncState.empty, onRetry = null;

  final _AsyncState _state;
  final String message;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    Widget body;
    switch (_state) {
      case _AsyncState.loading:
        body = Column(mainAxisSize: MainAxisSize.min, children: [const CircularProgressIndicator(), const SizedBox(height: 12), Text(message)]);
        break;
      case _AsyncState.error:
        body = Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            ElevatedButton(onPressed: onRetry, child: const Text('Reintentar')),
          ],
        );
        break;
      case _AsyncState.empty:
        body = Text(message, textAlign: TextAlign.center);
        break;
    }

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Center(child: body),
      ),
    );
  }
}

enum _AsyncState { loading, error, empty }
