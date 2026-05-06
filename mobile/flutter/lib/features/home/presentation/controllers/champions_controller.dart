import 'package:flutter/foundation.dart';

import '../../../mvp/data/mvp_api_repository.dart';

class ChampionsController extends ChangeNotifier {
  ChampionsController({required this.api, required this.token});

  final MvpApiRepository api;
  final String token;

  bool isLoading = true;
  String? error;
  List<ChampionCatalogEntry> champions = const [];
  String? selectedId;

  Future<void> load() async {
    isLoading = true;
    error = null;
    notifyListeners();
    try {
      champions = await api.championCatalog(token);
      selectedId = champions.firstWhere((c) => c.selected, orElse: () => champions.first).id;
    } on MvpApiException catch (e) {
      error = e.code;
    } catch (_) {
      error = 'UNKNOWN';
    }
    isLoading = false;
    notifyListeners();
  }

  void select(String id) {
    selectedId = id;
    notifyListeners();
  }

  Future<void> confirm() async {
    final selected = champions.where((c) => c.id == selectedId).firstOrNull;
    if (selected == null) {
      return;
    }
    if (!selected.owned) {
      await api.unlockChampion(token, selected.id);
    }
    await api.selectChampion(token, selected.id);
    await load();
  }
}
