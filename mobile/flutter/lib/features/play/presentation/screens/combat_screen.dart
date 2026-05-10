import 'package:flutter/material.dart';

import '../../../../core/theme/duel_theme.dart';
import '../../../../core/widgets/td_champion_glyph.dart';
import '../controllers/combat_controller.dart';
import '../controllers/result_controller.dart';
import 'result_screen.dart';

// ── Phase state machine ───────────────────────────────────────────────────────

enum _CombatPhase { pickAction, pickTarget, review }

// ── Screen ────────────────────────────────────────────────────────────────────

class CombatScreen extends StatefulWidget {
  const CombatScreen({
    super.key,
    required this.controller,
    required this.onContinue,
    required this.onPlayAgain,
  });

  final CombatController controller;
  final VoidCallback onContinue;
  final VoidCallback onPlayAgain;

  @override
  State<CombatScreen> createState() => _CombatScreenState();
}

class _CombatScreenState extends State<CombatScreen> {
  _CombatPhase _phase = _CombatPhase.pickAction;
  String? _pendingActionType;
  bool _showLog = false;
  List<_FloatEntry> _activeFloats = const [];

  @override
  void initState() {
    super.initState();
    widget.controller.load();
  }

  // ── Phase transitions ────────────────────────────────────────────────────

  void _onActionChosen(String action) {
    final c = widget.controller;
    if (action == 'defend') {
      c.assignAction('defend');
      setState(() {
        _phase = c.isReadyToSubmit ? _CombatPhase.review : _CombatPhase.pickAction;
      });
    } else {
      setState(() {
        _phase = _CombatPhase.pickTarget;
        _pendingActionType = action;
      });
    }
  }

  void _onTargetChosen(int enemySlot) {
    final c = widget.controller;
    c.assignActionWithTarget(_pendingActionType!, enemySlot);
    setState(() {
      _pendingActionType = null;
      _phase = c.isReadyToSubmit ? _CombatPhase.review : _CombatPhase.pickAction;
    });
  }

  void _cancelTargeting() {
    setState(() {
      _phase = _CombatPhase.pickAction;
      _pendingActionType = null;
    });
  }

  void _enterEditMode() {
    setState(() => _phase = _CombatPhase.pickAction);
  }

  // ── Submit ───────────────────────────────────────────────────────────────

  Future<void> _submit() async {
    final settlement = await widget.controller.submitTurn();
    if (!mounted) return;

    _triggerFloatingLabels(widget.controller.recentEvents);

    if (settlement == null) {
      setState(() => _phase = _CombatPhase.pickAction);
      return;
    }

    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ResultScreen(
          controller: ResultController.fromSettlement(settlement),
          onContinue: widget.onContinue,
          onPlayAgain: widget.onPlayAgain,
        ),
      ),
    );
  }

  void _triggerFloatingLabels(List<Map<String, dynamic>> events) {
    if (events.isEmpty) return;
    final last = events.last;

    // Acumular daño por slot para evitar solapamiento cuando múltiples
    // personajes atacan el mismo objetivo.
    final enemyDmg = <int, int>{};
    final playerDmg = <int, int>{};
    final playerDef = <int, bool>{};
    final enemyDef = <int, bool>{};

    for (final ev in (last['playerEvents'] as List<dynamic>? ?? const []).whereType<Map<String, dynamic>>()) {
      final action = ev['action'] as String? ?? '';
      final dmg = ev['damage'] as int? ?? 0;
      final target = ev['targetSlot'] as int? ?? 0;
      final actor = ev['actorSlot'] as int? ?? 0;
      if (action == 'defend') {
        playerDef[actor] = true;
      } else if (dmg > 0) {
        enemyDmg[target] = (enemyDmg[target] ?? 0) + dmg;
      }
    }

    for (final ev in (last['enemyEvents'] as List<dynamic>? ?? const []).whereType<Map<String, dynamic>>()) {
      final action = ev['action'] as String? ?? '';
      final dmg = ev['damage'] as int? ?? 0;
      final target = ev['targetSlot'] as int? ?? 0;
      final actor = ev['actorSlot'] as int? ?? 0;
      if (action == 'defend') {
        enemyDef[actor] = true;
      } else if (dmg > 0) {
        playerDmg[target] = (playerDmg[target] ?? 0) + dmg;
      }
    }

    final floats = <_FloatEntry>[
      for (final e in enemyDmg.entries)
        _FloatEntry(side: 'enemy', slot: e.key, text: '-${e.value}', color: const Color(0xFFC76E6E)),
      for (final e in playerDmg.entries)
        _FloatEntry(side: 'player', slot: e.key, text: '-${e.value}', color: const Color(0xFFE06060)),
      for (final slot in playerDef.keys)
        _FloatEntry(side: 'player', slot: slot, text: 'DEF', color: const Color(0xFF6E9DC7)),
      for (final slot in enemyDef.keys)
        _FloatEntry(side: 'enemy', slot: slot, text: 'DEF', color: const Color(0xFF6E9DC7)),
    ];

    if (floats.isEmpty) return;
    setState(() => _activeFloats = floats);
    Future.delayed(const Duration(milliseconds: 1500), () {
      if (mounted) setState(() => _activeFloats = const []);
    });
  }

  // ── Build ────────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0A0A0B),
      body: AnimatedBuilder(
        animation: widget.controller,
        builder: (context, _) {
          final c = widget.controller;
          final td = context.td;

          if (c.isLoading) {
            return Center(child: CircularProgressIndicator(color: td.gold));
          }

          // Estado vacío: el backend devolvió la partida sin campeones
          if (c.playerChampions.isEmpty && c.enemyChampions.isEmpty) {
            return _EmptyMatchState(
              errorCode: c.errorCode,
              onBack: () => Navigator.of(context).maybePop(),
              onRetry: () => c.load(),
            );
          }

          final isMyTurn = !c.inputLocked && c.winner == null;
          final inTargeting = _phase == _CombatPhase.pickTarget;

          return SafeArea(
            child: Stack(
              children: [
                // ── Main layout ──────────────────────────────────────────
                Column(
                  children: [
                    _CombatHeader(
                      turn: c.currentTurn,
                      errorCode: c.errorCode,
                      showLog: _showLog,
                      onToggleLog: () => setState(() => _showLog = !_showLog),
                    ),

                    if (c.winner == null)
                      _TurnOwnerBanner(isMyTurn: isMyTurn),

                    const SizedBox(height: 8),

                    // ── Enemy section — pinned top ───────────────────────
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 12),
                      child: LayoutBuilder(builder: (_, cst) {
                        final n = c.enemyChampions.length;
                        final cardW = n > 0 ? (cst.maxWidth - 4.0 * (n - 1)) / n : 0.0;
                        return Stack(
                          clipBehavior: Clip.none,
                          children: [
                            Row(
                              children: List.generate(n, (i) {
                                final champ = c.enemyChampions[i];
                                final activeAction = c.activeSlot != null
                                    ? c.pendingActions[c.activeSlot]
                                    : null;
                                final isTargeted = activeAction?.targetSlot == i;
                                final isTargetable = inTargeting && champ.isAlive;
                                return Expanded(
                                  child: Padding(
                                    padding: EdgeInsets.only(
                                      left: i == 0 ? 0 : 4,
                                      right: i == n - 1 ? 0 : 4,
                                    ),
                                    child: _ChampionCard(
                                      champ: champ,
                                      isActive: false,
                                      isTargeted: isTargeted,
                                      isTargetable: isTargetable,
                                      side: 'enemy',
                                      onTap: isTargetable ? () => _onTargetChosen(i) : null,
                                      charges: null,
                                    ),
                                  ),
                                );
                              }),
                            ),
                            for (final f in _activeFloats.where((f) => f.side == 'enemy' && f.slot < n))
                              Positioned(
                                top: 6,
                                left: (cardW + 4) * f.slot + cardW / 2 - 30,
                                width: 60,
                                child: _FloatingLabel(key: ValueKey(f.id), text: f.text, color: f.color),
                              ),
                          ],
                        );
                      }),
                    ),

                    // ── Zona rival (futuro: info del rival) ────────────
                    const Spacer(),

                    // ── Timer divider — centrado entre secciones ────────
                    _TurnTimerDivider(
                      turn: c.currentTurn,
                      active: isMyTurn,
                    ),

                    // ── Zona jugador: targeting / review en 1 línea ─────
                    Expanded(
                      child: Align(
                        alignment: Alignment.center,
                        child: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 12),
                          child: _buildPlayerInfo(c),
                        ),
                      ),
                    ),

                    // ── Player cards — siempre fijas aquí ───────────────
                    AnimatedOpacity(
                      opacity: inTargeting ? 0.35 : 1.0,
                      duration: const Duration(milliseconds: 200),
                      child: IgnorePointer(
                        ignoring: inTargeting,
                        child: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 12),
                          child: LayoutBuilder(builder: (_, cst) {
                            final n = c.playerChampions.length;
                            final cardW = n > 0 ? (cst.maxWidth - 4.0 * (n - 1)) / n : 0.0;
                            return Stack(
                              clipBehavior: Clip.none,
                              children: [
                                Row(
                                  children: List.generate(n, (i) {
                                    final champ = c.playerChampions[i];
                                    final isActive = c.activeSlot == i;
                                    return Expanded(
                                      child: Padding(
                                        padding: EdgeInsets.only(
                                          left: i == 0 ? 0 : 4,
                                          right: i == n - 1 ? 0 : 4,
                                        ),
                                        child: _ChampionCard(
                                          champ: champ,
                                          isActive: isActive,
                                          isTargeted: false,
                                          isTargetable: false,
                                          side: 'player',
                                          onTap: champ.isAlive && !c.inputLocked
                                              ? () {
                                                  c.selectSlot(i);
                                                  if (_phase == _CombatPhase.review) {
                                                    setState(() => _phase = _CombatPhase.pickAction);
                                                  }
                                                }
                                              : null,
                                          charges: champ.charges,
                                        ),
                                      ),
                                    );
                                  }),
                                ),
                                for (final f in _activeFloats.where((f) => f.side == 'player' && f.slot < n))
                                  Positioned(
                                    top: 6,
                                    left: (cardW + 4) * f.slot + cardW / 2 - 30,
                                    width: 60,
                                    child: _FloatingLabel(key: ValueKey(f.id), text: f.text, color: f.color),
                                  ),
                              ],
                            );
                          }),
                        ),
                      ),
                    ),

                    const SizedBox(height: 10),

                    // ── Action picker — siempre visible, bloqueado fuera de pickAction
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 12),
                      child: _ActionPicker(
                        controller: c,
                        onAction: _onActionChosen,
                        blocked: _phase != _CombatPhase.pickAction,
                      ),
                    ),

                    // ── Bottom bar ───────────────────────────────────────
                    Padding(
                      padding: const EdgeInsets.fromLTRB(12, 8, 12, 8),
                      child: Row(
                        children: [
                          GestureDetector(
                            onTap: () => Navigator.of(context).maybePop(),
                            child: Container(
                              width: 44,
                              height: 44,
                              decoration: BoxDecoration(
                                color: td.card,
                                border: Border.all(color: td.border2),
                              ),
                              child: Icon(Icons.arrow_back, size: 16, color: td.fg2),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: SizedBox(
                              height: 44,
                              child: ElevatedButton(
                                onPressed: c.isReadyToSubmit ? _submit : null,
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: td.gold,
                                  foregroundColor: const Color(0xFF16110A),
                                  disabledBackgroundColor: td.gold.withAlpha(60),
                                  shape: const RoundedRectangleBorder(),
                                  padding: EdgeInsets.zero,
                                ),
                                child: Text(
                                  'FINALIZAR TURNO',
                                  style: TdText.display(
                                    16,
                                    letterSpacing: 1.44,
                                    color: const Color(0xFF16110A),
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),

                // ── Sliding log panel ───────────────────────────────────
                AnimatedPositioned(
                  duration: const Duration(milliseconds: 240),
                  curve: Curves.easeOutCubic,
                  right: _showLog ? 10 : -220,
                  top: 180,
                  child: SizedBox(
                    width: 230,
                    child: _CombatLogCard(events: c.recentEvents),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildPlayerInfo(CombatController c) {
    return switch (_phase) {
      _CombatPhase.pickTarget => _TargetingPrompt(
          actionType: _pendingActionType ?? 'attack',
          onCancel: _cancelTargeting,
        ),
      _CombatPhase.review => _ReviewPanel(
          controller: c,
          onTapEdit: _enterEditMode,
        ),
      _CombatPhase.pickAction => _ActionAssignmentRow(
          controller: c,
          onClear: (i) {
            c.clearSlotAction(i);
            setState(() {});
          },
        ),
    };
  }
}

// ── Turn owner banner ─────────────────────────────────────────────────────────

class _TurnOwnerBanner extends StatelessWidget {
  const _TurnOwnerBanner({required this.isMyTurn});
  final bool isMyTurn;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      color: isMyTurn ? td.gold.withAlpha(18) : Colors.transparent,
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 6,
            height: 6,
            decoration: BoxDecoration(
              color: isMyTurn ? td.gold : td.muted,
              shape: BoxShape.circle,
            ),
          ),
          const SizedBox(width: 8),
          Text(
            isMyTurn ? 'TU TURNO' : 'PROCESANDO...',
            style: TdText.display(13, color: isMyTurn ? td.gold : td.muted),
          ),
        ],
      ),
    );
  }
}

// ── Turn timer divider ────────────────────────────────────────────────────────

class _TurnTimerDivider extends StatefulWidget {
  const _TurnTimerDivider({required this.turn, required this.active});
  final int turn;
  final bool active;

  @override
  State<_TurnTimerDivider> createState() => _TurnTimerDividerState();
}

class _TurnTimerDividerState extends State<_TurnTimerDivider>
    with SingleTickerProviderStateMixin {
  late AnimationController _timer;
  static const _kDuration = Duration(seconds: 30);

  @override
  void initState() {
    super.initState();
    _timer = AnimationController(vsync: this, duration: _kDuration);
    if (widget.active) _timer.forward();
  }

  @override
  void didUpdateWidget(_TurnTimerDivider old) {
    super.didUpdateWidget(old);
    if (widget.turn != old.turn) {
      _timer.reset();
      if (widget.active) _timer.forward();
    } else if (widget.active && !old.active) {
      _timer.forward(from: _timer.value);
    } else if (!widget.active && old.active) {
      _timer.stop();
    }
  }

  @override
  void dispose() {
    _timer.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return AnimatedBuilder(
      animation: _timer,
      builder: (_, __) {
        final remaining = (1.0 - _timer.value).clamp(0.0, 1.0);
        final barColor = Color.lerp(td.gold, td.loss, _timer.value * _timer.value)!;
        final secs = (_kDuration.inSeconds * remaining).ceil();

        return Padding(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          child: Row(
            children: [
              Text('VS', style: TdText.display(13, color: const Color(0x33ECE7DA))),
              const SizedBox(width: 10),
              Expanded(
                child: SizedBox(
                  height: 3,
                  child: Stack(
                    children: [
                      Container(height: 3, color: td.border),
                      Align(
                        alignment: Alignment.centerLeft,
                        child: FractionallySizedBox(
                          widthFactor: remaining,
                          child: Container(height: 3, color: barColor),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 8),
              SizedBox(
                width: 26,
                child: Text(
                  '${secs}s',
                  style: TdText.mono(10, color: barColor),
                  textAlign: TextAlign.right,
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

// ── Targeting prompt ──────────────────────────────────────────────────────────

class _TargetingPrompt extends StatelessWidget {
  const _TargetingPrompt({required this.actionType, required this.onCancel});
  final String actionType;
  final VoidCallback onCancel;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final color = actionType == 'attack'
        ? const Color(0xFFC76E6E)
        : const Color(0xFF8B5CF6);
    final label = actionType == 'attack' ? 'ATTACK' : 'SPECIAL';

    return Container(
      decoration: BoxDecoration(
        color: color.withAlpha(12),
        border: Border(left: BorderSide(color: color, width: 2)),
      ),
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      child: Row(
        children: [
          Icon(Icons.gps_fixed, size: 13, color: color),
          const SizedBox(width: 8),
          Text('SELECCIONA OBJETIVO', style: TdText.eyebrow(color: color)),
          const SizedBox(width: 6),
          Text('· $label', style: TdText.mono(10, color: td.muted)),
          const Spacer(),
          GestureDetector(
            onTap: onCancel,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: td.card,
                border: Border.all(color: td.border2),
              ),
              child: Text('CANCELAR', style: TdText.mono(9, color: td.fg2)),
            ),
          ),
        ],
      ),
    );
  }
}

// ── Review panel ──────────────────────────────────────────────────────────────

class _ReviewPanel extends StatelessWidget {
  const _ReviewPanel({required this.controller, required this.onTapEdit});
  final CombatController controller;
  final VoidCallback onTapEdit;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final c = controller;

    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFF13131A),
        border: Border.all(color: td.gold.withAlpha(100)),
      ),
      padding: const EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text('LISTO PARA ENVIAR', style: TdText.eyebrow(color: td.gold)),
              const Spacer(),
              GestureDetector(
                onTap: onTapEdit,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: td.card,
                    border: Border.all(color: td.border2),
                  ),
                  child: Text('EDITAR', style: TdText.mono(9, color: td.fg2)),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          ...List.generate(c.playerChampions.length, (i) {
            final champ = c.playerChampions[i];
            if (!champ.isAlive) return const SizedBox.shrink();
            final action = c.pendingActions[i];
            if (action == null) return const SizedBox.shrink();

            final abbr = action.action == 'attack'
                ? 'ATK'
                : action.action == 'defend'
                    ? 'DEF'
                    : 'SPE';
            final targetLabel =
                action.targetSlot != null ? ' → E${action.targetSlot}' : '';
            final color = action.action == 'attack'
                ? const Color(0xFFC76E6E)
                : action.action == 'defend'
                    ? const Color(0xFF6E9DC7)
                    : const Color(0xFF8B5CF6);

            return Padding(
              padding: const EdgeInsets.only(bottom: 5),
              child: Row(
                children: [
                  Container(
                    width: 32,
                    padding: const EdgeInsets.symmetric(vertical: 2),
                    color: color.withAlpha(40),
                    child: Text(
                      abbr,
                      style: TdText.mono(9, color: color),
                      textAlign: TextAlign.center,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    'S${i + 1} ${champ.displayName}$targetLabel',
                    style: TdText.mono(11, color: td.fg),
                  ),
                ],
              ),
            );
          }),
        ],
      ),
    );
  }
}

// ── Combat header ─────────────────────────────────────────────────────────────

class _CombatHeader extends StatelessWidget {
  const _CombatHeader({
    required this.turn,
    required this.errorCode,
    required this.showLog,
    required this.onToggleLog,
  });

  final int turn;
  final String? errorCode;
  final bool showLog;
  final VoidCallback onToggleLog;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        border: Border(bottom: BorderSide(color: td.border)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
              color: td.card,
              border: Border.all(color: td.border2),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 6,
                  height: 6,
                  decoration: BoxDecoration(
                    color: td.gold,
                    shape: BoxShape.circle,
                  ),
                ),
                const SizedBox(width: 6),
                Text('TURNO $turn', style: TdText.mono(11, color: td.fg)),
              ],
            ),
          ),
          const Spacer(),
          if (errorCode != null)
            Text('Error: $errorCode', style: TdText.mono(10, color: td.loss)),
          const SizedBox(width: 8),
          GestureDetector(
            onTap: onToggleLog,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
              decoration: BoxDecoration(
                color: showLog ? td.goldGlow : td.card,
                border: Border.all(color: showLog ? td.gold : td.border2),
              ),
              child: Text(
                'LOG',
                style: TdText.mono(10, color: showLog ? td.gold : td.fg2),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ── Champion card ─────────────────────────────────────────────────────────────

class _ChampionCard extends StatelessWidget {
  const _ChampionCard({
    required this.champ,
    required this.isActive,
    required this.isTargeted,
    required this.isTargetable,
    required this.side,
    required this.onTap,
    required this.charges,
  });

  final CombatChampion champ;
  final bool isActive;
  final bool isTargeted;
  final bool isTargetable;
  final String side;
  final VoidCallback? onTap;
  final int? charges;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final ko = !champ.isAlive;
    final initial =
        champ.displayName.isNotEmpty ? champ.displayName[0].toUpperCase() : '?';
    final accentColor = side == 'player' ? td.gold : td.loss;

    Color borderColor;
    if (isTargetable) {
      borderColor = td.loss.withAlpha(200);
    } else if (isActive) {
      borderColor = td.gold;
    } else if (isTargeted) {
      borderColor = td.loss;
    } else {
      borderColor = td.border2;
    }

    final hpProgress = (champ.hp / 100).clamp(0.0, 1.0);
    Color hpColor;
    if (champ.hp > 60) {
      hpColor = const Color(0xFF7BB893);
    } else if (champ.hp > 30) {
      hpColor = const Color(0xFFD9A557);
    } else {
      hpColor = const Color(0xFFC76E6E);
    }

    return GestureDetector(
      onTap: onTap,
      child: Opacity(
        opacity: ko ? 0.45 : 1.0,
        child: Stack(
          clipBehavior: Clip.none,
          children: [
            AnimatedContainer(
              duration: const Duration(milliseconds: 180),
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: ko
                    ? const Color(0xFF0F0E10)
                    : isActive
                        ? const Color(0xFF1A180F)
                        : const Color(0xFF15151B),
                border: Border.all(color: borderColor),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  if (isActive)
                    Container(
                      height: 2,
                      color: td.gold,
                      margin: const EdgeInsets.only(bottom: 4),
                    ),
                  Center(
                    child: TdChampionGlyph(
                      initial: initial,
                      size: 48,
                      accent: accentColor,
                      subtle: ko,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    champ.displayName,
                    style: TdText.display(13, color: ko ? td.muted : td.fg),
                    textAlign: TextAlign.center,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Container(
                    height: 6,
                    decoration: BoxDecoration(
                      color: const Color(0xFF0E0E12),
                      border: Border.all(color: td.border),
                    ),
                    child: FractionallySizedBox(
                      alignment: Alignment.centerLeft,
                      widthFactor: ko ? 0 : hpProgress,
                      child: Container(
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                              colors: [hpColor, hpColor.withAlpha(180)]),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    ko ? 'KO' : '${champ.hp}/100',
                    style: TdText.mono(10, color: td.fg2),
                    textAlign: TextAlign.right,
                  ),
                  if (side == 'player' && !ko && charges != null) ...[
                    const SizedBox(height: 4),
                    Row(
                      children: List.generate(3, (i) {
                        return Expanded(
                          child: Container(
                            height: 4,
                            margin: EdgeInsets.only(left: i == 0 ? 0 : 2),
                            color: i < charges! ? td.gold : td.muted2,
                          ),
                        );
                      }),
                    ),
                  ],
                ],
              ),
            ),
            // Crosshair indicator when enemy is targetable
            if (isTargetable && !ko)
              Positioned(
                top: 6,
                right: 6,
                child: Icon(Icons.gps_fixed, size: 14, color: td.loss),
              ),
          ],
        ),
      ),
    );
  }
}

// ── Action badge ──────────────────────────────────────────────────────────────

class _ActionBadge extends StatelessWidget {
  const _ActionBadge({required this.action});
  final SlotAction action;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final color = _color(action.action);
    final label = _label();
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
      decoration: BoxDecoration(
        color: color.withAlpha(50),
        border: Border.all(color: color.withAlpha(128)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(label, style: TdText.mono(9, color: color)),
          const SizedBox(width: 2),
          Icon(Icons.close, size: 9, color: td.muted),
        ],
      ),
    );
  }

  String _label() {
    final abbr = action.action == 'attack'
        ? 'ATK'
        : action.action == 'defend'
            ? 'DEF'
            : 'SPE';
    if (action.action == 'defend' || action.targetSlot == null) return abbr;
    return '$abbr→E${action.targetSlot}';
  }

  Color _color(String act) {
    switch (act) {
      case 'attack':
        return const Color(0xFFC76E6E);
      case 'defend':
        return const Color(0xFF6E9DC7);
      case 'special':
        return const Color(0xFF8B5CF6);
      default:
        return const Color(0xFF7A7872);
    }
  }
}

// ── Action picker ─────────────────────────────────────────────────────────────

class _ActionPicker extends StatelessWidget {
  const _ActionPicker({
    required this.controller,
    required this.onAction,
    this.blocked = false,
  });
  final CombatController controller;
  final ValueChanged<String> onAction;
  final bool blocked;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final c = controller;
    final activeSlot = c.activeSlot;
    final activeChamp =
        (activeSlot != null && activeSlot < c.playerChampions.length)
            ? c.playerChampions[activeSlot]
            : null;
    final disabled = blocked || c.inputLocked || activeChamp == null || !activeChamp.isAlive;
    final canSpecial = !disabled && activeChamp.charges >= 2;

    final slotLabel = activeSlot != null
        ? 'SLOT ${activeSlot + 1} · ${activeChamp?.displayName ?? ''}'
        : 'SELECCIONA UN CAMPEÓN';

    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFF13131A),
        border: Border.all(color: td.border2),
      ),
      padding: const EdgeInsets.all(10),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text(slotLabel, style: TdText.eyebrow(color: td.gold)),
              const Spacer(),
              Text('ELIGE ACCIÓN', style: TdText.mono(10, color: td.muted)),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: _ActionButton(
                  label: 'ATTACK',
                  desc: 'Elige un objetivo.',
                  onPressed: disabled ? null : () => onAction('attack'),
                  bgColor: const Color(0xFF2A1515),
                  borderColor: const Color(0xFFC76E6E),
                  textColor: const Color(0xFFC76E6E),
                ),
              ),
              const SizedBox(width: 6),
              Expanded(
                child: _ActionButton(
                  label: 'DEFEND',
                  desc: 'Reduce daño recibido.',
                  onPressed: disabled ? null : () => onAction('defend'),
                  bgColor: const Color(0xFF111D26),
                  borderColor: const Color(0xFF6E9DC7),
                  textColor: const Color(0xFF6E9DC7),
                ),
              ),
              const SizedBox(width: 6),
              Expanded(
                child: _ActionButton(
                  label: 'SPECIAL',
                  desc: canSpecial ? 'Elige un objetivo.' : 'Req. 2 cargas.',
                  onPressed: (disabled || !canSpecial) ? null : () => onAction('special'),
                  bgColor: const Color(0xFF1A1426),
                  borderColor: const Color(0xFF8B5CF6),
                  textColor: const Color(0xFF8B5CF6),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _ActionButton extends StatelessWidget {
  const _ActionButton({
    required this.label,
    required this.desc,
    required this.onPressed,
    required this.bgColor,
    required this.borderColor,
    required this.textColor,
  });

  final String label;
  final String desc;
  final VoidCallback? onPressed;
  final Color bgColor;
  final Color borderColor;
  final Color textColor;

  @override
  Widget build(BuildContext context) {
    final disabled = onPressed == null;
    return GestureDetector(
      onTap: onPressed,
      child: Opacity(
        opacity: disabled ? 0.4 : 1.0,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          decoration: BoxDecoration(
            color: bgColor,
            border: Border.all(color: borderColor),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                label,
                style: TdText.display(16, color: textColor),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 2),
              Text(
                desc,
                style: TdText.mono(9, color: const Color(0xFF7A7872)),
                textAlign: TextAlign.center,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ── Combat log card ───────────────────────────────────────────────────────────

class _CombatLogCard extends StatelessWidget {
  const _CombatLogCard({required this.events});
  final List<Map<String, dynamic>> events;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final lines = events.isEmpty
        ? ['¡Combate iniciado!']
        : events.reversed
              .take(3)
              .map((e) {
                final pe = (e['playerEvents'] as List<dynamic>? ?? []);
                if (pe.isEmpty) return '';
                final parts = pe.map((ev) {
                  final m = ev as Map<String, dynamic>;
                  final act = m['action'] as String? ?? '?';
                  final dmg = m['damage'] as int? ?? 0;
                  final ts = m['targetSlot'];
                  if (act == 'defend') return 'S${m['actorSlot']}:DEF';
                  return 'S${m['actorSlot']}→E$ts $dmg dmg';
                }).join(' | ');
                return 'T${e['turn']}: $parts';
              })
              .where((s) => s.isNotEmpty)
              .toList();

    return Container(
      key: const Key('combat-recent-log'),
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: td.card,
        border: Border.all(color: td.border2),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('LOG', style: TdText.eyebrow(color: td.muted)),
          const SizedBox(height: 6),
          ...lines.map(
            (line) => Padding(
              padding: const EdgeInsets.only(bottom: 4),
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 5),
                color: td.card2,
                child: Text(line, style: TdText.mono(10, color: td.fg2)),
              ),
            ),
          ),
          if (lines.isEmpty)
            Text('Sin eventos', style: TdText.mono(10, color: td.muted)),
        ],
      ),
    );
  }
}

// ── Empty match state ─────────────────────────────────────────────────────────

class _EmptyMatchState extends StatelessWidget {
  const _EmptyMatchState({
    required this.errorCode,
    required this.onBack,
    required this.onRetry,
  });
  final String? errorCode;
  final VoidCallback onBack;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(
              'ESTADO DE PARTIDA INVÁLIDO',
              style: TdText.display(18, color: td.fg),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
            Text(
              errorCode != null
                  ? 'Error: $errorCode'
                  : 'El servidor devolvió la partida sin estado.\nEs posible que ya haya finalizado.',
              style: TdText.mono(12, color: td.muted),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 32),
            Row(
              children: [
                Expanded(
                  child: GestureDetector(
                    onTap: onBack,
                    child: Container(
                      height: 44,
                      decoration: BoxDecoration(
                        color: td.card,
                        border: Border.all(color: td.border2),
                      ),
                      alignment: Alignment.center,
                      child: Text('VOLVER', style: TdText.mono(12, color: td.fg2)),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: GestureDetector(
                    onTap: onRetry,
                    child: Container(
                      height: 44,
                      decoration: BoxDecoration(
                        color: td.gold.withAlpha(20),
                        border: Border.all(color: td.gold),
                      ),
                      alignment: Alignment.center,
                      child: Text('REINTENTAR', style: TdText.mono(12, color: td.gold)),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

// ── Float entry data ──────────────────────────────────────────────────────────

class _FloatEntry {
  _FloatEntry({
    required this.side,
    required this.slot,
    required this.text,
    required this.color,
  }) : id = '${DateTime.now().microsecondsSinceEpoch}_${side}_$slot';

  final String side; // 'enemy' | 'player'
  final int slot;
  final String text;
  final Color color;
  final String id;
}

// ── Floating damage label ─────────────────────────────────────────────────────

class _FloatingLabel extends StatefulWidget {
  const _FloatingLabel({super.key, required this.text, required this.color});
  final String text;
  final Color color;

  @override
  State<_FloatingLabel> createState() => _FloatingLabelState();
}

class _FloatingLabelState extends State<_FloatingLabel>
    with SingleTickerProviderStateMixin {
  late final AnimationController _ctrl;
  late final Animation<double> _y;
  late final Animation<double> _opacity;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(vsync: this, duration: const Duration(milliseconds: 1300));
    _y = Tween<double>(begin: 0, end: -44).animate(
      CurvedAnimation(parent: _ctrl, curve: Curves.easeOut),
    );
    _opacity = Tween<double>(begin: 1, end: 0).animate(
      CurvedAnimation(parent: _ctrl, curve: const Interval(0.4, 1.0, curve: Curves.easeIn)),
    );
    _ctrl.forward();
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _ctrl,
      builder: (_, child) => Transform.translate(
        offset: Offset(0, _y.value),
        child: Opacity(opacity: _opacity.value, child: child),
      ),
      child: Text(
        widget.text,
        textAlign: TextAlign.center,
        style: TdText.display(22, color: widget.color),
      ),
    );
  }
}

// ── Action assignment row (above player cards) ────────────────────────────────

class _ActionAssignmentRow extends StatelessWidget {
  const _ActionAssignmentRow({
    required this.controller,
    required this.onClear,
  });

  final CombatController controller;
  final ValueChanged<int> onClear;

  @override
  Widget build(BuildContext context) {
    final c = controller;
    if (c.playerChampions.isEmpty) return const SizedBox.shrink();

    return Row(
      children: List.generate(c.playerChampions.length, (i) {
        final pending = c.pendingActions[i];
        return Expanded(
          child: Padding(
            padding: EdgeInsets.only(
              left: i == 0 ? 0 : 4,
              right: i == c.playerChampions.length - 1 ? 0 : 4,
            ),
            child: SizedBox(
              height: 22,
              child: pending != null
                  ? GestureDetector(
                      onTap: () => onClear(i),
                      child: _ActionBadge(action: pending),
                    )
                  : const SizedBox.shrink(),
            ),
          ),
        );
      }),
    );
  }
}
