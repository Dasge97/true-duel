import 'package:flutter/foundation.dart';

import '../../../../features/mvp/data/mvp_api_repository.dart';

class TeamController extends ChangeNotifier {
  TeamController({required this.api, required this.token});

  final MvpApiRepository api;
  final String token;

  List<PersonajeEntry> catalog = const [];
  final List<String?> slots = [null, null, null];
  int activeSlot = 0;
  bool isLoading = false;
  bool isSaving = false;
  String? error;
  String? saveError;
  bool savedOk = false;

  bool get isValid => slots.every((s) => s != null) && slots.whereType<String>().toSet().length == 3;

  String? get principalId => slots[0];

  String? get principalName {
    final id = slots[0];
    if (id == null) return null;
    return catalog.where((p) => p.id == id).firstOrNull?.nombre;
  }

  Future<void> load() async {
    isLoading = true;
    error = null;
    notifyListeners();
    try {
      final results = await Future.wait([
        api.personajesCatalog(token),
        api.teamGet(token),
      ]);
      catalog = results[0] as List<PersonajeEntry>;
      final team = results[1] as EquipoResult;
      slots[0] = null;
      slots[1] = null;
      slots[2] = null;
      for (final s in team.slots) {
        final idx = s.slot - 1;
        if (idx >= 0 && idx < 3 && s.personajeId.isNotEmpty) {
          slots[idx] = s.personajeId;
        }
      }
      // set active slot to first empty
      final firstEmpty = slots.indexWhere((s) => s == null);
      activeSlot = firstEmpty == -1 ? 0 : firstEmpty;
    } catch (e) {
      error = e.toString();
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  void selectSlot(int slot) {
    activeSlot = slot;
    notifyListeners();
  }

  void toggleChampion(String id) {
    final existingIdx = slots.indexWhere((s) => s == id);
    if (existingIdx != -1) {
      slots[existingIdx] = null;
      notifyListeners();
      return;
    }
    slots[activeSlot] = id;
    _advanceToNextEmptySlot();
    notifyListeners();
  }

  void _advanceToNextEmptySlot() {
    for (int i = 0; i < 3; i++) {
      if (slots[i] == null) {
        activeSlot = i;
        return;
      }
    }
  }

  Future<void> save() async {
    if (!isValid) return;
    isSaving = true;
    saveError = null;
    savedOk = false;
    notifyListeners();
    try {
      await api.teamSave(token, slots.whereType<String>().toList());
      savedOk = true;
    } catch (e) {
      saveError = e.toString();
    } finally {
      isSaving = false;
      notifyListeners();
    }
  }
}
