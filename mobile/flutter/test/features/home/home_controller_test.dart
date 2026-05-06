import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:juego_mobile/features/home/presentation/controllers/home_controller.dart';
import 'package:juego_mobile/features/mvp/data/mvp_api_repository.dart';

void main() {
  group('HomeController', () {
    test('loads profile, missions and last three matches', () async {
      final mockClient = MockClient((request) async {
        if (request.url.path == '/v1/profile') {
          return http.Response(jsonEncode({'name': 'Ada', 'rank': 'Gold', 'mmrGlobal': 1412, 'level': 8, 'coins': 300, 'gems': 5, 'stats': {'matches': 7, 'wins': 5, 'losses': 2}}), 200);
        }
        if (request.url.path == '/v1/missions/daily') {
          return http.Response(jsonEncode({'summary': {'completed': 1, 'total': 3}, 'missions': [{'missionId': 'm1', 'title': 'Win 1', 'rewardXp': 20, 'rewardCoins': 10, 'target': 1, 'progress': 0, 'claimed': false, 'completed': false}]}), 200);
        }
        if (request.url.path == '/v1/history') {
          return http.Response(jsonEncode({'matches': [
            {'result': 'win', 'enemy': 'Bot A', 'turns': 3, 'mmrDelta': 10},
            {'result': 'lose', 'enemy': 'Bot B', 'turns': 5, 'mmrDelta': -7},
            {'result': 'win', 'enemy': 'Bot C', 'turns': 4, 'mmrDelta': 8},
            {'result': 'win', 'enemy': 'Bot D', 'turns': 6, 'mmrDelta': 11},
          ]}), 200);
        }
        return http.Response('{}', 404);
      });

      final api = MvpApiRepository(baseUrl: 'http://api.test', httpClient: mockClient);
      final controller = HomeController(api: api, token: 'token');

      await controller.load();

      expect(controller.isLoading, false);
      expect(controller.profile?.name, 'Ada');
      expect(controller.missions?.completed, 1);
      expect(controller.history.length, 3);
      expect(controller.profileError, isNull);
      expect(controller.missionsError, isNull);
      expect(controller.historyError, isNull);
    });

    test('keeps partial data when one module fails', () async {
      final mockClient = MockClient((request) async {
        if (request.url.path == '/v1/profile') {
          return http.Response(jsonEncode({'name': 'Ada', 'rank': 'Gold', 'mmrGlobal': 1412, 'level': 8, 'coins': 300, 'gems': 5, 'stats': {'matches': 7, 'wins': 5, 'losses': 2}}), 200);
        }
        if (request.url.path == '/v1/missions/daily') {
          return http.Response(jsonEncode({'error': {'code': 'MISSION_DOWN', 'message': 'fail'}}), 500);
        }
        if (request.url.path == '/v1/history') {
          return http.Response(jsonEncode({'matches': [{'result': 'win', 'enemy': 'Bot A', 'turns': 3, 'mmrDelta': 10}]}), 200);
        }
        return http.Response('{}', 404);
      });

      final api = MvpApiRepository(baseUrl: 'http://api.test', httpClient: mockClient);
      final controller = HomeController(api: api, token: 'token');

      await controller.load();

      expect(controller.profile?.name, 'Ada');
      expect(controller.history.length, 1);
      expect(controller.missions, isNull);
      expect(controller.missionsError, 'MISSION_DOWN');
      expect(controller.hasAnyData, true);
    });
  });
}
