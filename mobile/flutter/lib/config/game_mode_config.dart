class GameModeConfig {
  const GameModeConfig({
    required this.apiBaseUrl,
  });

  final String apiBaseUrl;

  static const GameModeConfig current = GameModeConfig(
    apiBaseUrl: String.fromEnvironment(
      'API_BASE_URL',
      defaultValue: 'http://10.0.2.2:8080',
    ),
  );
}
