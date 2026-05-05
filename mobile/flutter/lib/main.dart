import 'dart:math';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

import 'config/game_mode_config.dart';
import 'features/combat/domain/local_battle_engine.dart';
import 'features/mvp/data/mvp_api_repository.dart';

void main() {
  runApp(const JuegoMvpApp());
}

class JuegoMvpApp extends StatelessWidget {
  const JuegoMvpApp({super.key});

  @override
  Widget build(BuildContext context) {
    final scheme = ColorScheme.fromSeed(
      seedColor: const Color(0xFF46C9FF),
      brightness: Brightness.dark,
    );
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'True Duel MVP',
      theme: ThemeData(
        colorScheme: scheme,
        scaffoldBackgroundColor: const Color(0xFF090D17),
        useMaterial3: true,
        cardTheme: const CardThemeData(
          color: Color(0xFF121A2B),
          elevation: 6,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.all(Radius.circular(18))),
        ),
      ),
      home: const LoginScreen(),
    );
  }
}

class AppUser {
  const AppUser({
    required this.name,
    required this.rank,
    required this.mmr,
    required this.playerId,
    required this.token,
    required this.apiMode,
  });

  final String name;
  final String rank;
  final int mmr;
  final String playerId;
  final String token;
  final bool apiMode;
}

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _nameController = TextEditingController(text: 'Player One');
  final _api = MvpApiRepository(baseUrl: GameModeConfig.current.apiBaseUrl, httpClient: http.Client());
  bool loading = false;
  String? error;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(18),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const SizedBox(height: 10),
              Text('TRUE DUEL', style: Theme.of(context).textTheme.headlineMedium),
              const SizedBox(height: 8),
              Text('Login rapido para entrar al duelo', style: TextStyle(color: Colors.blueGrey.shade200)),
              const SizedBox(height: 20),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(14),
                  child: Column(
                    children: [
                      TextField(
                        controller: _nameController,
                        decoration: const InputDecoration(labelText: 'Nombre de jugador'),
                      ),
                      const SizedBox(height: 12),
                      FilledButton(
                        onPressed: loading
                            ? null
                            : () async {
                                setState(() => loading = true);
                                await Future<void>.delayed(const Duration(milliseconds: 420));
                                 final enteredName = _nameController.text.trim().isEmpty ? 'Player One' : _nameController.text.trim();
                                 AppUser user;
                                 if (GameModeConfig.current.defaultMode == GameMode.api) {
                                   try {
                                     final login = await _api.login(enteredName);
                                     final profile = await _api.profile(login.token);
                                     user = AppUser(
                                       name: profile.name,
                                       rank: profile.rank,
                                       mmr: profile.mmr,
                                       playerId: login.playerId,
                                       token: login.token,
                                       apiMode: true,
                                     );
                                   } on MvpApiException catch (e) {
                                     user = AppUser(
                                       name: enteredName,
                                       rank: 'Silver II',
                                       mmr: 1210,
                                       playerId: 'local-player',
                                       token: '',
                                       apiMode: false,
                                     );
                                     error = 'API ${e.statusCode} ${e.code}. Modo offline activo.';
                                   }
                                 } else {
                                   user = AppUser(
                                     name: enteredName,
                                     rank: 'Silver II',
                                     mmr: 1210,
                                     playerId: 'local-player',
                                     token: '',
                                     apiMode: false,
                                   );
                                 }
                                 if (!context.mounted) return;
                                 Navigator.of(context).pushReplacement(
                                    MaterialPageRoute(
                                    builder: (_) => HomeMenuScreen(user: user),
                                  ),
                                );
                              },
                        child: Text(loading ? 'Entrando...' : 'Entrar'),
                      ),
                      if (error != null) ...[
                        const SizedBox(height: 8),
                        Text(error!, style: const TextStyle(color: Colors.orangeAccent)),
                      ],
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class HomeMenuScreen extends StatelessWidget {
  const HomeMenuScreen({super.key, required this.user});

  final AppUser user;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Home')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Card(
              child: ListTile(
                leading: const CircleAvatar(child: Icon(Icons.person)),
                title: Text(user.name),
                subtitle: Text('${user.rank} · ${user.mmr} MMR'),
              ),
            ),
            const SizedBox(height: 10),
            _MenuButton(
              icon: Icons.sports_martial_arts,
              title: 'Play',
              subtitle: '1v1 rápido con modificadores',
              onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ChampionSelectScreen(user: user))),
            ),
            _MenuButton(
              icon: Icons.person_outline,
              title: 'Profile',
              subtitle: 'Stats y progreso',
              onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ProfileScreen(user: user))),
            ),
            _MenuButton(
              icon: Icons.emoji_events_outlined,
              title: 'Ranking',
              subtitle: 'Global y por campeón',
              onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => RankingScreen(user: user))),
            ),
            _MenuButton(
              icon: Icons.groups_outlined,
              title: 'Users',
              subtitle: 'Jugadores online',
              onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => UsersScreen(user: user))),
            ),
            _MenuButton(
              icon: Icons.history,
              title: 'History',
              subtitle: 'Últimas partidas',
              onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => HistoryScreen(user: user))),
            ),
          ],
        ),
      ),
    );
  }
}

class ChampionSelectScreen extends StatefulWidget {
  const ChampionSelectScreen({super.key, required this.user});

  final AppUser user;

  @override
  State<ChampionSelectScreen> createState() => _ChampionSelectScreenState();
}

class _ChampionSelectScreenState extends State<ChampionSelectScreen> {
  ChampionDefinition? selected;

  @override
  void initState() {
    super.initState();
    selected = LocalBattleEngine.roster.first;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Champion Select')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text('Choose your champion', style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 10),
            Expanded(
              child: ListView.separated(
                itemBuilder: (_, i) {
                  final c = LocalBattleEngine.roster[i];
                  return ChampionCard(
                    champion: c,
                    selected: c.id == selected?.id,
                    onTap: () => setState(() => selected = c),
                  );
                },
                separatorBuilder: (_, __) => const SizedBox(height: 10),
                itemCount: LocalBattleEngine.roster.length,
              ),
            ),
            FilledButton(
              onPressed: selected == null
                  ? null
                  : () => Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => ModifierPhaseScreen(user: widget.user, champion: selected!),
                        ),
                      ),
              child: const Text('Continue'),
            ),
          ],
        ),
      ),
    );
  }
}

class ModifierPhaseScreen extends StatefulWidget {
  const ModifierPhaseScreen({super.key, required this.user, required this.champion});

  final AppUser user;
  final ChampionDefinition champion;

  @override
  State<ModifierPhaseScreen> createState() => _ModifierPhaseScreenState();
}

class _ModifierPhaseScreenState extends State<ModifierPhaseScreen> {
  late final List<MatchModifier> options;
  final List<MatchModifier> proposed = [];
  MatchModifier? selectedFinal;
  bool secondStep = false;

  @override
  void initState() {
    super.initState();
    options = List<MatchModifier>.from(LocalBattleEngine.modifierPool)..shuffle(Random());
  }

  @override
  Widget build(BuildContext context) {
    final visible = secondStep ? proposed : options.take(3).toList(growable: false);
    return Scaffold(
      appBar: AppBar(title: const Text('Modifier Phase')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(secondStep ? 'Step B: choose 1 modifier' : 'Step A: choose 2 modifiers'),
            const SizedBox(height: 10),
            Expanded(
              child: ListView.separated(
                itemCount: visible.length,
                separatorBuilder: (_, __) => const SizedBox(height: 10),
                itemBuilder: (_, i) {
                  final m = visible[i];
                  final selected = secondStep ? selectedFinal?.id == m.id : proposed.any((e) => e.id == m.id);
                  return ModifierCard(
                    modifier: m,
                    selected: selected,
                    onTap: () {
                      setState(() {
                        if (!secondStep) {
                          if (selected) {
                            proposed.removeWhere((e) => e.id == m.id);
                          } else if (proposed.length < 2) {
                            proposed.add(m);
                          }
                        } else {
                          selectedFinal = m;
                        }
                      });
                    },
                  );
                },
              ),
            ),
            FilledButton(
              onPressed: !secondStep
                  ? (proposed.length == 2
                      ? () => setState(() => secondStep = true)
                      : null)
                  : (selectedFinal == null
                      ? null
                      : () {
                           Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => QueueAndModeScreen(user: widget.user, champion: widget.champion, modifier: selectedFinal!)));
                         }),
              child: Text(secondStep ? 'Start Match' : 'Confirm 2 modifiers'),
            ),
          ],
        ),
      ),
    );
  }
}

class MatchScreen extends StatefulWidget {
  const MatchScreen({super.key, required this.player, required this.enemy, required this.modifier, required this.modeLabel});

  final ChampionDefinition player;
  final ChampionDefinition enemy;
  final MatchModifier modifier;
  final String modeLabel;

  @override
  State<MatchScreen> createState() => _MatchScreenState();
}

class _MatchScreenState extends State<MatchScreen> {
  late LocalBattleEngine engine;
  final List<String> logs = <String>[];

  @override
  void initState() {
    super.initState();
    engine = LocalBattleEngine(player: widget.player, enemy: widget.enemy, modifiers: [widget.modifier]);
    logs.add('Battle started under ${widget.modifier.name}.');
  }

  void act(String action) {
    if (engine.isFinished) return;
    final t = engine.playTurn(action);
    setState(() {
      logs.insert(0, 'T${t.turn} ${t.playerAction} vs ${t.enemyAction} · ${t.playerDamage}/${t.enemyDamage}');
      if (logs.length > 6) logs.removeLast();
    });
    if (!engine.isFinished) return;
    final result = engine.finishResult();
    Future<void>.delayed(const Duration(milliseconds: 420), () {
      if (!mounted) return;
      Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => ResultScreen(result: result)));
    });
  }

  @override
  Widget build(BuildContext context) {
    final p = engine.playerState;
    final e = engine.enemyState;
    return Scaffold(
      appBar: AppBar(title: Text('Match · ${widget.modeLabel}')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            FighterPanel(name: e.champion.name, hp: e.hp, maxHp: e.maxHp, statuses: [widget.modifier.name]),
            const SizedBox(height: 10),
            CombatLog(lines: logs),
            const SizedBox(height: 10),
            FighterPanel(name: p.champion.name, hp: p.hp, maxHp: p.maxHp, statuses: ['Charges ${p.defenseCharges}']),
            const Spacer(),
            Row(
              children: [
                Expanded(child: ActionButton(label: 'Attack', onTap: () => act('Ataque'))),
                const SizedBox(width: 8),
                Expanded(child: ActionButton(label: 'Skill 1', onTap: () => act('H1'))),
                const SizedBox(width: 8),
                Expanded(child: ActionButton(label: 'Skill 2', onTap: () => act('H2'))),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class ResultScreen extends StatelessWidget {
  const ResultScreen({super.key, required this.result});

  final BattleResult result;

  @override
  Widget build(BuildContext context) {
    final victory = result.winner == 'player';
    return Scaffold(
      appBar: AppBar(title: const Text('Result')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    Text(victory ? 'Victory' : 'Defeat', style: Theme.of(context).textTheme.headlineSmall),
                    const SizedBox(height: 12),
                    Text('Turns played: ${result.turns}'),
                    Text('Coins: ${result.rewards.coins}'),
                    Text('MMR: ${result.rewards.globalMmrDelta > 0 ? '+' : ''}${result.rewards.globalMmrDelta}'),
                  ],
                ),
              ),
            ),
            const Spacer(),
            FilledButton(
              onPressed: () => Navigator.of(context).pushAndRemoveUntil(
                MaterialPageRoute(
                  builder: (_) => HomeMenuScreen(user: const AppUser(name: 'Player One', rank: 'Silver II', mmr: 1210, playerId: 'local-player', token: '', apiMode: false)),
                ),
                (route) => false,
              ),
              child: const Text('Back to menu'),
            ),
          ],
        ),
      ),
    );
  }
}

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key, required this.user});

  final AppUser user;

  @override
  Widget build(BuildContext context) {
    final api = MvpApiRepository(baseUrl: GameModeConfig.current.apiBaseUrl, httpClient: http.Client());
    return Scaffold(
      appBar: AppBar(title: const Text('Profile')),
      body: FutureBuilder<ProfileResult>(
        future: user.apiMode ? api.profile(user.token) : Future<ProfileResult>.value(ProfileResult(name: user.name, rank: user.rank, mmr: user.mmr)),
        builder: (context, snapshot) {
          final profile = snapshot.data ?? ProfileResult(name: user.name, rank: user.rank, mmr: user.mmr);
          final fallbackNotice = snapshot.hasError ? 'Fallback local activo por error API.' : null;
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              if (fallbackNotice != null) Text(fallbackNotice, style: const TextStyle(color: Colors.orangeAccent)),
              Card(child: ListTile(title: Text(profile.name), subtitle: Text('${profile.rank} · ${profile.mmr} MMR'))),
              const SizedBox(height: 10),
              const Card(child: ListTile(title: Text('Matches'), subtitle: Text('124 played · 56% winrate'))),
              const Card(child: ListTile(title: Text('Main champion'), subtitle: Text('Assassin · 61% winrate'))),
            ],
          );
        },
      ),
    );
  }
}

class RankingScreen extends StatelessWidget {
  const RankingScreen({super.key, required this.user});

  final AppUser user;

  @override
  Widget build(BuildContext context) {
    final api = MvpApiRepository(baseUrl: GameModeConfig.current.apiBaseUrl, httpClient: http.Client());
    return Scaffold(
      appBar: AppBar(title: const Text('Ranking')),
      body: FutureBuilder<List<RankingEntry>>(
        future: user.apiMode ? api.ranking(user.token) : Future<List<RankingEntry>>.value([RankingEntry(name: 'Raven', mmr: 1450), RankingEntry(name: 'Nova', mmr: 1410), RankingEntry(name: user.name, mmr: user.mmr)]),
        builder: (context, snapshot) {
          final items = snapshot.data ?? [RankingEntry(name: 'Raven', mmr: 1450), RankingEntry(name: 'Nova', mmr: 1410), RankingEntry(name: user.name, mmr: user.mmr)];
          return ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: items.length,
            separatorBuilder: (_, __) => const SizedBox(height: 8),
            itemBuilder: (_, i) => Card(
              child: ListTile(
                leading: CircleAvatar(child: Text('#${i + 1}')),
                title: Text(items[i].name),
                trailing: Text('${items[i].mmr} MMR'),
              ),
            ),
          );
        },
      ),
    );
  }
}

class UsersScreen extends StatelessWidget {
  const UsersScreen({super.key, required this.user});

  final AppUser user;

  @override
  Widget build(BuildContext context) {
    final api = MvpApiRepository(baseUrl: GameModeConfig.current.apiBaseUrl, httpClient: http.Client());
    return Scaffold(
      appBar: AppBar(title: const Text('Users')),
      body: FutureBuilder<List<UserEntry>>(
        future: user.apiMode ? api.users(user.token) : Future<List<UserEntry>>.value(const [UserEntry(name: 'Nova', mmr: 1410), UserEntry(name: 'Raven', mmr: 1450), UserEntry(name: 'Luna', mmr: 1300)]),
        builder: (context, snapshot) {
          final items = snapshot.data ?? const [UserEntry(name: 'Nova', mmr: 1410), UserEntry(name: 'Raven', mmr: 1450), UserEntry(name: 'Luna', mmr: 1300)];
          return ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: items.length,
            itemBuilder: (_, i) => Card(
              child: ListTile(
                leading: const CircleAvatar(child: Icon(Icons.person)),
                title: Text(items[i].name),
                subtitle: Text('MMR ${items[i].mmr}'),
                trailing: FilledButton.tonal(onPressed: () {}, child: const Text('Inspect')),
              ),
            ),
          );
        },
      ),
    );
  }
}

class HistoryScreen extends StatelessWidget {
  const HistoryScreen({super.key, required this.user});

  final AppUser user;

  @override
  Widget build(BuildContext context) {
    final api = MvpApiRepository(baseUrl: GameModeConfig.current.apiBaseUrl, httpClient: http.Client());
    return Scaffold(
      appBar: AppBar(title: const Text('History')),
      body: FutureBuilder<List<HistoryEntry>>(
        future: user.apiMode ? api.history(user.token) : Future<List<HistoryEntry>>.value(const [HistoryEntry(result: 'win', enemy: 'Bruiser', turns: 8, mmrDelta: 10), HistoryEntry(result: 'loss', enemy: 'Control', turns: 11, mmrDelta: -7)]),
        builder: (context, snapshot) {
          final items = snapshot.data ?? const [HistoryEntry(result: 'win', enemy: 'Bruiser', turns: 8, mmrDelta: 10), HistoryEntry(result: 'loss', enemy: 'Control', turns: 11, mmrDelta: -7)];
          return ListView(
            padding: const EdgeInsets.all(16),
            children: items
                .map((e) => Card(child: ListTile(title: Text('${e.result.toUpperCase()} vs ${e.enemy}'), subtitle: Text('${e.turns} turns · ${e.mmrDelta >= 0 ? '+' : ''}${e.mmrDelta} MMR'))))
                .toList(growable: false),
          );
        },
      ),
    );
  }
}

class QueueAndModeScreen extends StatefulWidget {
  const QueueAndModeScreen({super.key, required this.user, required this.champion, required this.modifier});

  final AppUser user;
  final ChampionDefinition champion;
  final MatchModifier modifier;

  @override
  State<QueueAndModeScreen> createState() => _QueueAndModeScreenState();
}

class _QueueAndModeScreenState extends State<QueueAndModeScreen> {
  final _api = MvpApiRepository(baseUrl: GameModeConfig.current.apiBaseUrl, httpClient: http.Client());
  String status = 'Buscando partida real...';

  @override
  void initState() {
    super.initState();
    _start();
  }

  Future<void> _start() async {
    final enemy = LocalBattleEngine.roster.firstWhere((e) => e.id != widget.champion.id);
    if (!widget.user.apiMode) {
      setState(() => status = 'Modo local principal: partida vs bot.');
      _go(enemy, 'bot/offline');
      return;
    }

    try {
      final queue = await _api.enqueue(widget.user.token, 'ranked', widget.champion.id);
      if (queue.matchId == null || queue.matchId!.isEmpty) {
        setState(() => status = 'Sin match online real. Activando modo bot.');
        _go(enemy, 'bot/fallback');
        return;
      }
      setState(() => status = 'Match real asignado: ${queue.matchId}.');
      _go(enemy, 'online-ready');
    } catch (_) {
      setState(() => status = 'Error de cola API. Fallback a bot.');
      _go(enemy, 'bot/fallback');
    }
  }

  void _go(ChampionDefinition enemy, String modeLabel) {
    Future<void>.delayed(const Duration(milliseconds: 350), () {
      if (!mounted) return;
      Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => MatchScreen(player: widget.champion, enemy: enemy, modifier: widget.modifier, modeLabel: modeLabel)));
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Queue')),
      body: Center(child: Padding(padding: const EdgeInsets.all(16), child: Text(status))),
    );
  }
}

class ChampionCard extends StatelessWidget {
  const ChampionCard({super.key, required this.champion, required this.selected, required this.onTap});

  final ChampionDefinition champion;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(16),
      onTap: onTap,
      child: Card(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: BorderSide(color: selected ? Theme.of(context).colorScheme.primary : Colors.transparent, width: 1.5),
        ),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              const CircleAvatar(radius: 22, child: Icon(Icons.shield_moon_outlined)),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(champion.name, style: const TextStyle(fontWeight: FontWeight.w700)),
                    Text('HP ${champion.baseHp} · ATK ${champion.baseAttack}', style: TextStyle(color: Colors.blueGrey.shade200)),
                  ],
                ),
              ),
              if (selected) const Icon(Icons.check_circle, color: Color(0xFF46C9FF)),
            ],
          ),
        ),
      ),
    );
  }
}

class ModifierCard extends StatelessWidget {
  const ModifierCard({super.key, required this.modifier, required this.selected, required this.onTap});

  final MatchModifier modifier;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(16),
      onTap: onTap,
      child: Card(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: BorderSide(color: selected ? Theme.of(context).colorScheme.tertiary : Colors.transparent, width: 1.4),
        ),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(modifier.name), const SizedBox(height: 4), Text(modifier.description)]),
        ),
      ),
    );
  }
}

class ActionButton extends StatelessWidget {
  const ActionButton({super.key, required this.label, required this.onTap});

  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(boxShadow: [BoxShadow(color: Theme.of(context).colorScheme.primary.withValues(alpha: 0.25), blurRadius: 16)]),
      child: FilledButton(onPressed: onTap, child: Text(label)),
    );
  }
}

class FighterPanel extends StatelessWidget {
  const FighterPanel({super.key, required this.name, required this.hp, required this.maxHp, required this.statuses});

  final String name;
  final int hp;
  final int maxHp;
  final List<String> statuses;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [Text(name), Text('$hp/$maxHp')]),
            const SizedBox(height: 8),
            HealthBar(value: hp / maxHp),
            const SizedBox(height: 8),
            Wrap(spacing: 6, children: statuses.map((s) => StatusEffectBadge(text: s)).toList(growable: false)),
          ],
        ),
      ),
    );
  }
}

class HealthBar extends StatelessWidget {
  const HealthBar({super.key, required this.value});

  final double value;

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(999),
      child: LinearProgressIndicator(
        value: value.clamp(0, 1),
        minHeight: 10,
        backgroundColor: Colors.white12,
      ),
    );
  }
}

class StatusEffectBadge extends StatelessWidget {
  const StatusEffectBadge({super.key, required this.text});

  final String text;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(border: Border.all(color: Colors.white24), borderRadius: BorderRadius.circular(999), color: Colors.white10),
      child: Text(text, style: const TextStyle(fontSize: 12)),
    );
  }
}

class CombatLog extends StatelessWidget {
  const CombatLog({super.key, required this.lines});

  final List<String> lines;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: SizedBox(
        height: 120,
        child: ListView.builder(
          padding: const EdgeInsets.all(10),
          reverse: false,
          itemCount: lines.length,
          itemBuilder: (_, i) => Padding(
            padding: const EdgeInsets.only(bottom: 6),
            child: Text(lines[i], style: TextStyle(color: Colors.blueGrey.shade100)),
          ),
        ),
      ),
    );
  }
}

class _MenuButton extends StatelessWidget {
  const _MenuButton({required this.icon, required this.title, required this.subtitle, required this.onTap});

  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Card(
        child: ListTile(
          onTap: onTap,
          leading: Icon(icon),
          title: Text(title),
          subtitle: Text(subtitle),
          trailing: const Icon(Icons.chevron_right),
        ),
      ),
    );
  }
}
