import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:http/http.dart' as http;

import 'config/game_mode_config.dart';
import 'core/theme/duel_theme.dart';
import 'core/widgets/td_button.dart';
import 'features/home/presentation/product_home_shell.dart';
import 'features/mvp/data/mvp_api_repository.dart';

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

// ─────────────── Login / Register ───────────────

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _displayNameCtrl = TextEditingController();
  final _usernameCtrl    = TextEditingController();
  final _emailCtrl       = TextEditingController();
  final _passwordCtrl    = TextEditingController();

  late final MvpApiRepository _api;
  bool _loading = false;
  bool _register = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _api = MvpApiRepository(
      baseUrl: GameModeConfig.current.apiBaseUrl,
      httpClient: http.Client(),
    );
  }

  @override
  void dispose() {
    _displayNameCtrl.dispose();
    _usernameCtrl.dispose();
    _emailCtrl.dispose();
    _passwordCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() { _loading = true; _error = null; });
    try {
      final user = _usernameCtrl.text.trim();
      final pass = _passwordCtrl.text;
      if (user.isEmpty || pass.isEmpty) {
        setState(() => _error = 'Usuario y contraseña obligatorios');
        return;
      }
      final login = _register
          ? await _api.register(
              username: user,
              email: _emailCtrl.text.trim(),
              password: pass,
              displayName: _displayNameCtrl.text.trim(),
            )
          : await _api.login(username: user, password: pass);

      final profile = await _api.profile(login.token);
      if (!mounted) return;

      Navigator.of(context).pushReplacement(
        MaterialPageRoute(
          builder: (_) => ProductHomeShell(
            playerName: profile.name,
            rank: profile.rank,
            mmr: profile.mmr,
            playerId: login.playerId,
            token: login.token,
            apiBaseUrl: GameModeConfig.current.apiBaseUrl,
          ),
        ),
      );
    } on MvpApiException catch (e) {
      if (!mounted) return;
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return Scaffold(
      backgroundColor: td.bg,
      body: SafeArea(
        child: LayoutBuilder(
          builder: (context, constraints) => SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 28),
            child: ConstrainedBox(
              constraints: BoxConstraints(minHeight: constraints.maxHeight),
              child: IntrinsicHeight(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
              // Marca — fija arriba, no se mueve al cambiar formulario
              const SizedBox(height: 40),
              Container(width: 56, height: 1, color: td.gold),
              const SizedBox(height: 18),
              Text(
                'CICLO COMPETITIVO · S2',
                style: TdText.mono(10, letterSpacing: 3.0, color: td.muted),
              ),
              const SizedBox(height: 8),
              RichText(
                text: TextSpan(
                  style: GoogleFonts.bebasNeue(
                    fontSize: 72,
                    height: 0.85,
                    letterSpacing: 0.72,
                    color: td.fg,
                  ),
                  children: [
                    const TextSpan(text: 'TRUE '),
                    TextSpan(text: 'DUEL', style: TextStyle(color: td.gold)),
                  ],
                ),
              ),
              const SizedBox(height: 14),
              Text(
                'DUELOS POR TURNOS · 3 VS 3',
                style: TdText.mono(11, letterSpacing: 0.88, color: td.fg2),
              ),
              const SizedBox(height: 36),

              // Tabs ENTRAR / CREAR CUENTA
              _AuthTabs(
                register: _register,
                onToggle: (v) => setState(() { _register = v; _error = null; }),
              ),
              const SizedBox(height: 22),

              // Campos
              if (_register) ...[
                _TdField(label: 'Nombre visible', controller: _displayNameCtrl),
                const SizedBox(height: 16),
              ],
              _TdField(label: 'Usuario', controller: _usernameCtrl),
              if (_register) ...[
                const SizedBox(height: 16),
                _TdField(
                  label: 'Email',
                  controller: _emailCtrl,
                  keyboardType: TextInputType.emailAddress,
                ),
              ],
              const SizedBox(height: 16),
              _TdField(label: 'Contraseña', controller: _passwordCtrl, obscure: true),
              const SizedBox(height: 22),

              // Error
              if (_error != null) ...[
                _ErrorBanner(message: _error!),
                const SizedBox(height: 12),
              ],

              // Botón principal
              TdButton(
                label: _register ? 'Crear cuenta' : 'Entrar al duelo',
                onPressed: _loading ? null : _submit,
                loading: _loading,
              ),
              const SizedBox(height: 18),

              Center(
                child: Text(
                  '¿OLVIDASTE LA CONTRASEÑA?',
                  style: TdText.mono(10, letterSpacing: 1.6, color: td.muted),
                ),
              ),

              const Spacer(),

              // Pie
              Padding(
                padding: const EdgeInsets.only(bottom: 28),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('v 0.42.1',        style: TdText.mono(9, letterSpacing: 1.62, color: td.muted2)),
                    Text('SERVIDOR · LOCAL', style: TdText.mono(9, letterSpacing: 1.62, color: td.muted2)),
                  ],
                ),
              ),
            ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _AuthTabs extends StatelessWidget {
  const _AuthTabs({required this.register, required this.onToggle});

  final bool register;
  final void Function(bool) onToggle;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return Container(
      decoration: BoxDecoration(border: Border(bottom: BorderSide(color: td.border2))),
      child: Row(
        children: [
          _Tab(label: 'ENTRAR',       active: !register, onTap: () => onToggle(false)),
          _Tab(label: 'CREAR CUENTA', active: register,  onTap: () => onToggle(true)),
        ],
      ),
    );
  }
}

class _Tab extends StatelessWidget {
  const _Tab({required this.label, required this.active, required this.onTap});

  final String label;
  final bool active;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 10),
          decoration: active
              ? BoxDecoration(border: Border(bottom: BorderSide(color: td.gold, width: 2)))
              : null,
          child: Text(
            label,
            textAlign: TextAlign.center,
            style: GoogleFonts.bebasNeue(
              fontSize: 18,
              letterSpacing: 2.88,
              color: active ? td.gold : td.muted,
            ),
          ),
        ),
      ),
    );
  }
}

class _TdField extends StatelessWidget {
  const _TdField({
    required this.label,
    required this.controller,
    this.obscure = false,
    this.keyboardType,
  });

  final String label;
  final TextEditingController controller;
  final bool obscure;
  final TextInputType? keyboardType;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label.toUpperCase(), style: TdText.mono(10, letterSpacing: 1.6, color: td.muted)),
        const SizedBox(height: 4),
        TextField(
          controller: controller,
          obscureText: obscure,
          keyboardType: keyboardType,
          style: TdText.ui(14, color: td.fg),
          decoration: InputDecoration(
            filled: true,
            fillColor: const Color(0xFF101015),
            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.zero,
              borderSide: BorderSide(color: td.border2),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.zero,
              borderSide: BorderSide(color: td.border2),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.zero,
              borderSide: BorderSide(color: td.gold),
            ),
          ),
        ),
      ],
    );
  }
}

class _ErrorBanner extends StatelessWidget {
  const _ErrorBanner({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: td.loss.withAlpha(15),
        border: Border.all(color: td.loss.withAlpha(77)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('ERROR', style: TdText.mono(10, letterSpacing: 1.2, color: td.loss)),
          const SizedBox(width: 10),
          Expanded(
            child: Text(message, style: TdText.ui(12, color: td.fg2, height: 1.4)),
          ),
        ],
      ),
    );
  }
}
