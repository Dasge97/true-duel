import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

import '../../mvp/data/mvp_api_repository.dart';
import '../../play/presentation/controllers/queue_controller.dart';
import '../../play/presentation/screens/matchmaking_screen.dart';
import '../../play/presentation/visual_play_flow.dart';
import 'controllers/champions_controller.dart';
import 'controllers/home_controller.dart';
import 'controllers/profile_controller.dart';
import 'controllers/ranked_controller.dart';
import 'controllers/shop_controller.dart';
import 'screens/champions_screen.dart';
import 'screens/home_screen.dart';
import 'screens/profile_screen.dart';
import 'screens/ranked_screen.dart';
import 'screens/shop_screen.dart';

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
  late final ChampionsController _championsController;
  late final ShopController _shopController;
  late final ProfileController _profileController;
  late final RankedController _rankedController;
  late final QueueController _queueController;
  int _tab = 0;

  @override
  void initState() {
    super.initState();
    _api = MvpApiRepository(baseUrl: widget.apiBaseUrl, httpClient: http.Client());
    _homeController = HomeController(api: _api, token: widget.token);
    _championsController = ChampionsController(api: _api, token: widget.token);
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

  Future<void> _openPlayFlow() async {
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => MatchmakingScreen(
          controller: _queueController,
          championId: 'assassin',
          championName: 'Assassin',
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
        championName: 'Assassin',
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
      RankedScreen(controller: _rankedController),
      ChampionsScreen(controller: _championsController),
      HomeScreen(controller: _homeController, onPlay: _openPlay),
      ShopScreen(controller: _shopController),
      ProfileScreen(controller: _profileController),
    ];
    return Scaffold(
      appBar: AppBar(title: const Text('True Duel')),
      body: IndexedStack(index: _tab, children: pages),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: (value) => setState(() => _tab = value),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.emoji_events_outlined), label: 'Ranking'),
          NavigationDestination(icon: Icon(Icons.shield_outlined), label: 'Campeones'),
          NavigationDestination(icon: Icon(Icons.home_outlined), label: 'Home'),
          NavigationDestination(icon: Icon(Icons.storefront_outlined), label: 'Tienda'),
          NavigationDestination(icon: Icon(Icons.person_outline), label: 'Perfil'),
        ],
      ),
    );
  }
}
