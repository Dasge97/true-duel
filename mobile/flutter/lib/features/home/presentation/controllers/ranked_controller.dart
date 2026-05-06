import 'package:flutter/foundation.dart';

import '../../../mvp/data/mvp_api_repository.dart';

class RankedController extends ChangeNotifier {
  RankedController({required this.api, required this.token});

  final MvpApiRepository api;
  final String token;

  bool isLoading = true;
  String? errorCode;
  TicketStatusResult? activeTicket;
  ProfileResult? profile;

  Future<void> load() async {
    isLoading = true;
    errorCode = null;
    notifyListeners();
    try {
      profile = await api.profile(token);
      activeTicket = await api.latestActiveTicket(token);
    } on MvpApiException catch (e) {
      errorCode = e.code;
      profile = null;
      activeTicket = null;
    } catch (_) {
      errorCode = 'UNKNOWN';
      profile = null;
      activeTicket = null;
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }
}
