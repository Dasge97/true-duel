import 'dart:async';

import 'package:flutter/material.dart';

import '../../../core/theme/duel_theme.dart';
import '../../mvp/data/mvp_api_repository.dart';

class VisualChampionSelectScreen extends StatefulWidget {
  const VisualChampionSelectScreen({
    super.key,
    required this.token,
    required this.api,
  });

  final String token;
  final MvpApiRepository api;

  @override
  State<VisualChampionSelectScreen> createState() => _VisualChampionSelectScreenState();
}

class _VisualChampionSelectScreenState extends State<VisualChampionSelectScreen> {
  late Future<List<ChampionCatalogEntry>> _future;
  String? _selectedChampionId;

  @override
  void initState() {
    super.initState();
    _future = widget.api.championCatalog(widget.token);
  }

  Future<void> _confirm(List<ChampionCatalogEntry> champions) async {
    final id = _selectedChampionId;
    if (id == null) return;

    ChampionCatalogEntry? selected;
    for (final champion in champions) {
      if (champion.id == id) {
        selected = champion;
        break;
      }
    }
    if (selected == null) return;

    try {
      if (!selected.owned) {
        await widget.api.unlockChampion(widget.token, selected.id);
      }
      await widget.api.selectChampion(widget.token, selected.id);
      if (!mounted) return;
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => VisualMatchmakingScreen(
            token: widget.token,
            api: widget.api,
            champion: selected!,
          ),
        ),
      );
    } on MvpApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.code)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.duel;
    return FutureBuilder<List<ChampionCatalogEntry>>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Scaffold(body: Center(child: CircularProgressIndicator()));
        }
        if (snapshot.hasError || snapshot.data == null) {
          return Scaffold(
            body: Center(
              child: FilledButton.tonal(
                onPressed: () => setState(() => _future = widget.api.championCatalog(widget.token)),
                child: const Text('Error cargando campeones · Reintentar'),
              ),
            ),
          );
        }

        final champions = snapshot.data!;
        if (champions.isEmpty) {
          return const Scaffold(body: Center(child: Text('No hay campeones disponibles')));
        }

        _selectedChampionId ??= champions.first.id;
        return Scaffold(
          body: SafeArea(
            child: Column(
              children: [
                _TopBar(
                  title: 'Selecciona tu Campeón',
                  subtitle: 'Catálogo real',
                  onBack: () => Navigator.of(context).pop(),
                ),
                Expanded(
                  child: ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: champions.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 10),
                    itemBuilder: (_, index) {
                      final c = champions[index];
                      final selected = c.id == _selectedChampionId;
                      return InkWell(
                        borderRadius: BorderRadius.circular(12),
                        onTap: () => setState(() => _selectedChampionId = c.id),
                        child: _Panel(
                          selected: selected,
                          child: Row(
                            children: [
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(c.name, style: const TextStyle(fontWeight: FontWeight.w800)),
                                    const SizedBox(height: 4),
                                    Text('Rol ${c.role} · MMR ${c.mmr}', style: TextStyle(color: colors.textSecondary)),
                                    Text('Maestría ${c.masteryLevel} (${c.masteryXp} XP)', style: TextStyle(color: colors.textSecondary)),
                                  ],
                                ),
                              ),
                              Text(
                                c.owned ? (c.selected ? 'SELEC' : 'OWNED') : '${c.priceCoins} C',
                                style: TextStyle(color: c.owned ? colors.neonGreen : colors.accentOrange, fontWeight: FontWeight.w700),
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
                ),
                Container(
                  color: colors.surface,
                  padding: const EdgeInsets.fromLTRB(16, 10, 16, 16),
                  child: SizedBox(
                    width: double.infinity,
                    child: FilledButton(
                      onPressed: () => _confirm(champions),
                      style: FilledButton.styleFrom(backgroundColor: colors.neonGreen),
                      child: const Text('CONFIRMAR SELECCIÓN'),
                    ),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}

class VisualMatchmakingScreen extends StatefulWidget {
  const VisualMatchmakingScreen({
    super.key,
    required this.token,
    required this.api,
    required this.champion,
  });

  final String token;
  final MvpApiRepository api;
  final ChampionCatalogEntry champion;

  @override
  State<VisualMatchmakingScreen> createState() => _VisualMatchmakingScreenState();
}

class _VisualMatchmakingScreenState extends State<VisualMatchmakingScreen> {
  Timer? _timer;
  int _seconds = 0;
  String _status = 'Creando partida...';

  @override
  void initState() {
    super.initState();
    _timer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (!mounted) return;
      setState(() => _seconds++);
    });
    _start();
  }

  Future<void> _start() async {
    try {
      final queue = await widget.api.enqueue(widget.token, 'normal', widget.champion.id, vsBot: true);
      final matchId = queue.matchId;
      if (!mounted) return;
      if (matchId == null || matchId.isEmpty) {
        setState(() => _status = 'No se pudo crear partida');
        return;
      }
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(
          builder: (_) => VisualCombatScreen(
            token: widget.token,
            api: widget.api,
            matchId: matchId,
            championName: widget.champion.name,
          ),
        ),
      );
    } on MvpApiException catch (e) {
      if (!mounted) return;
      setState(() => _status = 'Error: ${e.code}');
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final minutes = (_seconds ~/ 60).toString().padLeft(1, '0');
    final secs = (_seconds % 60).toString().padLeft(2, '0');
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const SizedBox(height: 16),
              Text('Buscando oponente...', style: Theme.of(context).textTheme.titleLarge),
              const SizedBox(height: 8),
              Text(_status),
              const SizedBox(height: 10),
              Text('$minutes:$secs'),
              const SizedBox(height: 12),
              OutlinedButton(onPressed: () => Navigator.of(context).pop(), child: const Text('Cancelar')),
            ],
          ),
        ),
      ),
    );
  }
}

class VisualCombatScreen extends StatefulWidget {
  const VisualCombatScreen({
    super.key,
    required this.token,
    required this.api,
    required this.matchId,
    required this.championName,
  });

  final String token;
  final MvpApiRepository api;
  final String matchId;
  final String championName;

  @override
  State<VisualCombatScreen> createState() => _VisualCombatScreenState();
}

class _VisualCombatScreenState extends State<VisualCombatScreen> {
  int _playerHp = 100;
  int _enemyHp = 100;
  int _charges = 0;
  int _serverVersion = 1;
  bool _loading = true;
  String? _winner;

  @override
  void initState() {
    super.initState();
    _refresh();
  }

  Future<void> _refresh() async {
    try {
      final res = await widget.api.match(widget.token, widget.matchId);
      final state = res['state'] as Map<String, dynamic>? ?? const {};
      if (!mounted) return;
      setState(() {
        _playerHp = state['playerHp'] as int? ?? _playerHp;
        _enemyHp = state['enemyHp'] as int? ?? _enemyHp;
        _charges = state['playerCharges'] as int? ?? _charges;
        _serverVersion = state['serverStateVersion'] as int? ?? _serverVersion;
        _winner = state['winner'] as String?;
        _loading = false;
      });
    } on MvpApiException catch (e) {
      if (!mounted) return;
      setState(() => _loading = false);
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.code)));
    }
  }

  Future<void> _act(String action) async {
    if (_loading) return;
    setState(() => _loading = true);
    try {
      final res = await widget.api.resolveTurn(widget.token, widget.matchId, action, _serverVersion);
      final snapshot = res['snapshot'] as Map<String, dynamic>? ?? const {};
      final winner = snapshot['winner'] as String?;
      setState(() {
        _playerHp = snapshot['playerHp'] as int? ?? _playerHp;
        _enemyHp = snapshot['enemyHp'] as int? ?? _enemyHp;
        _charges = snapshot['playerCharges'] as int? ?? _charges;
        _serverVersion = snapshot['serverStateVersion'] as int? ?? _serverVersion;
        _winner = winner;
        _loading = false;
      });

      if (winner != null) {
        final result = await widget.api.completeMatch(widget.token, widget.matchId);
        final mmr = result['mmr'] as Map<String, dynamic>? ?? const {};
        final rewards = result['rewards'] as Map<String, dynamic>? ?? const {};
        if (!mounted) return;
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(
            builder: (_) => VisualResultScreen(
              victory: winner == 'player',
              mmrDelta: mmr['globalDelta'] as int? ?? 0,
              xp: rewards['xp'] as int? ?? 0,
              coins: rewards['coins'] as int? ?? 0,
              token: widget.token,
              api: widget.api,
            ),
          ),
        );
      }
    } on MvpApiException catch (e) {
      if (e.code == 'STATE_VERSION_CONFLICT') {
        await _refresh();
      } else {
        if (!mounted) return;
        setState(() => _loading = false);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.code)));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.duel;
    if (_loading && _winner == null) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            children: [
              _Panel(child: _Health(name: 'SparringBot', hp: _enemyHp)),
              const Spacer(),
              _Panel(child: _Health(name: widget.championName, hp: _playerHp, extra: 'Cargas: $_charges')),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: FilledButton.icon(
                      onPressed: _loading ? null : () => _act('attack'),
                      icon: const Icon(Icons.gpp_maybe_outlined),
                      label: const Text('Atacar'),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: FilledButton.icon(
                      onPressed: _loading ? null : () => _act('defend'),
                      style: FilledButton.styleFrom(backgroundColor: colors.surface),
                      icon: const Icon(Icons.shield_outlined),
                      label: const Text('Defender'),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class VisualResultScreen extends StatelessWidget {
  const VisualResultScreen({
    super.key,
    required this.victory,
    required this.mmrDelta,
    required this.xp,
    required this.coins,
    required this.token,
    required this.api,
  });

  final bool victory;
  final int mmrDelta;
  final int xp;
  final int coins;
  final String token;
  final MvpApiRepository api;

  @override
  Widget build(BuildContext context) {
    final colors = context.duel;
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.emoji_events_outlined, size: 84, color: victory ? colors.neonGreen : colors.danger),
                const SizedBox(height: 16),
                Text(victory ? '¡VICTORIA!' : 'DERROTA', style: Theme.of(context).textTheme.headlineMedium),
                const SizedBox(height: 12),
                _Panel(
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: [
                      _ResultStat(label: 'MMR', value: '${mmrDelta >= 0 ? '+' : ''}$mmrDelta'),
                      _ResultStat(label: 'XP', value: '+$xp'),
                      _ResultStat(label: 'Monedas', value: '+$coins'),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton(
                    onPressed: () => Navigator.of(context).popUntil((route) => route.isFirst),
                    style: FilledButton.styleFrom(backgroundColor: colors.neonGreen),
                    child: const Text('Continuar'),
                  ),
                ),
                const SizedBox(height: 8),
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton(
                    onPressed: () => Navigator.of(context).pushReplacement(
                      MaterialPageRoute(
                        builder: (_) => VisualChampionSelectScreen(
                          token: token,
                          api: api,
                        ),
                      ),
                    ),
                    child: const Text('Jugar de Nuevo'),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _Health extends StatelessWidget {
  const _Health({required this.name, required this.hp, this.extra});

  final String name;
  final int hp;
  final String? extra;

  @override
  Widget build(BuildContext context) {
    final colors = context.duel;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(name, style: const TextStyle(fontWeight: FontWeight.w700)),
        const SizedBox(height: 8),
        _Progress(value: hp / 100, orange: false),
        if (extra != null) ...[
          const SizedBox(height: 6),
          Text(extra!, style: TextStyle(color: colors.textSecondary)),
        ],
      ],
    );
  }
}

class _ResultStat extends StatelessWidget {
  const _ResultStat({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final colors = context.duel;
    return Column(
      children: [
        Text(value, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 18)),
        const SizedBox(height: 4),
        Text(label, style: TextStyle(color: colors.textSecondary)),
      ],
    );
  }
}

class _TopBar extends StatelessWidget {
  const _TopBar({required this.title, required this.subtitle, required this.onBack});

  final String title;
  final String subtitle;
  final VoidCallback onBack;

  @override
  Widget build(BuildContext context) {
    final colors = context.duel;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 12),
      color: colors.surface,
      child: Row(
        children: [
          IconButton(onPressed: onBack, icon: const Icon(Icons.arrow_back)),
          const SizedBox(width: 8),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: Theme.of(context).textTheme.titleMedium),
              const SizedBox(height: 2),
              Text(subtitle, style: TextStyle(color: colors.textSecondary)),
            ],
          ),
        ],
      ),
    );
  }
}

class _Panel extends StatelessWidget {
  const _Panel({required this.child, this.selected = false});

  final Widget child;
  final bool selected;

  @override
  Widget build(BuildContext context) {
    final colors = context.duel;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: selected ? colors.neonGreen : colors.border),
      ),
      child: child,
    );
  }
}

class _Progress extends StatelessWidget {
  const _Progress({required this.value, required this.orange});

  final double value;
  final bool orange;

  @override
  Widget build(BuildContext context) {
    final colors = context.duel;
    return Container(
      height: 8,
      decoration: BoxDecoration(color: colors.surfaceAlt, borderRadius: BorderRadius.circular(99)),
      clipBehavior: Clip.antiAlias,
      child: Align(
        alignment: Alignment.centerLeft,
        child: FractionallySizedBox(
          widthFactor: value.clamp(0, 1),
          child: Container(color: orange ? colors.accentOrange : colors.neonGreen),
        ),
      ),
    );
  }
}
