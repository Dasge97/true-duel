import 'package:flutter/material.dart';

import '../../mvp/data/mvp_api_repository.dart';
import 'controllers/combat_controller.dart';
import 'screens/combat_screen.dart';

class VisualPlayFlow {
  const VisualPlayFlow._();

  static Route<void> combatRoute({
    required String token,
    required MvpApiRepository api,
    required String matchId,
    required String championName,
    required VoidCallback onContinue,
    required VoidCallback onPlayAgain,
  }) {
    return MaterialPageRoute<void>(
      builder: (_) => CombatScreen(
        controller: CombatController(
          api: api,
          token: token,
          matchId: matchId,
          championName: championName,
        ),
        onContinue: onContinue,
        onPlayAgain: onPlayAgain,
      ),
    );
  }
}
