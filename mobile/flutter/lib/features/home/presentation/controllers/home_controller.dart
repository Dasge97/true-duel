import 'package:flutter/foundation.dart';

import '../../../mvp/data/mvp_api_repository.dart';

class HomeController extends ChangeNotifier {
  HomeController({required this.api, required this.token});

  final MvpApiRepository api;
  final String token;

  bool isLoading = true;
  String? profileError;
  String? missionsError;
  String? historyError;
  ProfileResult? profile;
  DailyMissionsResult? missions;
  List<HistoryEntry> history = const [];
  bool isOpeningPlay = false;

  bool get hasAnyData => profile != null || missions != null || history.isNotEmpty;

  Future<void> load() async {
    isLoading = true;
    profileError = null;
    missionsError = null;
    historyError = null;
    notifyListeners();

    try {
      profile = await api.profile(token);
    } on MvpApiException catch (e) {
      profileError = e.code;
    } catch (_) {
      profileError = 'UNKNOWN';
    }

    try {
      missions = await api.dailyMissions(token);
    } on MvpApiException catch (e) {
      missionsError = e.code;
      missions = null;
    } catch (_) {
      missionsError = 'UNKNOWN';
      missions = null;
    }

    try {
      history = (await api.history(token)).take(3).toList(growable: false);
    } on MvpApiException catch (e) {
      historyError = e.code;
      history = const [];
    } catch (_) {
      historyError = 'UNKNOWN';
      history = const [];
    }

    isLoading = false;
    notifyListeners();
  }

  Future<void> claim(String missionId) async {
    await api.claimMission(token, missionId);
    await load();
  }
}
