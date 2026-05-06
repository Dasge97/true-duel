import 'package:flutter/foundation.dart';

import '../../../mvp/data/mvp_api_repository.dart';

class ProfileController extends ChangeNotifier {
  ProfileController({required this.api, required this.token});

  final MvpApiRepository api;
  final String token;

  bool isLoading = true;
  String? error;
  ProfileResult? profile;

  Future<void> load() async {
    isLoading = true;
    error = null;
    notifyListeners();
    try {
      profile = await api.profile(token);
    } on MvpApiException catch (e) {
      error = e.code;
    } catch (_) {
      error = 'UNKNOWN';
    }
    isLoading = false;
    notifyListeners();
  }
}
