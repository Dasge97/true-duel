// Controlador placeholder para Mochila (inventario)
// Los datos reales se conectarán a la BD en el futuro
import 'package:flutter/material.dart';

class MochilaItem {
  const MochilaItem({
    required this.id,
    required this.name,
    required this.type,
    required this.rarity,
    this.equipped = false,
  });

  final String id;
  final String name;
  final String type;
  final String rarity;
  final bool equipped;
}

class MochilaController extends ChangeNotifier {
  // Datos mock hasta integración con BD
  List<MochilaItem> items = const [];
  bool isLoading = false;

  void load() {
    // placeholder — sin datos hasta integrar BD
    isLoading = false;
    notifyListeners();
  }
}
