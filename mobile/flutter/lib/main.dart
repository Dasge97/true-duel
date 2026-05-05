import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

import 'config/game_mode_config.dart';
import 'core/theme/duel_theme.dart';
import 'features/home/presentation/product_home_shell.dart';
import 'features/mvp/data/mvp_api_repository.dart';
import 'features/play/presentation/visual_play_flow.dart';

void main() {
  runApp(const JuegoMvpApp());
}

class JuegoMvpApp extends StatelessWidget {
  const JuegoMvpApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'True Duel',
      theme: DuelTheme.dark(),
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
  });

  final String name;
  final String rank;
  final int mmr;
  final String playerId;
  final String token;
}

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _displayNameController = TextEditingController();
  final _usernameController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  late final MvpApiRepository _api;
  bool loading = false;
  bool registerMode = false;
  String? error;

  @override
  void initState() {
    super.initState();
    _api = MvpApiRepository(baseUrl: GameModeConfig.current.apiBaseUrl, httpClient: http.Client());
  }

  @override
  void dispose() {
    _displayNameController.dispose();
    _usernameController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() {
      loading = true;
      error = null;
    });

    try {
      final username = _usernameController.text.trim();
      final password = _passwordController.text;
      if (username.isEmpty || password.isEmpty) {
        setState(() => error = 'Usuario y contraseña obligatorios');
        return;
      }

      final login = registerMode
          ? await _api.register(
              username: username,
              email: _emailController.text.trim(),
              password: password,
              displayName: _displayNameController.text.trim(),
            )
          : await _api.login(
              username: username,
              password: password,
            );
      final profile = await _api.profile(login.token);
      final user = AppUser(
        name: profile.name,
        rank: profile.rank,
        mmr: profile.mmr,
        playerId: login.playerId,
        token: login.token,
      );

      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(
          builder: (_) => ProductHomeShell(
            playerName: user.name,
            rank: user.rank,
            mmr: user.mmr,
            playerId: user.playerId,
            token: user.token,
            apiBaseUrl: GameModeConfig.current.apiBaseUrl,
            onPlayTap: () {
              Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (_) => VisualChampionSelectScreen(
                    token: user.token,
                    api: _api,
                  ),
                ),
              );
            },
          ),
        ),
      );
    } on MvpApiException catch (e) {
      setState(() => error = 'API ${e.statusCode}: ${e.code} - ${e.message}');
    } finally {
      if (mounted) {
        setState(() => loading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.duel;
    return Scaffold(
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(18),
          children: [
            const SizedBox(height: 30),
            Icon(Icons.sports_martial_arts, size: 72, color: colors.neonGreen),
            const SizedBox(height: 14),
            Center(
              child: Text(
                'TRUE DUEL',
                style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w900),
              ),
            ),
            const SizedBox(height: 26),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: FilledButton.tonal(
                            onPressed: loading ? null : () => setState(() => registerMode = false),
                            child: const Text('Login'),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: FilledButton.tonal(
                            onPressed: loading ? null : () => setState(() => registerMode = true),
                            child: const Text('Register'),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 14),
                    if (registerMode) ...[
                      const Text('Nombre visible'),
                      const SizedBox(height: 8),
                      TextField(controller: _displayNameController),
                      const SizedBox(height: 12),
                    ],
                    const Text('Usuario'),
                    const SizedBox(height: 8),
                    TextField(controller: _usernameController),
                    if (registerMode) ...[
                      const SizedBox(height: 12),
                      const Text('Email'),
                      const SizedBox(height: 8),
                      TextField(controller: _emailController),
                    ],
                    const SizedBox(height: 12),
                    const Text('Contraseña'),
                    const SizedBox(height: 8),
                    TextField(controller: _passwordController, obscureText: true),
                    const SizedBox(height: 14),
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton(
                        onPressed: loading ? null : _submit,
                        style: FilledButton.styleFrom(
                          backgroundColor: colors.neonGreen,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 14),
                        ),
                        child: Text(
                          loading
                              ? (registerMode ? 'Creando cuenta...' : 'Entrando...')
                              : (registerMode ? 'Crear cuenta' : 'Entrar'),
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                      ),
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
    );
  }
}
