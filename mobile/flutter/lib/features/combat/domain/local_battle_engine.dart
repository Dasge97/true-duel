import 'dart:math';

class ChampionDefinition {
  const ChampionDefinition({
    required this.id,
    required this.name,
    required this.baseHp,
    required this.baseAttack,
  });

  final String id;
  final String name;
  final int baseHp;
  final int baseAttack;
}

class MatchModifier {
  const MatchModifier({
    required this.id,
    required this.name,
    required this.description,
    required this.attackBonus,
    required this.abilityBonus,
  });

  final String id;
  final String name;
  final String description;
  final int attackBonus;
  final int abilityBonus;
}

class FighterState {
  FighterState({required this.champion, required this.maxHp}) : hp = maxHp;

  final ChampionDefinition champion;
  final int maxHp;
  int hp;
  int defenseCharges = 0;
}

class TurnLog {
  const TurnLog({
    required this.turn,
    required this.playerAction,
    required this.enemyAction,
    required this.playerDamage,
    required this.enemyDamage,
    required this.playerDefenseCharges,
    required this.enemyDefenseCharges,
  });

  final int turn;
  final String playerAction;
  final String enemyAction;
  final int playerDamage;
  final int enemyDamage;
  final int playerDefenseCharges;
  final int enemyDefenseCharges;
}

class BattleResult {
  const BattleResult({
    required this.winner,
    required this.turns,
    required this.rewards,
  });

  final String winner;
  final int turns;
  final MatchRewards rewards;
}

class MatchRewards {
  const MatchRewards({
    required this.coins,
    required this.gems,
    required this.globalMmrDelta,
    required this.championMmrDelta,
  });

  final int coins;
  final int gems;
  final int globalMmrDelta;
  final int championMmrDelta;
}

class LocalBattleEngine {
  LocalBattleEngine({required this.player, required this.enemy, required this.modifiers})
    : _rng = Random(),
      _player = FighterState(champion: player, maxHp: player.baseHp),
      _enemy = FighterState(champion: enemy, maxHp: enemy.baseHp);

  static const List<ChampionDefinition> roster = [
    ChampionDefinition(id: 'assassin', name: 'Assassin', baseHp: 90, baseAttack: 18),
    ChampionDefinition(id: 'bruiser', name: 'Bruiser', baseHp: 120, baseAttack: 14),
    ChampionDefinition(id: 'control', name: 'Control', baseHp: 100, baseAttack: 15),
    ChampionDefinition(id: 'sustain', name: 'Sustain', baseHp: 110, baseAttack: 13),
  ];

  static const List<MatchModifier> modifierPool = [
    MatchModifier(id: 'tempo', name: 'Tempo', description: '+2 ataque basico', attackBonus: 2, abilityBonus: 0),
    MatchModifier(id: 'focus', name: 'Focus', description: '+3 habilidades', attackBonus: 0, abilityBonus: 3),
    MatchModifier(id: 'sharp', name: 'Sharp Edge', description: '+1 ataque y +1 habilidad', attackBonus: 1, abilityBonus: 1),
    MatchModifier(id: 'ferocity', name: 'Ferocity', description: '+3 ataque basico', attackBonus: 3, abilityBonus: 0),
    MatchModifier(id: 'arcane', name: 'Arcane Pulse', description: '+2 habilidades', attackBonus: 0, abilityBonus: 2),
    MatchModifier(id: 'duelist', name: 'Duelist', description: '+1 ataque', attackBonus: 1, abilityBonus: 0),
    MatchModifier(id: 'burst', name: 'Burst', description: '+4 habilidades', attackBonus: 0, abilityBonus: 4),
    MatchModifier(id: 'discipline', name: 'Discipline', description: '+1 ataque y +2 habilidad', attackBonus: 1, abilityBonus: 2),
    MatchModifier(id: 'pressure', name: 'Pressure', description: '+2 ataque', attackBonus: 2, abilityBonus: 0),
    MatchModifier(id: 'mindgame', name: 'Mindgame', description: '+2 habilidad', attackBonus: 0, abilityBonus: 2),
  ];

  final ChampionDefinition player;
  final ChampionDefinition enemy;
  final List<MatchModifier> modifiers;
  final Random _rng;
  final FighterState _player;
  final FighterState _enemy;
  final List<TurnLog> turnLogs = [];
  int _turn = 1;
  static const int maxTurns = 16;

  FighterState get playerState => _player;
  FighterState get enemyState => _enemy;

  bool get isFinished => _player.hp <= 0 || _enemy.hp <= 0 || _turn > maxTurns;

  BattleResult finishResult() {
    final winner = _player.hp >= _enemy.hp ? 'player' : 'enemy';
    final victory = winner == 'player';
    return BattleResult(
      winner: winner,
      turns: turnLogs.length,
      rewards: MatchRewards(
        coins: victory ? 100 : 45,
        gems: 0,
        globalMmrDelta: victory ? 10 : -7,
        championMmrDelta: victory ? 8 : -5,
      ),
    );
  }

  TurnLog playTurn(String playerAction) {
    final enemyAction = _enemyAction();
    final playerDamage = _calculateDamage(attacker: _player, defender: _enemy, action: playerAction);
    final enemyDamage = _calculateDamage(attacker: _enemy, defender: _player, action: enemyAction);

    if (playerAction == 'Defender') {
      _player.defenseCharges = min(_player.defenseCharges + 1, 2);
    }
    if (enemyAction == 'Defender') {
      _enemy.defenseCharges = min(_enemy.defenseCharges + 1, 2);
    }

    _enemy.hp = max(0, _enemy.hp - playerDamage);
    if (_enemy.hp > 0) {
      _player.hp = max(0, _player.hp - enemyDamage);
    }

    final log = TurnLog(
      turn: _turn,
      playerAction: playerAction,
      enemyAction: enemyAction,
      playerDamage: playerDamage,
      enemyDamage: enemyDamage,
      playerDefenseCharges: _player.defenseCharges,
      enemyDefenseCharges: _enemy.defenseCharges,
    );
    turnLogs.add(log);
    _turn += 1;
    return log;
  }

  int _calculateDamage({required FighterState attacker, required FighterState defender, required String action}) {
    if (action == 'Defender') {
      return 0;
    }

    final atkBonus = modifiers.fold<int>(0, (sum, m) => sum + m.attackBonus);
    final abilityBonus = modifiers.fold<int>(0, (sum, m) => sum + m.abilityBonus);
    var rawDamage = attacker.champion.baseAttack + atkBonus;
    if (action == 'H1') {
      rawDamage += 6 + abilityBonus;
    } else if (action == 'H2') {
      rawDamage += 8 + abilityBonus;
    }

    var mitigation = 0;
    if (defender.defenseCharges > 0) {
      mitigation = 7;
      defender.defenseCharges -= 1;
    }

    return max(1, rawDamage - mitigation);
  }

  String _enemyAction() {
    const options = ['Ataque', 'H1', 'H2', 'Defender'];
    return options[_rng.nextInt(options.length)];
  }
}

List<MatchModifier> selectMvpModifiers() {
  final rng = Random();
  final pool = List<MatchModifier>.from(LocalBattleEngine.modifierPool)..shuffle(rng);
  return pool.take(3).toList(growable: false);
}
