import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;
import 'package:juego_mobile/core/theme/duel_theme.dart';

import '../../mvp/data/mvp_api_repository.dart';
import '../../play/presentation/controllers/queue_controller.dart';
import '../../play/presentation/screens/matchmaking_screen.dart';
import '../../play/presentation/visual_play_flow.dart';
import 'controllers/home_controller.dart';
import 'controllers/mochila_controller.dart';
import 'controllers/profile_controller.dart';
import 'controllers/ranked_controller.dart';
import 'controllers/shop_controller.dart';
import 'controllers/team_controller.dart';
import 'screens/home_screen.dart';
import 'screens/mochila_screen.dart';
import 'screens/profile_screen.dart';
import 'screens/ranked_screen.dart';
import 'screens/shop_screen.dart';
import 'screens/team_screen.dart';

// ── Tab model ────────────────────────────────────────────────────────────────

class _TabItem {
  const _TabItem({required this.label, required this.icon});
  final String label;
  final IconData icon;
}

const _tabs = [
  _TabItem(label: 'Mochila',    icon: Icons.inventory_2_outlined),
  _TabItem(label: 'Campeones',  icon: Icons.shield_outlined),
  _TabItem(label: 'Home',       icon: Icons.home_outlined),
  _TabItem(label: 'Tienda',     icon: Icons.store_outlined),
  _TabItem(label: 'Perfil',     icon: Icons.person_outline),
];

// ── Shell widget ─────────────────────────────────────────────────────────────

class ProductHomeShell extends StatefulWidget {
  const ProductHomeShell({
    super.key,
    required this.playerName,
    required this.rank,
    required this.mmr,
    required this.playerId,
    required this.token,
    required this.apiBaseUrl,
  });

  final String playerName;
  final String rank;
  final int mmr;
  final String playerId;
  final String token;
  final String apiBaseUrl;

  @override
  State<ProductHomeShell> createState() => _ProductHomeShellState();
}

class _ProductHomeShellState extends State<ProductHomeShell> {
  late final MvpApiRepository _api;
  late final HomeController _homeController;
  late final MochilaController _mochilaController;
  late final TeamController _teamController;
  late final ShopController _shopController;
  late final ProfileController _profileController;
  late final RankedController _rankedController;
  late final QueueController _queueController;
  int _tab = 2; // default to Home

  @override
  void initState() {
    super.initState();
    _api = MvpApiRepository(baseUrl: widget.apiBaseUrl, httpClient: http.Client());
    _homeController = HomeController(api: _api, token: widget.token);
    _mochilaController = MochilaController();
    _teamController = TeamController(api: _api, token: widget.token);
    _shopController = ShopController(api: _api, token: widget.token);
    _profileController = ProfileController(api: _api, token: widget.token);
    _rankedController = RankedController(api: _api, token: widget.token);
    _queueController = QueueController(api: _api, token: widget.token);
  }

  Future<void> _refreshHomeData() async {
    await Future.wait([
      _homeController.load(),
      _profileController.load(),
      _rankedController.load(),
    ]);
  }

  void _openPlay() {
    _openPlayFlow();
  }

  void _openRanked() {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => RankedScreen(controller: _rankedController),
      ),
    );
  }

  Future<void> _openPlayFlow() async {
    if (_teamController.catalog.isEmpty) {
      await _teamController.load();
    }
    final championId = _teamController.principalId ?? 'vanguard';
    final championName = _teamController.principalName ?? 'Tu equipo';

    if (!mounted) return;
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => MatchmakingScreen(
          controller: _queueController,
          championId: championId,
          championName: championName,
          playerRank: widget.rank,
          playerMmr: widget.mmr,
          onOpenMatch: _openCombat,
        ),
      ),
    );
    if (!mounted) return;
    await _refreshHomeData();
  }

  void _openCombat(String matchId) {
    _openCombatFlow(matchId);
  }

  Future<void> _openCombatFlow(String matchId) async {
    await Navigator.of(context).push(
      VisualPlayFlow.combatRoute(
        token: widget.token,
        api: _api,
        matchId: matchId,
        onContinue: () => Navigator.of(context).popUntil((route) => route.isFirst),
        onPlayAgain: () => Navigator.of(context).popUntil((route) => route.isFirst),
      ),
    );
    if (!mounted) return;
    await _refreshHomeData();
  }

  @override
  Widget build(BuildContext context) {
    final pages = [
      MochilaScreen(controller: _mochilaController),
      TeamScreen(controller: _teamController),
      HomeScreen(controller: _homeController, onPlay: _openPlay, onRankingTap: _openRanked),
      ShopScreen(controller: _shopController),
      ProfileScreen(controller: _profileController),
    ];

    // AnimatedBuilder wraps the whole scaffold so it is a proper ancestor of
    // every page in IndexedStack. Flutter allows marking an ancestor dirty
    // during the build of a descendant, which avoids the "setState called
    // during build" error when a child's initState fires notifyListeners().
    return AnimatedBuilder(
      animation: _homeController,
      builder: (context, _) {
        final profile = _homeController.profile;
        return Scaffold(
          body: Column(
            children: [
              _TdAppHeader(
                playerName: widget.playerName,
                rank: profile?.rank ?? widget.rank,
                mmr: profile?.mmr ?? widget.mmr,
                coins: profile?.coins ?? 0,
                gems: profile?.gems ?? 0,
              ),
              Expanded(child: IndexedStack(index: _tab, children: pages)),
            ],
          ),
          bottomNavigationBar: _TdBottomNav(
            selectedIndex: _tab,
            onTabSelected: (i) => setState(() => _tab = i),
          ),
        );
      },
    );
  }
}

// ── Header ───────────────────────────────────────────────────────────────────

class _TdAppHeader extends StatelessWidget {
  const _TdAppHeader({
    required this.playerName,
    required this.rank,
    required this.mmr,
    required this.coins,
    required this.gems,
  });

  final String playerName;
  final String rank;
  final int mmr;
  final int coins;
  final int gems;

  @override
  Widget build(BuildContext context) {
    final td = context.td;

    return Container(
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [Color(0xFF18181E), Color(0xFF14141A)],
        ),
        border: Border(
          bottom: BorderSide(color: td.border2),
        ),
      ),
      child: SafeArea(
        bottom: false,
        child: SizedBox(
          height: 56,
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Row(
              children: [
                // ── Avatar ───────────────────────────────────────────────
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [td.gold, td.goldDeep],
                      transform: const GradientRotation(2.356), // ~135 degrees
                    ),
                    border: Border.all(color: td.goldDeep),
                  ),
                  alignment: Alignment.center,
                  child: Text(
                    playerName.isNotEmpty ? playerName[0].toUpperCase() : '?',
                    style: GoogleFonts.bebasNeue(
                      fontSize: 20,
                      color: const Color(0xFF1A140A),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                // ── Name + meta ──────────────────────────────────────────
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        playerName,
                        style: GoogleFonts.inter(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: td.fg,
                          letterSpacing: 0.01,
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 1),
                      Row(
                        children: [
                          Text(
                            rank,
                            style: GoogleFonts.jetBrainsMono(
                              fontSize: 10,
                              fontWeight: FontWeight.w500,
                              color: td.gold,
                            ),
                          ),
                          Text(
                            ' · ',
                            style: GoogleFonts.jetBrainsMono(
                              fontSize: 10,
                              color: td.muted,
                            ),
                          ),
                          Text(
                            'MMR $mmr',
                            style: GoogleFonts.jetBrainsMono(
                              fontSize: 10,
                              fontWeight: FontWeight.w500,
                              color: td.muted,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                // ── Currency pills ───────────────────────────────────────
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    _CurrencyPill(type: _PillType.coin, amount: coins, td: td),
                    const SizedBox(width: 4),
                    _CurrencyPill(type: _PillType.gem,  amount: gems,  td: td),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

enum _PillType { coin, gem }

class _CurrencyPill extends StatelessWidget {
  const _CurrencyPill({
    required this.type,
    required this.amount,
    required this.td,
  });

  final _PillType type;
  final int amount;
  final DuelPalette td;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
      decoration: BoxDecoration(
        color: td.card,
        border: Border.all(color: td.border),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          _Dot(type: type, td: td),
          const SizedBox(width: 4),
          Text(
            '$amount',
            style: GoogleFonts.jetBrainsMono(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              color: td.fg,
            ),
          ),
        ],
      ),
    );
  }
}

class _Dot extends StatelessWidget {
  const _Dot({required this.type, required this.td});

  final _PillType type;
  final DuelPalette td;

  @override
  Widget build(BuildContext context) {
    if (type == _PillType.coin) {
      return Container(
        width: 10,
        height: 10,
        decoration: BoxDecoration(
          color: td.gold,
          shape: BoxShape.circle,
        ),
      );
    }
    // gem — diamond shape via 45° rotated square
    return Transform.rotate(
      angle: 0.785398, // 45 degrees in radians
      child: Container(
        width: 7,
        height: 7,
        color: td.info,
      ),
    );
  }
}

// ── Bottom nav ───────────────────────────────────────────────────────────────

class _TdBottomNav extends StatelessWidget {
  const _TdBottomNav({
    required this.selectedIndex,
    required this.onTabSelected,
  });

  final int selectedIndex;
  final ValueChanged<int> onTabSelected;

  @override
  Widget build(BuildContext context) {
    final td = context.td;

    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFF0F0F13),
        border: Border(
          top: BorderSide(color: td.border2),
        ),
      ),
      child: SafeArea(
        top: false,
        child: SizedBox(
          height: 56,
          child: Row(
            children: List.generate(_tabs.length, (i) {
              return Expanded(
                child: _TdNavItem(
                  tab: _tabs[i],
                  active: i == selectedIndex,
                  onTap: () => onTabSelected(i),
                  td: td,
                ),
              );
            }),
          ),
        ),
      ),
    );
  }
}

class _TdNavItem extends StatelessWidget {
  const _TdNavItem({
    required this.tab,
    required this.active,
    required this.onTap,
    required this.td,
  });

  final _TabItem tab;
  final bool active;
  final VoidCallback onTap;
  final DuelPalette td;

  @override
  Widget build(BuildContext context) {
    final itemColor = active ? td.gold : td.muted2;

    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: onTap,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          // Active indicator bar at top
          if (active)
            Container(
              height: 2,
              width: double.infinity,
              color: td.gold,
            )
          else
            const SizedBox(height: 2),
          const Spacer(),
          Icon(tab.icon, size: 22, color: itemColor),
          const SizedBox(height: 3),
          Text(
            tab.label.toUpperCase(),
            style: GoogleFonts.jetBrainsMono(
              fontSize: 9,
              letterSpacing: 1.44,
              color: itemColor,
            ),
          ),
          const Spacer(),
        ],
      ),
    );
  }
}
