import 'package:flutter/foundation.dart';

import '../../../mvp/data/mvp_api_repository.dart';

class RankedController extends ChangeNotifier {
  RankedController({required this.api, required this.token});

  final MvpApiRepository api;
  final String token;

  bool isLoading = true;
  String? errorCode;
  ProfileResult? profile;
  List<RankingEntry> rankingMmr = const [];
  List<RankingEntry> rankingSp = const [];

  Future<void> load() async {
    isLoading = true;
    errorCode = null;
    notifyListeners();
    try {
      final results = await Future.wait([
        api.profile(token),
        api.ranking(token),
        api.rankingSp(token),
      ]);
      profile = results[0] as ProfileResult;
      rankingMmr = results[1] as List<RankingEntry>;
      rankingSp = results[2] as List<RankingEntry>;
    } on MvpApiException catch (e) {
      errorCode = e.code;
      profile = null;
      rankingMmr = const [];
      rankingSp = const [];
    } catch (_) {
      errorCode = 'UNKNOWN';
      profile = null;
      rankingMmr = const [];
      rankingSp = const [];
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }
}
