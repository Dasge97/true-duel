import 'package:flutter/material.dart';

import '../../../core/theme/duel_theme.dart';
import '../../mvp/data/mvp_api_repository.dart';

class ProductHomeShell extends StatefulWidget {
  const ProductHomeShell({
    super.key,
    required this.playerName,
    required this.rank,
    required this.mmr,
    required this.playerId,
    required this.token,
    required this.apiBaseUrl,
    required this.onPlayTap,
  });

  final String playerName;
  final String rank;
  final int mmr;
  final String playerId;
  final String token;
  final String apiBaseUrl;
  final VoidCallback onPlayTap;

  @override
  State<ProductHomeShell> createState() => _ProductHomeShellState();
}

class _ProductHomeShellState extends State<ProductHomeShell> {
  int _currentIndex = 0;
  late final MvpApiRepository _api;

  @override
  void initState() {
    super.initState();
    _api = MvpApiRepository(baseUrl: widget.apiBaseUrl);
  }

  @override
  Widget build(BuildContext context) {
    final pages = <Widget>[
      _HomeTab(token: widget.token, api: _api, onPlayTap: widget.onPlayTap),
      _ChampionsTab(token: widget.token, api: _api),
      _RankedTab(token: widget.token, api: _api),
      _ShopTab(token: widget.token, api: _api),
      _ProfileTab(token: widget.token, api: _api),
    ];

    return Scaffold(
      body: SafeArea(child: pages[_currentIndex]),
      bottomNavigationBar: _BottomNav(
        currentIndex: _currentIndex,
        onChanged: (index) => setState(() => _currentIndex = index),
      ),
    );
  }
}

class _HomeTab extends StatefulWidget {
  const _HomeTab({
    required this.token,
    required this.api,
    required this.onPlayTap,
  });

  final String token;
  final MvpApiRepository api;
  final VoidCallback onPlayTap;

  @override
  State<_HomeTab> createState() => _HomeTabState();
}

class _HomeTabState extends State<_HomeTab> {
  late Future<(ProfileResult, DailyMissionsResult)> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<(ProfileResult, DailyMissionsResult)> _load() async {
    final profile = await widget.api.profile(widget.token);
    final missions = await widget.api.dailyMissions(widget.token);
    return (profile, missions);
  }

  Future<void> _claim(String missionId) async {
    try {
      await widget.api.claimMission(widget.token, missionId);
      if (!mounted) return;
      setState(() => _future = _load());
    } on MvpApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.code)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.duel;
    return FutureBuilder<(ProfileResult, DailyMissionsResult)>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snapshot.hasError || snapshot.data == null) {
          return _LoadError(onRetry: () => setState(() => _future = _load()));
        }

        final profile = snapshot.data!.$1;
        final missions = snapshot.data!.$2;
        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('¡Bienvenido,\n${profile.name}!', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800)),
                      const SizedBox(height: 6),
                      Text('Listo para la batalla', style: TextStyle(color: colors.textSecondary)),
                    ],
                  ),
                ),
                _SoftCard(child: Text('Rango: ${profile.rank}', style: const TextStyle(fontWeight: FontWeight.w700))),
              ],
            ),
            const SizedBox(height: 16),
            _SoftCard(
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: [
                  _MiniStat(label: 'MMR', value: '${profile.mmr}'),
                  _MiniStat(label: 'Nivel', value: '${profile.level}'),
                  _MiniStat(label: 'Monedas', value: '${profile.coins}'),
                ],
              ),
            ),
            const SizedBox(height: 20),
            SizedBox(
              height: 56,
              child: FilledButton.icon(
                style: FilledButton.styleFrom(backgroundColor: colors.neonGreen, foregroundColor: Colors.white),
                onPressed: widget.onPlayTap,
                icon: const Icon(Icons.sports_martial_arts),
                label: const Text('JUGAR AHORA', style: TextStyle(fontWeight: FontWeight.w800)),
              ),
            ),
            const SizedBox(height: 16),
            _SectionCard(
              title: 'Misiones Diarias',
              trailing: Text('${missions.completed}/${missions.total}', style: TextStyle(color: colors.neonGreen, fontWeight: FontWeight.w700)),
              child: Column(
                children: missions.missions
                    .map(
                      (m) => Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: _MissionRow(
                          title: m.title,
                          reward: '${m.rewardXp} XP · ${m.rewardCoins} C',
                          progress: m.target == 0 ? 0 : m.progress / m.target,
                          completed: m.completed || m.claimed,
                          actionLabel: (m.completed && !m.claimed) ? 'Cobrar' : null,
                          onActionTap: (m.completed && !m.claimed) ? () => _claim(m.missionId) : null,
                        ),
                      ),
                    )
                    .toList(growable: false),
              ),
            ),
          ],
        );
      },
    );
  }
}

class _ChampionsTab extends StatefulWidget {
  const _ChampionsTab({required this.token, required this.api});

  final String token;
  final MvpApiRepository api;

  @override
  State<_ChampionsTab> createState() => _ChampionsTabState();
}

class _ChampionsTabState extends State<_ChampionsTab> {
  late Future<List<ChampionCatalogEntry>> _future;
  String? _selectedId;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<List<ChampionCatalogEntry>> _load() => widget.api.championCatalog(widget.token);

  Future<void> _confirm(List<ChampionCatalogEntry> list) async {
    if (_selectedId == null) return;
    ChampionCatalogEntry? selected;
    for (final c in list) {
      if (c.id == _selectedId) {
        selected = c;
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
      setState(() => _future = _load());
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
        if (snapshot.connectionState != ConnectionState.done) return const Center(child: CircularProgressIndicator());
        if (snapshot.hasError || snapshot.data == null) return _LoadError(onRetry: () => setState(() => _future = _load()));
        final champions = snapshot.data!;
        if (champions.isEmpty) return const Center(child: Text('No hay campeones'));

        if (_selectedId == null) {
          for (final c in champions) {
            if (c.selected) {
              _selectedId = c.id;
              break;
            }
          }
          _selectedId ??= champions.first.id;
        }

        return Column(
          children: [
            _TopBar(title: 'Selecciona tu Campeón', subtitle: 'Catálogo real'),
            Expanded(
              child: ListView.separated(
                padding: const EdgeInsets.all(16),
                itemCount: champions.length,
                separatorBuilder: (_, __) => const SizedBox(height: 10),
                itemBuilder: (_, i) {
                  final c = champions[i];
                  final selected = c.id == _selectedId;
                  return InkWell(
                    onTap: () => setState(() => _selectedId = c.id),
                    child: _SoftCard(
                      borderColor: selected ? colors.neonGreen : null,
                      child: Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(c.name, style: const TextStyle(fontWeight: FontWeight.w800)),
                                const SizedBox(height: 4),
                                Text('${c.role} · MMR ${c.mmr} · Maestría ${c.masteryLevel}', style: TextStyle(color: colors.textSecondary)),
                              ],
                            ),
                          ),
                          Text(c.owned ? (c.selected ? 'SELEC' : 'OWNED') : '${c.priceCoins} C', style: TextStyle(color: c.owned ? colors.neonGreen : colors.accentOrange)),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(16),
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
        );
      },
    );
  }
}

class _RankedTab extends StatefulWidget {
  const _RankedTab({required this.token, required this.api});

  final String token;
  final MvpApiRepository api;

  @override
  State<_RankedTab> createState() => _RankedTabState();
}

class _RankedTabState extends State<_RankedTab> {
  late Future<(ProfileResult, List<RankingEntry>)> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<(ProfileResult, List<RankingEntry>)> _load() async {
    final profile = await widget.api.profile(widget.token);
    final ranking = await widget.api.ranking(widget.token);
    return (profile, ranking);
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.duel;
    return FutureBuilder<(ProfileResult, List<RankingEntry>)>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) return const Center(child: CircularProgressIndicator());
        if (snapshot.hasError || snapshot.data == null) return _LoadError(onRetry: () => setState(() => _future = _load()));
        final profile = snapshot.data!.$1;
        final ranking = snapshot.data!.$2;
        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Text('Clasificación Global', style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 12),
            _SectionCard(
              title: profile.rank,
              trailing: Text('${profile.mmr} LP', style: TextStyle(color: colors.accentOrange, fontWeight: FontWeight.w700)),
              child: Row(
                children: [
                  Expanded(child: _MiniStat(label: 'Victorias', value: '${profile.wins}')),
                  Expanded(child: _MiniStat(label: 'Derrotas', value: '${profile.losses}')),
                ],
              ),
            ),
            const SizedBox(height: 12),
            ...ranking.asMap().entries.map((entry) {
              final index = entry.key;
              final item = entry.value;
              return Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: _SoftCard(
                  child: Row(
                    children: [
                      Text('#${index + 1}', style: const TextStyle(fontWeight: FontWeight.w700)),
                      const SizedBox(width: 10),
                      Expanded(child: Text(item.name)),
                      Text('${item.mmr} LP', style: TextStyle(color: colors.accentOrange)),
                    ],
                  ),
                ),
              );
            }),
          ],
        );
      },
    );
  }
}

class _ShopTab extends StatefulWidget {
  const _ShopTab({required this.token, required this.api});

  final String token;
  final MvpApiRepository api;

  @override
  State<_ShopTab> createState() => _ShopTabState();
}

class _ShopTabState extends State<_ShopTab> {
  late Future<StoreCatalogResult> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<StoreCatalogResult> _load() => widget.api.storeCatalog(widget.token);

  Future<void> _buyOrEquip(StoreItem item) async {
    try {
      if (!item.owned) {
        await widget.api.purchaseItem(widget.token, item.id);
      } else if (!item.equipped) {
        await widget.api.equipItem(widget.token, item.id);
      }
      if (!mounted) return;
      setState(() => _future = _load());
    } on MvpApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.code)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.duel;
    return FutureBuilder<StoreCatalogResult>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) return const Center(child: CircularProgressIndicator());
        if (snapshot.hasError || snapshot.data == null) return _LoadError(onRetry: () => setState(() => _future = _load()));
        final store = snapshot.data!;
        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Row(
              children: [
                Expanded(child: Text('Tienda', style: Theme.of(context).textTheme.titleLarge)),
                Text('${store.wallet.coins} C', style: TextStyle(color: colors.accentOrange)),
              ],
            ),
            const SizedBox(height: 10),
            ...store.items.map((item) {
              final label = !item.owned ? 'Comprar ${item.priceCoins} C' : (item.equipped ? 'Equipado' : 'Equipar');
              return Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: _SoftCard(
                  child: Row(
                    children: [
                      Expanded(child: Text('${item.name} · ${item.type.toUpperCase()}')),
                      FilledButton.tonal(
                        onPressed: item.equipped ? null : () => _buyOrEquip(item),
                        child: Text(label),
                      ),
                    ],
                  ),
                ),
              );
            }),
          ],
        );
      },
    );
  }
}

class _ProfileTab extends StatefulWidget {
  const _ProfileTab({required this.token, required this.api});

  final String token;
  final MvpApiRepository api;

  @override
  State<_ProfileTab> createState() => _ProfileTabState();
}

class _ProfileTabState extends State<_ProfileTab> {
  late Future<ProfileResult> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.profile(widget.token);
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<ProfileResult>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) return const Center(child: CircularProgressIndicator());
        if (snapshot.hasError || snapshot.data == null) return _LoadError(onRetry: () => setState(() => _future = widget.api.profile(widget.token)));
        final p = snapshot.data!;
        final total = p.wins + p.losses;
        final ratio = total == 0 ? 0 : ((p.wins * 100) ~/ total);
        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            _SectionCard(
              title: p.name,
              subtitle: p.rank,
              child: Row(
                children: [
                  Expanded(child: _MiniStat(label: 'Nivel', value: '${p.level}')),
                  Expanded(child: _MiniStat(label: 'XP', value: '${p.experienceTotal}')),
                  Expanded(child: _MiniStat(label: 'MMR', value: '${p.mmr}')),
                ],
              ),
            ),
            const SizedBox(height: 10),
            _SectionCard(
              title: 'Estadísticas',
              child: Row(
                children: [
                  Expanded(child: _MiniStat(label: 'Partidas', value: '$total')),
                  Expanded(child: _MiniStat(label: 'Victorias', value: '${p.wins}')),
                  Expanded(child: _MiniStat(label: 'Winrate', value: '$ratio%')),
                ],
              ),
            ),
          ],
        );
      },
    );
  }
}

class _BottomNav extends StatelessWidget {
  const _BottomNav({required this.currentIndex, required this.onChanged});

  final int currentIndex;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) {
    final colors = context.duel;
    final items = const [
      (Icons.home_outlined, 'Inicio'),
      (Icons.groups_2_outlined, 'Campeones'),
      (Icons.emoji_events_outlined, 'Ranked'),
      (Icons.storefront_outlined, 'Tienda'),
      (Icons.person_outline, 'Perfil'),
    ];
    return Container(
      decoration: BoxDecoration(
        color: colors.surface,
        border: Border(top: BorderSide(color: colors.border)),
      ),
      child: SafeArea(
        top: false,
        child: Row(
          children: List.generate(items.length, (index) {
            final selected = index == currentIndex;
            final color = selected ? colors.neonGreen : colors.textSecondary;
            return Expanded(
              child: InkWell(
                onTap: () => onChanged(index),
                child: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 9),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(items[index].$1, color: color, size: 20),
                      const SizedBox(height: 4),
                      Text(items[index].$2, style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.w600)),
                    ],
                  ),
                ),
              ),
            );
          }),
        ),
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  const _SectionCard({
    required this.title,
    required this.child,
    this.trailing,
    this.subtitle,
  });

  final String title;
  final Widget child;
  final Widget? trailing;
  final String? subtitle;

  @override
  Widget build(BuildContext context) {
    final colors = context.duel;
    return _SoftCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, style: Theme.of(context).textTheme.titleMedium),
                    if (subtitle != null) ...[
                      const SizedBox(height: 4),
                      Text(subtitle!, style: TextStyle(color: colors.textSecondary)),
                    ],
                  ],
                ),
              ),
              if (trailing != null) trailing!,
            ],
          ),
          const SizedBox(height: 10),
          child,
        ],
      ),
    );
  }
}

class _SoftCard extends StatelessWidget {
  const _SoftCard({
    required this.child,
    this.padding = const EdgeInsets.all(14),
    this.borderColor,
  });

  final Widget child;
  final EdgeInsetsGeometry padding;
  final Color? borderColor;

  @override
  Widget build(BuildContext context) {
    final colors = context.duel;
    return DecoratedBox(
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: borderColor ?? colors.border),
      ),
      child: Padding(padding: padding, child: child),
    );
  }
}

class _TopBar extends StatelessWidget {
  const _TopBar({required this.title, required this.subtitle});

  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    final colors = context.duel;
    return Container(
      width: double.infinity,
      color: colors.surface,
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 2),
          Text(subtitle, style: TextStyle(color: colors.textSecondary)),
        ],
      ),
    );
  }
}

class _MissionRow extends StatelessWidget {
  const _MissionRow({
    required this.title,
    required this.reward,
    required this.progress,
    this.completed = false,
    this.actionLabel,
    this.onActionTap,
  });

  final String title;
  final String reward;
  final double progress;
  final bool completed;
  final String? actionLabel;
  final VoidCallback? onActionTap;

  @override
  Widget build(BuildContext context) {
    final colors = context.duel;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                title,
                style: TextStyle(
                  color: colors.textPrimary,
                  decoration: completed ? TextDecoration.lineThrough : null,
                ),
              ),
            ),
            Text(reward, style: TextStyle(color: colors.accentOrange, fontWeight: FontWeight.w700)),
            if (actionLabel != null) ...[
              const SizedBox(width: 8),
              FilledButton.tonal(onPressed: onActionTap, child: Text(actionLabel!)),
            ],
          ],
        ),
        const SizedBox(height: 6),
        _ProgressLine(progress: progress, green: true),
      ],
    );
  }
}

class _ProgressLine extends StatelessWidget {
  const _ProgressLine({required this.progress, this.green = false});

  final double progress;
  final bool green;

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
          widthFactor: progress.clamp(0, 1),
          child: Container(color: green ? colors.neonGreen : colors.accentOrange),
        ),
      ),
    );
  }
}

class _MiniStat extends StatelessWidget {
  const _MiniStat({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final colors = context.duel;
    return Column(
      children: [
        Text(value, style: const TextStyle(fontWeight: FontWeight.w800)),
        const SizedBox(height: 2),
        Text(label, style: TextStyle(color: colors.textSecondary, fontSize: 12)),
      ],
    );
  }
}

class _LoadError extends StatelessWidget {
  const _LoadError({required this.onRetry});

  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Text('Error cargando datos'),
          const SizedBox(height: 8),
          FilledButton.tonal(onPressed: onRetry, child: const Text('Reintentar')),
        ],
      ),
    );
  }
}
