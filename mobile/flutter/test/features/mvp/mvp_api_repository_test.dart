import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:juego_mobile/features/mvp/data/mvp_api_repository.dart';

void main() {
  group('MvpApiRepository queue contracts', () {
    test('enqueue sends new mode contract and maps response', () async {
      late Uri requestUri;
      late Map<String, dynamic> requestBody;

      final mockClient = MockClient((request) async {
        requestUri = request.url;
        requestBody = jsonDecode(request.body) as Map<String, dynamic>;
        return http.Response(
          jsonEncode({
            'ticketId': 'ticket-1',
            'status': 'queued',
            'queue': 'ranked_pvp',
            'matchId': null,
            'etaSec': 22,
            'region': 'eu-west',
          }),
          200,
        );
      });

      final repository = MvpApiRepository(baseUrl: 'http://api.test', httpClient: mockClient);
      final result = await repository.enqueue('token', 'ranked_pvp', 'assassin');

      expect(requestUri.path, '/v1/matchmaking/enqueue');
      expect(requestBody['mode'], 'ranked_pvp');
      expect(requestBody['queue'], 'ranked');
      expect(requestBody['vsBot'], false);
      expect(result.status, 'queued');
      expect(result.queue, 'ranked_pvp');
      expect(result.region, 'eu-west');
    });

    test('ticketStatus maps canonical fields', () async {
      final mockClient = MockClient((request) async {
        expect(request.url.path, '/v1/matchmaking/tickets/ticket-55');
        return http.Response(
          jsonEncode({
            'ticketId': 'ticket-55',
            'status': 'matched',
            'queue': 'ranked_pvp',
            'matchId': 'match-9',
            'etaSec': 0,
            'region': 'eu-west',
          }),
          200,
        );
      });

      final repository = MvpApiRepository(baseUrl: 'http://api.test', httpClient: mockClient);
      final ticket = await repository.ticketStatus('token', 'ticket-55');

      expect(ticket.ticketId, 'ticket-55');
      expect(ticket.status, 'matched');
      expect(ticket.matchId, 'match-9');
      expect(ticket.etaSec, 0);
    });
  });
}
