import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/testing.dart';
import 'package:juego_mobile/features/home/presentation/controllers/champions_controller.dart';
import 'package:juego_mobile/features/home/presentation/controllers/shop_controller.dart';
import 'package:juego_mobile/features/home/presentation/screens/champions_screen.dart';
import 'package:juego_mobile/features/home/presentation/screens/shop_screen.dart';
import 'package:juego_mobile/features/mvp/data/mvp_api_repository.dart';

class _MutableCatalogApi extends MvpApiRepository {
  _MutableCatalogApi()
      : super(
          baseUrl: 'http://api.test',
          httpClient: MockClient((_) async => throw UnimplementedError()),
        );

  bool unlocked = false;
  bool selectedAssassin = false;
  bool purchasedSword = false;
  bool equippedSword = false;

  @override
  Future<List<ChampionCatalogEntry>> championCatalog(String token) async {
    return [
      ChampionCatalogEntry(
        id: 'assassin',
        name: 'Assassin',
        role: 'DPS',
        owned: unlocked,
        selected: selectedAssassin,
        priceCoins: 500,
        masteryLevel: 1,
        masteryXp: 0,
        mmr: 1300,
      ),
      const ChampionCatalogEntry(
        id: 'tank',
        name: 'Tank',
        role: 'Frontline',
        owned: true,
        selected: false,
        priceCoins: 0,
        masteryLevel: 2,
        masteryXp: 10,
        mmr: 1200,
      ),
    ];
  }

  @override
  Future<void> unlockChampion(String token, String championId) async {
    unlocked = true;
  }

  @override
  Future<void> selectChampion(String token, String championId) async {
    if (championId == 'assassin') {
      selectedAssassin = true;
    }
  }

  @override
  Future<StoreCatalogResult> storeCatalog(String token) async {
    return StoreCatalogResult(
      wallet: Wallet(coins: purchasedSword ? 300 : 500, gems: 3),
      items: [
        StoreItem(
          id: 'sword',
          name: 'Espada',
          type: 'weapon',
          owned: purchasedSword,
          equipped: equippedSword,
          priceCoins: 200,
          quantity: 1,
        ),
      ],
    );
  }

  @override
  Future<void> purchaseItem(String token, String itemId) async {
    purchasedSword = true;
  }

  @override
  Future<void> equipItem(String token, String itemId) async {
    equippedSword = true;
  }
}

void main() {
  testWidgets('champions confirm updates selection on same screen', (tester) async {
    final api = _MutableCatalogApi();
    final controller = ChampionsController(api: api, token: 'token');

    await tester.pumpWidget(
      MaterialApp(home: Scaffold(body: ChampionsScreen(controller: controller))),
    );
    await tester.pumpAndSettle();

    expect(find.text('500 C'), findsOneWidget);

    await tester.tap(find.byKey(const Key('champions-confirm-cta')));
    await tester.pumpAndSettle();

    expect(find.text('OWNED'), findsOneWidget);
    expect(find.text('SELEC'), findsOneWidget);
  });

  testWidgets('shop buy then equip stays visible on same screen', (tester) async {
    final api = _MutableCatalogApi();
    final controller = ShopController(api: api, token: 'token');

    await tester.pumpWidget(
      MaterialApp(home: Scaffold(body: ShopScreen(controller: controller))),
    );
    await tester.pumpAndSettle();

    expect(find.text('Comprar 200'), findsOneWidget);

    await tester.tap(find.byKey(const Key('shop-item-cta-sword')));
    await tester.pumpAndSettle();
    expect(find.text('Equipar'), findsOneWidget);

    await tester.tap(find.byKey(const Key('shop-item-cta-sword')));
    await tester.pumpAndSettle();
    expect(find.text('Equipado'), findsOneWidget);
  });
}
