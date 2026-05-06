import 'dart:async';

import 'package:flutter/material.dart';

import '../../../../core/theme/duel_theme.dart';
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

class _MatchmakingScreenState extends State<MatchmakingScreen> with SingleTickerProviderStateMixin {
  late final AnimationController _pulseController;

  @override
  void initState() {
    super.initState();
    _pulseController = AnimationController(vsync: this, duration: const Duration(milliseconds: 1600))..repeat(reverse: true);
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
          selectedMode: widget.controller.activeTicket?.queue ?? widget.controller.selectedMode,
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
    final duel = context.duel;
    return Scaffold(
      body: SafeArea(
        child: AnimatedBuilder(
          animation: widget.controller,
          builder: (context, _) {
            final c = widget.controller;
            final selectedMode = c.modeLocked ? (c.activeTicket?.queue ?? c.selectedMode) : c.selectedMode;
            final canEnqueue = !c.isBusy;

            return Column(
              children: [
                Expanded(
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
                    children: [
                      _TopBar(
                        championName: widget.championName,
                        playerRank: widget.playerRank,
                        playerMmr: widget.playerMmr,
                      ),
                      const SizedBox(height: 18),
                      Text('Selecciona modo', style: Theme.of(context).textTheme.titleMedium),
                      const SizedBox(height: 10),
                      ...QueueController.queueModes.map(
                        (mode) => _ModeCard(
                          data: _modeUiData(mode),
                          selected: selectedMode == mode,
                          locked: c.modeLocked,
                          pulse: _pulseController,
                          onTap: () => c.selectMode(mode),
                        ),
                      ),
                      const SizedBox(height: 12),
                      Text(
                        c.modeLocked ? 'Tienes una búsqueda activa en curso.' : 'Elige modo y pulsa buscar partida.',
                        style: TextStyle(color: duel.textSecondary, fontSize: 12),
                      ),
                      if (c.errorCode != null)
                        Padding(
                          padding: const EdgeInsets.only(top: 8),
                          child: Card(
                            child: Padding(
                              padding: const EdgeInsets.all(12),
                              child: Text('Error de cola: ${c.errorCode}', style: TextStyle(color: duel.danger)),
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
                  decoration: BoxDecoration(
                    color: duel.bgSoft.withOpacity(0.92),
                    border: Border(top: BorderSide(color: duel.border)),
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      SizedBox(
                        width: double.infinity,
                        child: FilledButton(
                          onPressed: canEnqueue ? _handlePrimaryCta : null,
                          style: FilledButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                          ),
                          child: const Text('Buscar partida'),
                        ),
                      ),
                    ],
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

class _TopBar extends StatelessWidget {
  const _TopBar({
    required this.championName,
    required this.playerRank,
    required this.playerMmr,
  });

  final String championName;
  final String? playerRank;
  final int? playerMmr;

  @override
  Widget build(BuildContext context) {
    final duel = context.duel;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            IconButton(
              onPressed: () => Navigator.of(context).maybePop(),
              icon: const Icon(Icons.arrow_back),
            ),
            const SizedBox(width: 6),
            Text('Matchmaking', style: Theme.of(context).textTheme.titleLarge),
          ],
        ),
        const SizedBox(height: 8),
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: duel.surface,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: duel.border),
          ),
          child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: duel.surfaceAlt,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(Icons.shield_outlined, color: duel.neonGreen),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Campeón seleccionado', style: TextStyle(color: duel.textSecondary, fontSize: 12)),
                    const SizedBox(height: 2),
                    Text(championName, style: const TextStyle(fontWeight: FontWeight.w700)),
                  ],
                ),
              ),
              if (playerRank != null || playerMmr != null)
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(playerRank ?? '-', style: TextStyle(color: duel.neonGreen, fontWeight: FontWeight.w700)),
                    Text('${playerMmr ?? '-'} MMR', style: TextStyle(color: duel.textSecondary, fontSize: 12)),
                  ],
                ),
            ],
          ),
        ),
      ],
    );
  }
}

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
    final duel = context.duel;
    final canTap = !locked;

    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: AnimatedScale(
        duration: const Duration(milliseconds: 180),
        scale: selected ? 1.01 : 1.0,
        child: AnimatedBuilder(
          animation: pulse,
          builder: (context, _) {
            final glow = selected ? (2 + (pulse.value * 8)) : 0.0;
            return InkWell(
              borderRadius: BorderRadius.circular(16),
              onTap: canTap ? onTap : null,
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 220),
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(16),
                  color: selected ? duel.surface.withOpacity(0.98) : duel.surface,
                  border: Border.all(
                    color: selected ? duel.neonGreen : duel.border,
                    width: selected ? 1.6 : 1.0,
                  ),
                  boxShadow: [
                    if (selected)
                      BoxShadow(
                        color: duel.neonGreen.withOpacity(0.18),
                        blurRadius: glow,
                        spreadRadius: 0.5,
                      ),
                  ],
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      width: 44,
                      height: 44,
                      decoration: BoxDecoration(
                        color: duel.surfaceAlt,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: duel.border),
                      ),
                      child: Icon(data.icon, color: selected ? duel.neonGreen : duel.textSecondary),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            data.name,
                            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            data.mode,
                            style: TextStyle(color: duel.textSecondary, fontSize: 12),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            );
          },
        ),
      ),
    );
  }
}

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
  State<_QueueSearchingScreen> createState() => _QueueSearchingScreenState();
}

class _QueueSearchingScreenState extends State<_QueueSearchingScreen> with SingleTickerProviderStateMixin {
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
    _minSearchSeconds = widget.selectedMode.contains('bot') ? 4 : 0;
    _pulseController = AnimationController(vsync: this, duration: const Duration(milliseconds: 1300))..repeat(reverse: true);
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
          setState(() {
            _matchFound = false;
          });
          return;
        }
        _navigatingToMatch = true;
        setState(() {});
        final elapsed = DateTime.now().difference(_startedAt).inSeconds;
        final waitSeconds = _minSearchSeconds > elapsed ? (_minSearchSeconds - elapsed) : 0;
        Future<void>.delayed(Duration(milliseconds: 250 + (waitSeconds * 1000)), () {
          if (!mounted) return;
          Navigator.of(context).pop();
          widget.onOpenMatch(matchId);
        });
      });
      return;
    }
    if (_matchFound && mounted) {
      setState(() {
        _matchFound = false;
      });
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
    final duel = context.duel;
    final elapsed = DateTime.now().difference(_startedAt).inSeconds;
    final eta = widget.controller.activeTicket?.etaSec ?? 0;

    return Scaffold(
      appBar: AppBar(title: const Text('Buscando partida')),
      body: AnimatedBuilder(
        animation: _pulseController,
        builder: (context, _) {
          final pulse = _pulseController.value;
          return Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                const Spacer(),
                Container(
                  width: 98,
                  height: 98,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: duel.surfaceAlt,
                    boxShadow: [
                      BoxShadow(
                        color: duel.neonGreen.withOpacity(0.18 + (pulse * 0.22)),
                        blurRadius: 10 + (pulse * 24),
                        spreadRadius: 1,
                      ),
                    ],
                  ),
                  child: Icon(Icons.manage_search_rounded, color: duel.neonGreen, size: 42),
                ),
                const SizedBox(height: 22),
                Text(
                  _matchFound ? 'Partida encontrada' : 'Buscando rival...',
                  style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 8),
                Text(
                  'Modo: ${_modeUiData(widget.selectedMode).name}',
                  style: TextStyle(color: duel.textSecondary),
                ),
                const SizedBox(height: 18),
                Text(_formatDuration(elapsed), style: TextStyle(fontSize: 28, fontWeight: FontWeight.w800, color: duel.accentOrange)),
                const SizedBox(height: 10),
                ClipRRect(
                  borderRadius: BorderRadius.circular(10),
                  child: LinearProgressIndicator(
                    minHeight: 7,
                    value: (elapsed % 8) / 8,
                    backgroundColor: duel.surfaceAlt,
                    valueColor: AlwaysStoppedAnimation<Color>(duel.neonGreen),
                  ),
                ),
                const SizedBox(height: 10),
                Text(
                  eta > 0 ? 'Tiempo estimado: ${eta}s' : 'Tiempo estimado no disponible',
                  style: TextStyle(color: duel.textSecondary, fontSize: 12),
                ),
                const Spacer(),
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton(
                    onPressed: widget.controller.isBusy || _matchFound || _navigatingToMatch ? null : _cancelQueue,
                    child: const Text('Cancelar búsqueda'),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _ModeUiData {
  _ModeUiData({
    required this.mode,
    required this.name,
    required this.icon,
  });

  final String mode;
  final String name;
  final IconData icon;
}

_ModeUiData _modeUiData(String mode) {
  switch (mode) {
    case 'ranked_pvp':
      return _ModeUiData(
        mode: 'ranked_pvp',
        name: 'Ranked',
        icon: Icons.leaderboard_outlined,
      );
    case 'ranked_bot':
      return _ModeUiData(
        mode: 'ranked_bot',
        name: 'Ranked vs Bot',
        icon: Icons.smart_toy_outlined,
      );
    case 'normal_bot':
      return _ModeUiData(
        mode: 'normal_bot',
        name: 'Casual vs Bot',
        icon: Icons.sports_esports_outlined,
      );
    default:
      return _ModeUiData(
        mode: mode,
        name: _humanizeMode(mode),
        icon: Icons.bolt_outlined,
      );
  }
}

String _humanizeMode(String mode) {
  return mode
      .split('_')
      .where((part) => part.isNotEmpty)
      .map((part) => '${part.substring(0, 1).toUpperCase()}${part.substring(1)}')
      .join(' ');
}

String _formatDuration(int totalSeconds) {
  final minutes = totalSeconds ~/ 60;
  final seconds = totalSeconds % 60;
  return '${minutes.toString().padLeft(2, '0')}:${seconds.toString().padLeft(2, '0')}';
}
