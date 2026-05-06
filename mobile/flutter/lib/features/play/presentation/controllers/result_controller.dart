class ResultController {
  const ResultController({
    required this.result,
    required this.mmrDelta,
    required this.xp,
    required this.coins,
    required this.gems,
    required this.damageDealt,
    required this.damageTaken,
    required this.turns,
    required this.attackCount,
    required this.defendCount,
    required this.specialCount,
    required this.mitigationTotal,
  });

  factory ResultController.fromSettlement(Map<String, dynamic> settlement) {
    return ResultController(
      result: (settlement['result'] as String? ?? 'draw').toLowerCase(),
      mmrDelta: settlement['mmrDelta'] as int? ?? 0,
      xp: settlement['xp'] as int? ?? 0,
      coins: settlement['coins'] as int? ?? 0,
      gems: settlement['gems'] as int?,
      damageDealt: settlement['damageDealt'] as int? ?? 0,
      damageTaken: settlement['damageTaken'] as int? ?? 0,
      turns: settlement['turns'] as int? ?? 0,
      attackCount: settlement['attackCount'] as int? ?? 0,
      defendCount: settlement['defendCount'] as int? ?? 0,
      specialCount: settlement['specialCount'] as int? ?? 0,
      mitigationTotal: settlement['mitigationTotal'] as int? ?? 0,
    );
  }

  final String result;
  final int mmrDelta;
  final int xp;
  final int coins;
  final int? gems;
  final int damageDealt;
  final int damageTaken;
  final int turns;
  final int attackCount;
  final int defendCount;
  final int specialCount;
  final int mitigationTotal;

  bool get victory => result == 'victory' || result == 'win' || result == 'player';
}
