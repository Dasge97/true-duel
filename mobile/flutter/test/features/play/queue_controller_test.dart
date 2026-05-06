import 'package:flutter_test/flutter_test.dart';
import 'package:http/testing.dart';
import 'package:juego_mobile/features/mvp/data/mvp_api_repository.dart';
import 'package:juego_mobile/features/play/presentation/controllers/queue_controller.dart';

class _FakeApi extends MvpApiRepository {
  _FakeApi()
      : super(
          baseUrl: 'http://api.test',
          httpClient: MockClient((_) async => throw UnimplementedError()),
        );

  TicketStatusResult? activeTicket;
  EnqueueResult enqueueResult = EnqueueResult(
    ticketId: 'ticket-1',
    etaSec: 12,
    matchId: null,
    status: 'queued',
    queue: 'normal_bot',
    region: 'eu-west',
  );
  TicketStatusResult polledTicket = const TicketStatusResult(
    ticketId: 'ticket-1',
    status: 'queued',
    queue: 'normal_bot',
    matchId: null,
    etaSec: 9,
    region: 'eu-west',
  );
  int cancelCalls = 0;

  @override
  Future<EnqueueResult> enqueue(String token, String queue, String championId, {bool vsBot = false}) async {
    return enqueueResult;
  }

  @override
  Future<TicketStatusResult> ticketStatus(String token, String ticketId) async {
    return polledTicket;
  }

  @override
  Future<TicketStatusResult?> latestActiveTicket(String token) async {
    return activeTicket;
  }

  @override
  Future<TicketStatusResult> cancelTicket(String token, String ticketId) async {
    cancelCalls++;
    return TicketStatusResult(
      ticketId: ticketId,
      status: 'cancelled',
      queue: 'normal_bot',
      matchId: null,
      etaSec: 0,
      region: 'eu-west',
    );
  }
}

void main() {
  group('QueueController', () {
    test('locks mode once queue is active', () async {
      final api = _FakeApi();
      final controller = QueueController(api: api, token: 'token');

      controller.selectMode('ranked_pvp');
      await controller.enqueue(championId: 'assassin');

      expect(controller.selectedMode, 'ranked_pvp');
      expect(controller.modeLocked, isTrue);
      expect(controller.statusLabel, 'En cola');
    });

    test('cancel clears active ticket and unlocks selector', () async {
      final api = _FakeApi();
      final controller = QueueController(api: api, token: 'token');

      await controller.enqueue(championId: 'assassin');
      await controller.cancel();

      expect(api.cancelCalls, 1);
      expect(controller.activeTicket, isNull);
      expect(controller.modeLocked, isFalse);
      expect(controller.statusLabel, 'Cola cancelada');
    });

    test('poll matched ticket exposes match transition', () async {
      final api = _FakeApi()
        ..polledTicket = const TicketStatusResult(
          ticketId: 'ticket-1',
          status: 'matched',
          queue: 'ranked_bot',
          matchId: 'match-77',
          etaSec: 0,
          region: 'eu-west',
        );
      final controller = QueueController(api: api, token: 'token');

      await controller.enqueue(championId: 'assassin');
      await controller.pollOnce();

      expect(controller.activeTicket?.status, 'matched');
      expect(controller.matchId, 'match-77');
      expect(controller.canOpenMatch, isTrue);
    });
  });
}
