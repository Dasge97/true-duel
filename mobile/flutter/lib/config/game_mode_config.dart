enum GameMode {
  offline,
  api,
}

class GameModeConfig {
  const GameModeConfig({
    required this.defaultMode,
    required this.apiBaseUrl,
    required this.apiToken,
  });

  final GameMode defaultMode;
  final String apiBaseUrl;
  final String apiToken;

  static const GameModeConfig current = GameModeConfig(
    defaultMode: GameMode.offline,
    apiBaseUrl: String.fromEnvironment(
      'API_BASE_URL',
      defaultValue: 'http://10.0.2.2:8080',
    ),
    apiToken: String.fromEnvironment('API_TOKEN', defaultValue: 'dev-token'),
  );
}
