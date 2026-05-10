import 'package:flutter/foundation.dart';

import '../../../mvp/data/mvp_api_repository.dart';

class QueueController extends ChangeNotifier {
  QueueController({required this.api, required this.token});

  final MvpApiRepository api;
  final String token;

  static const queueModes = ['normal_bot', 'ranked_pvp', 'ranked_bot'];

  String selectedMode = 'normal_bot';
  TicketStatusResult? activeTicket;
  bool isBusy = false;
  String? errorCode;
  String? _statusOverride;

  bool get modeLocked => activeTicket != null && (activeTicket!.status == 'creating' || activeTicket!.status == 'queued');
  String? get matchId => activeTicket?.matchId;
  bool get canOpenMatch => activeTicket?.status == 'matched' && (activeTicket?.matchId?.isNotEmpty ?? false);

  String get statusLabel {
    if (_statusOverride != null) return _statusOverride!;
    final status = activeTicket?.status;
    switch (status) {
      case 'creating':
        return 'Creando ticket';
      case 'queued':
        return 'En cola';
      case 'matched':
        return 'Partida encontrada';
      case 'cancelled':
        return 'Cola cancelada';
      case 'error':
        return 'Error en cola';
      default:
        return 'Sin cola activa';
    }
  }

  void selectMode(String mode) {
    if (modeLocked || !queueModes.contains(mode)) return;
    selectedMode = mode;
    notifyListeners();
  }

  Future<void> loadActiveTicket() async {
    isBusy = true;
    errorCode = null;
    _statusOverride = null;
    notifyListeners();
    try {
      activeTicket = await api.latestActiveTicket(token);
      if (activeTicket != null && queueModes.contains(activeTicket!.queue)) {
        selectedMode = activeTicket!.queue;
      }
    } on MvpApiException catch (e) {
      errorCode = e.code;
      activeTicket = null;
    } finally {
      isBusy = false;
      notifyListeners();
    }
  }

  Future<void> enqueue({required String championId}) async {
    isBusy = true;
    errorCode = null;
    _statusOverride = null;
    notifyListeners();
    try {
      final queued = await api.enqueue(token, selectedMode, championId);
      activeTicket = TicketStatusResult(
        ticketId: queued.ticketId,
        status: queued.status,
        queue: queued.queue,
        matchId: queued.matchId,
        etaSec: queued.etaSec,
        region: queued.region,
      );
    } on MvpApiException catch (e) {
      if (e.code == 'MATCH_ALREADY_ACTIVE') {
        // Ya existe una partida activa: cargamos el ticket para navegar a ella
        try {
          activeTicket = await api.latestActiveTicket(token);
          errorCode = null;
        } catch (_) {
          errorCode = e.code;
        }
      } else {
        errorCode = e.code;
      }
    } finally {
      isBusy = false;
      notifyListeners();
    }
  }

  Future<String?> resolvePlayableMatchId() async {
    final id = activeTicket?.matchId;
    if (id == null || id.isEmpty) return null;

    try {
      final match = await api.match(token, id);
      final status = (match['status'] as String? ?? '').toLowerCase();
      if (status == 'completed') {
        activeTicket = null;
        _statusOverride = 'Sin cola activa';
        errorCode = 'MATCH_FINISHED';
        notifyListeners();
        return null;
      }
      return id;
    } on MvpApiException catch (e) {
      activeTicket = null;
      _statusOverride = 'Sin cola activa';
      errorCode = e.code;
      notifyListeners();
      return null;
    }
  }

  Future<void> pollOnce() async {
    final ticketId = activeTicket?.ticketId;
    if (ticketId == null || ticketId.isEmpty) return;
    try {
      activeTicket = await api.ticketStatus(token, ticketId);
      notifyListeners();
    } on MvpApiException catch (e) {
      errorCode = e.code;
      notifyListeners();
    }
  }

  Future<void> cancel() async {
    final ticketId = activeTicket?.ticketId;
    if (ticketId == null || ticketId.isEmpty) return;
    isBusy = true;
    notifyListeners();
    try {
      await api.cancelTicket(token, ticketId);
      activeTicket = null;
      _statusOverride = 'Cola cancelada';
    } on MvpApiException catch (e) {
      errorCode = e.code;
    } finally {
      isBusy = false;
      notifyListeners();
    }
  }
}
