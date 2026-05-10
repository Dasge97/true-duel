import 'dart:async';

import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../../core/theme/duel_theme.dart';
import '../../../../core/widgets/td_button.dart';
import '../../../../core/widgets/td_champion_glyph.dart';
import '../controllers/queue_controller.dart';

class MatchmakingScreen extends StatefulWidget {
  const MatchmakingScreen({
    super.key,
    required this.controller,
    required this.championId,
    required this.championName,
    required this.onOpenMatch,
    this.playerRank,
    this.playerMmr,
  });

  final QueueController controller;
  final String championId;
  final String championName;
  final ValueChanged<String> onOpenMatch;
  final String? playerRank;
  final int? playerMmr;

  @override
  State<MatchmakingScreen> createState() => _MatchmakingScreenState();
}

class _MatchmakingScreenState extends State<MatchmakingScreen>
    with SingleTickerProviderStateMixin {
  late final AnimationController _pulseController;

  @override
  void initState() {
    super.initState();
    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1600),
    )..repeat(reverse: true);
    widget.controller.loadActiveTicket();
  }

  @override
  void dispose() {
    _pulseController.dispose();
    super.dispose();
  }

  Future<void> _openSearchingScreen() async {
    await Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => _QueueSearchingScreen(
          controller: widget.controller,
          selectedMode:
              widget.controller.activeTicket?.queue ??
              widget.controller.selectedMode,
          onOpenMatch: widget.onOpenMatch,
        ),
      ),
    );
  }

  Future<void> _handlePrimaryCta() async {
    final c = widget.controller;
    if (c.isBusy) return;

    if (c.modeLocked) {
      await _openSearchingScreen();
      return;
    }

    await c.enqueue(championId: widget.championId);
    if (!mounted) return;
    if (c.errorCode == null && c.activeTicket != null) {
      await _openSearchingScreen();
    }
  }

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return Scaffold(
      backgroundColor: td.bg,
      body: SafeArea(
        child: AnimatedBuilder(
          animation: widget.controller,
          builder: (context, _) {
            final c = widget.controller;
            final selectedMode = c.modeLocked
                ? (c.activeTicket?.queue ?? c.selectedMode)
                : c.selectedMode;
            final canEnqueue = !c.isBusy;

            return Column(
              children: [
                // ── Back bar ─────────────────────────────────────────
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 12,
                  ),
                  decoration: BoxDecoration(
                    border: Border(
                      bottom: BorderSide(color: td.border),
                    ),
                  ),
                  child: Row(
                    children: [
                      GestureDetector(
                        onTap: () => Navigator.of(context).maybePop(),
                        child: Container(
                          width: 32,
                          height: 32,
                          decoration: BoxDecoration(
                            border: Border.all(color: td.border2),
                          ),
                          child: Icon(
                            Icons.arrow_back,
                            size: 14,
                            color: td.fg,
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Text(
                        'MATCHMAKING',
                        style: TdText.display(22, letterSpacing: 1.32),
                      ),
                      const Spacer(),
                    ],
                  ),
                ),

                // ── Scrollable content ────────────────────────────────
                Expanded(
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(14, 14, 14, 0),
                    children: [
                      // Captain card
                      _CaptainCard(
                        championName: widget.championName,
                        playerRank: widget.playerRank,
                        playerMmr: widget.playerMmr,
                      ),
                      const SizedBox(height: 14),

                      // Mode selector header
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'MODO',
                                style: TdText.display(22),
                              ),
                              Text(
                                'Selecciona modo',
                                style: TdText.ui(12, color: td.fg2),
                              ),
                            ],
                          ),
                          Text(
                            '${QueueController.queueModes.length} DISPONIBLES',
                            style: TdText.mono(
                              11,
                              color: td.muted,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),

                      // Mode cards
                      ...QueueController.queueModes.map(
                        (mode) => Padding(
                          padding: const EdgeInsets.only(bottom: 6),
                          child: _ModeCard(
                            data: _modeUiData(mode),
                            selected: selectedMode == mode,
                            locked: c.modeLocked,
                            pulse: _pulseController,
                            onTap: () => c.selectMode(mode),
                          ),
                        ),
                      ),

                      const SizedBox(height: 14),
                      Text(
                        c.modeLocked
                            ? 'Tienes una búsqueda activa en curso.'
                            : 'Elige modo y pulsa buscar partida.',
                        style: TdText.mono(11, color: td.fg2),
                        textAlign: TextAlign.center,
                      ),

                      if (c.errorCode != null) ...[
                        const SizedBox(height: 10),
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            border: Border.all(color: td.loss.withAlpha(80)),
                            color: td.loss.withAlpha(20),
                          ),
                          child: Text(
                            'Error de cola: ${c.errorCode}',
                            style: TdText.mono(11, color: td.loss),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),

                // ── CTA pegado al fondo ───────────────────────────────
                Padding(
                  padding: const EdgeInsets.fromLTRB(14, 10, 14, 14),
                  child: FilledButton(
                    onPressed: canEnqueue ? _handlePrimaryCta : null,
                    style: FilledButton.styleFrom(
                      backgroundColor: td.gold,
                      foregroundColor: const Color(0xFF16110A),
                      disabledBackgroundColor: td.gold.withAlpha(60),
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: const RoundedRectangleBorder(),
                    ),
                    child: Text(
                      'BUSCAR PARTIDA',
                      style: TdText.display(22, letterSpacing: 1.44),
                    ),
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}

// ── Captain card ────────────────────────────────────────────────────────────

class _CaptainCard extends StatelessWidget {
  const _CaptainCard({
    required this.championName,
    required this.playerRank,
    required this.playerMmr,
  });

  final String championName;
  final String? playerRank;
  final int? playerMmr;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final initial =
        championName.isNotEmpty ? championName[0].toUpperCase() : '?';

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          transform: GradientRotation(135 * 3.14159 / 180),
          colors: [Color(0xFF1B1A14), Color(0xFF14141A)],
        ),
        border: Border.all(color: td.gold.withAlpha(77)),
      ),
      child: Row(
        children: [
          TdChampionGlyph(initial: initial, size: 72, accent: td.gold),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'CAPITÁN',
                  style: TdText.eyebrow(color: td.gold),
                ),
                const SizedBox(height: 2),
                // Keep original casing so widget tests can locate by name
                Text(
                  championName,
                  style: TdText.display(26),
                ),
                const SizedBox(height: 6),
                Row(
                  children: [
                    if (playerRank != null) ...[
                      Text(
                        playerRank!,
                        style: TdText.mono(11, color: td.fg2),
                      ),
                      Text(
                        ' · ',
                        style: TdText.mono(11, color: td.muted),
                      ),
                    ],
                    if (playerMmr != null)
                      Text(
                        '${playerMmr} MMR',
                        style: TdText.mono(11, color: td.fg2),
                      ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ── Mode card ───────────────────────────────────────────────────────────────

class _ModeCard extends StatelessWidget {
  const _ModeCard({
    required this.data,
    required this.selected,
    required this.locked,
    required this.pulse,
    required this.onTap,
  });

  final _ModeUiData data;
  final bool selected;
  final bool locked;
  final Animation<double> pulse;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final canTap = !locked;

    return GestureDetector(
      onTap: canTap ? onTap : null,
      child: AnimatedBuilder(
        animation: pulse,
        builder: (context, _) {
          return Stack(
            children: [
              AnimatedContainer(
                duration: const Duration(milliseconds: 220),
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  gradient: selected
                      ? LinearGradient(
                          begin: Alignment.centerLeft,
                          end: Alignment.centerRight,
                          colors: [
                            td.goldGlow,
                            Colors.transparent,
                          ],
                        )
                      : null,
                  color: selected ? null : td.card,
                  border: Border.all(
                    color: selected ? td.gold : td.border,
                    width: selected ? 1.5 : 1,
                  ),
                ),
                child: Row(
                  children: [
                    const SizedBox(width: 3), // space for left accent bar
                    Container(
                      width: 40,
                      height: 40,
                      decoration: BoxDecoration(
                        color: selected ? td.goldGlow : Colors.transparent,
                        border: Border.all(
                          color: selected ? td.gold : td.border2,
                        ),
                      ),
                      child: Icon(
                        data.icon,
                        color: selected ? td.gold : td.fg2,
                        size: 20,
                      ),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            data.name.toUpperCase(),
                            style: TdText.display(18),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            data.desc,
                            style: TdText.ui(12, color: td.fg2),
                          ),
                        ],
                      ),
                    ),
                    // Radio button
                    Container(
                      width: 18,
                      height: 18,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        border: Border.all(
                          color:
                              selected ? td.gold : td.borderStrong,
                          width: 1.5,
                        ),
                      ),
                      child: selected
                          ? Center(
                              child: Container(
                                width: 8,
                                height: 8,
                                decoration: BoxDecoration(
                                  shape: BoxShape.circle,
                                  color: td.gold,
                                ),
                              ),
                            )
                          : null,
                    ),
                  ],
                ),
              ),
              // Left accent bar when selected
              if (selected)
                Positioned(
                  left: 0,
                  top: 0,
                  bottom: 0,
                  child: Container(
                    width: 3,
                    color: td.gold,
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}

// ── Searching screen ────────────────────────────────────────────────────────

class _QueueSearchingScreen extends StatefulWidget {
  const _QueueSearchingScreen({
    required this.controller,
    required this.selectedMode,
    required this.onOpenMatch,
  });

  final QueueController controller;
  final String selectedMode;
  final ValueChanged<String> onOpenMatch;

  @override
  State<_QueueSearchingScreen> createState() =>
      _QueueSearchingScreenState();
}

class _QueueSearchingScreenState extends State<_QueueSearchingScreen>
    with SingleTickerProviderStateMixin {
  late final AnimationController _pulseController;
  Timer? _pollTimer;
  late final DateTime _startedAt;
  late final int _minSearchSeconds;
  bool _navigatingToMatch = false;
  bool _matchFound = false;
  bool _resolvingMatch = false;

  @override
  void initState() {
    super.initState();
    _startedAt = DateTime.now();
    _minSearchSeconds =
        widget.selectedMode.contains('bot') ? 4 : 0;
    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1300),
    )..repeat(reverse: true);
    widget.controller.addListener(_onControllerChanged);
    _onControllerChanged();
    _pollTimer = Timer.periodic(const Duration(seconds: 1), (_) async {
      if (!mounted) return;
      await widget.controller.pollOnce();
      setState(() {});
    });
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    widget.controller.removeListener(_onControllerChanged);
    _pulseController.dispose();
    super.dispose();
  }

  void _onControllerChanged() {
    if (!mounted || _navigatingToMatch) return;
    if (widget.controller.canOpenMatch && !_resolvingMatch) {
      _matchFound = true;
      _resolvingMatch = true;
      final futureMatchId = widget.controller.resolvePlayableMatchId();
      futureMatchId.then((matchId) {
        _resolvingMatch = false;
        if (!mounted || _navigatingToMatch) return;
        if (matchId == null || matchId.isEmpty) {
          setState(() => _matchFound = false);
          return;
        }
        _navigatingToMatch = true;
        setState(() {});
        final elapsed =
            DateTime.now().difference(_startedAt).inSeconds;
        final waitSeconds = _minSearchSeconds > elapsed
            ? (_minSearchSeconds - elapsed)
            : 0;
        Future<void>.delayed(
          Duration(milliseconds: 250 + (waitSeconds * 1000)),
          () {
            if (!mounted) return;
            _pollTimer?.cancel();
            _pollTimer = null;
            widget.onOpenMatch(matchId);
          },
        );
      });
      return;
    }
    if (_matchFound && mounted) {
      setState(() => _matchFound = false);
    }
  }

  Future<void> _cancelQueue() async {
    if (_matchFound || _navigatingToMatch) return;
    await widget.controller.cancel();
    if (!mounted) return;
    Navigator.of(context).pop();
  }

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final elapsed =
        DateTime.now().difference(_startedAt).inSeconds;

    return Scaffold(
      backgroundColor: td.bg,
      body: SafeArea(
        child: AnimatedBuilder(
          animation: _pulseController,
          builder: (context, _) {
            return Column(
              children: [
                // Back bar
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 12,
                  ),
                  decoration: BoxDecoration(
                    border: Border(
                      bottom: BorderSide(color: td.border),
                    ),
                  ),
                  child: Row(
                    children: [
                      GestureDetector(
                        onTap: _cancelQueue,
                        child: Container(
                          width: 32,
                          height: 32,
                          decoration: BoxDecoration(
                            border: Border.all(color: td.border2),
                          ),
                          child: Icon(
                            Icons.arrow_back,
                            size: 14,
                            color: td.fg,
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Text(
                        'BUSCANDO RIVAL',
                        style: TdText.display(22, letterSpacing: 1.32),
                      ),
                      const Spacer(),
                    ],
                  ),
                ),

                const Spacer(),

                // Pulsing glyph area
                Container(
                  width: 98,
                  height: 98,
                  decoration: BoxDecoration(
                    color: td.card,
                    border: Border.all(
                      color: td.gold.withAlpha(
                        (46 + (_pulseController.value * 80)).round(),
                      ),
                    ),
                  ),
                  child: Icon(
                    Icons.manage_search_rounded,
                    color: td.gold,
                    size: 42,
                  ),
                ),
                const SizedBox(height: 22),
                Text(
                  _matchFound ? 'PARTIDA ENCONTRADA' : 'BUSCANDO RIVAL',
                  style: TdText.display(38),
                ),
                const SizedBox(height: 8),
                // Loading dots
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: List.generate(3, (i) {
                    final active = (elapsed % 3) == i;
                    return AnimatedContainer(
                      duration: const Duration(milliseconds: 300),
                      margin: const EdgeInsets.symmetric(horizontal: 3),
                      width: active ? 10 : 6,
                      height: active ? 10 : 6,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: active ? td.gold : td.muted2,
                      ),
                    );
                  }),
                ),
                const SizedBox(height: 18),
                Text(
                  _formatDuration(elapsed),
                  style: TdText.mono(28, color: td.gold),
                ),
                const SizedBox(height: 8),
                Text(
                  'Modo: ${_modeUiData(widget.selectedMode).name}',
                  style: TdText.mono(11, color: td.fg2),
                ),

                const Spacer(),

                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                  child: TdButton(
                    label: 'CANCELAR BÚSQUEDA',
                    onPressed:
                        widget.controller.isBusy ||
                            _matchFound ||
                            _navigatingToMatch
                        ? null
                        : _cancelQueue,
                    variant: TdButtonVariant.ghost,
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}

// ── Mode UI data ─────────────────────────────────────────────────────────────

class _ModeUiData {
  _ModeUiData({
    required this.mode,
    required this.name,
    required this.desc,
    required this.icon,
  });

  final String mode;
  final String name;
  final String desc;
  final IconData icon;
}

_ModeUiData _modeUiData(String mode) {
  switch (mode) {
    case 'ranked_pvp':
      return _ModeUiData(
        mode: 'ranked_pvp',
        name: 'Ranked',
        desc: 'Compite contra otros jugadores por MMR.',
        icon: Icons.emoji_events_outlined,
      );
    case 'ranked_bot':
      return _ModeUiData(
        mode: 'ranked_bot',
        name: 'Ranked vs Bot',
        desc: 'Entrena contra IA avanzada con MMR en juego.',
        icon: Icons.smart_toy_outlined,
      );
    case 'normal_bot':
      return _ModeUiData(
        mode: 'normal_bot',
        name: 'Casual vs Bot',
        desc: 'Practica sin consecuencias ranked.',
        icon: Icons.sports_esports_outlined,
      );
    default:
      return _ModeUiData(
        mode: mode,
        name: _humanizeMode(mode),
        desc: '',
        icon: Icons.bolt_outlined,
      );
  }
}

String _humanizeMode(String mode) {
  return mode
      .split('_')
      .where((p) => p.isNotEmpty)
      .map((p) => '${p[0].toUpperCase()}${p.substring(1)}')
      .join(' ');
}

String _formatDuration(int totalSeconds) {
  final minutes = totalSeconds ~/ 60;
  final seconds = totalSeconds % 60;
  return '${minutes.toString().padLeft(2, '0')}:${seconds.toString().padLeft(2, '0')}';
}
