import 'package:flutter/foundation.dart';

import '../../../mvp/data/mvp_api_repository.dart';

class RankedController extends ChangeNotifier {
  RankedController({required this.api, required this.token});

  final MvpApiRepository api;
  final String token;

  bool isLoading = true;
  String? errorCode;
  ProfileResult? profile;
  List<RankingEntry> ranking = const [];

  Future<void> load() async {
    isLoading = true;
    errorCode = null;
    notifyListeners();
    try {
      profile = await api.profile(token);
      ranking = await api.ranking(token);
    } on MvpApiException catch (e) {
      errorCode = e.code;
      profile = null;
      ranking = const [];
    } catch (_) {
      errorCode = 'UNKNOWN';
      profile = null;
      ranking = const [];
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }
}
