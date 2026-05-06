import 'package:flutter/foundation.dart';

import '../../../mvp/data/mvp_api_repository.dart';

class ShopController extends ChangeNotifier {
  ShopController({required this.api, required this.token});

  final MvpApiRepository api;
  final String token;

  bool isLoading = true;
  String? error;
  StoreCatalogResult? store;
  bool isMutating = false;

  Future<void> load() async {
    isLoading = true;
    error = null;
    notifyListeners();
    try {
      store = await api.storeCatalog(token);
    } on MvpApiException catch (e) {
      error = e.code;
    } catch (_) {
      error = 'UNKNOWN';
    }
    isLoading = false;
    notifyListeners();
  }

  Future<void> buyOrEquip(StoreItem item) async {
    isMutating = true;
    notifyListeners();
    try {
      if (!item.owned) {
        await api.purchaseItem(token, item.id);
      } else if (!item.equipped) {
        await api.equipItem(token, item.id);
      }
      await load();
    } finally {
      isMutating = false;
      notifyListeners();
    }
  }
}
