import 'package:flutter/material.dart';

import '../../../../core/theme/duel_theme.dart';
import '../../../../core/widgets/async_state_card.dart';
import '../controllers/ranked_controller.dart';

class RankedScreen extends StatefulWidget {
  const RankedScreen({super.key, required this.controller});

  final RankedController controller;

  @override
  State<RankedScreen> createState() => _RankedScreenState();
}

class _RankedScreenState extends State<RankedScreen> with SingleTickerProviderStateMixin {
  late final TabController _tab;

  @override
  void initState() {
    super.initState();
    _tab = TabController(length: 2, vsync: this);
    widget.controller.load();
  }

  @override
  void dispose() {
    _tab.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: widget.controller,
      builder: (context, _) {
        final c = widget.controller;
        final td = context.td;

        if (c.isLoading) return const Center(child: AsyncStateCard.loading());
        if (c.errorCode != null && c.profile == null) {
          return Center(
            child: AsyncStateCard.error(
              message: 'Error cargando ranking',
              onRetry: c.load,
            ),
          );
        }

        return Scaffold(
          backgroundColor: td.bg,
          body: SafeArea(
            child: Column(
              children: [
                // Player card
                if (c.profile != null)
                  Padding(
                    padding: const EdgeInsets.fromLTRB(14, 14, 14, 0),
                    child: _PlayerCard(profile: c.profile!, tab: _tab, td: td),
                  ),
                const SizedBox(height: 14),

                // Tabs
                Container(
                  margin: const EdgeInsets.symmetric(horizontal: 14),
                  decoration: BoxDecoration(
                    border: Border(bottom: BorderSide(color: td.border)),
                  ),
                  child: TabBar(
                    controller: _tab,
                    labelStyle: TdText.mono(12, letterSpacing: 1.0),
                    unselectedLabelStyle: TdText.mono(12, letterSpacing: 1.0, color: td.muted),
                    labelColor: td.gold,
                    unselectedLabelColor: td.muted,
                    indicatorColor: td.gold,
                    indicatorWeight: 2,
                    tabs: const [
                      Tab(text: 'LIGA'),
                      Tab(text: 'TÍTULOS'),
                    ],
                  ),
                ),

                // Content
                Expanded(
                  child: TabBarView(
                    controller: _tab,
                    children: [
                      _RankingList(
                        ranking: c.rankingMmr,
                        mode: _RankMode.mmr,
                        onRefresh: c.load,
                        td: td,
                      ),
                      _RankingList(
                        ranking: c.rankingSp,
                        mode: _RankMode.sp,
                        onRefresh: c.load,
                        td: td,
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}

// ── Lista de ranking ──────────────────────────────────────────────────────────

enum _RankMode { mmr, sp }

class _RankingList extends StatelessWidget {
  const _RankingList({
    required this.ranking,
    required this.mode,
    required this.onRefresh,
    required this.td,
  });

  final List<dynamic> ranking;
  final _RankMode mode;
  final Future<void> Function() onRefresh;
  final DuelPalette td;

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      color: td.gold,
      backgroundColor: td.card,
      onRefresh: onRefresh,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(14, 10, 14, 24),
        children: [
          _TableHeader(mode: mode, td: td),
          if (ranking.isEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 20),
              child: AsyncStateCard.empty(message: 'Aún no hay datos de ranking'),
            )
          else
            ...ranking.asMap().entries.map(
              (e) => _RankingRow(
                position: e.key + 1,
                entry: e.value,
                mode: mode,
                td: td,
              ),
            ),
        ],
      ),
    );
  }
}

// ── Tarjeta del jugador ────────────────────────────────────────────────────────

class _PlayerCard extends StatelessWidget {
  const _PlayerCard({required this.profile, required this.tab, required this.td});

  final dynamic profile;
  final TabController tab;
  final DuelPalette td;

  @override
  Widget build(BuildContext context) {
    final rank = (profile.rank as String).toUpperCase();
    final mmr = profile.mmr as int;
    final sp = profile.sp as int;
    final lp = mmr % 100;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF1F1A12), Color(0xFF14141A)],
        ),
        border: Border.all(color: const Color(0x4DE5B567)),
      ),
      child: AnimatedBuilder(
        animation: tab,
        builder: (_, __) {
          final isSpTab = tab.index == 1;
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('TU POSICIÓN', style: TdText.eyebrow(color: td.muted)),
              const SizedBox(height: 6),
              Text(
                isSpTab ? _titleFromSp(sp).toUpperCase() : rank,
                style: TdText.display(36, color: td.gold),
              ),
              const SizedBox(height: 14),
              IntrinsicHeight(
                child: Row(
                  children: [
                    Expanded(
                      child: _StatCol(label: 'MMR', value: _fmtNum(mmr), td: td),
                    ),
                    VerticalDivider(width: 1, color: td.border),
                    Expanded(
                      child: _StatCol(
                        label: isSpTab ? 'SP' : 'LP',
                        value: isSpTab ? _fmtNum(sp) : '$lp/100',
                        td: td,
                        large: true,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 10),
              Builder(builder: (_) {
                double progress;
                String label;
                if (!isSpTab) {
                  progress = (lp / 100).clamp(0.0, 1.0);
                  label = '${100 - lp} LP PARA PROMOCIÓN';
                } else {
                  int curThreshold = 0;
                  int? nextThreshold;
                  for (var i = 0; i < _kSpTitles.length; i++) {
                    if (sp >= _kSpTitles[i].$1) curThreshold = _kSpTitles[i].$1;
                    if (sp < _kSpTitles[i].$1 && nextThreshold == null) nextThreshold = _kSpTitles[i].$1;
                  }
                  if (nextThreshold == null) {
                    progress = 1.0;
                    label = 'TÍTULO MÁXIMO';
                  } else {
                    final range = nextThreshold - curThreshold;
                    progress = ((sp - curThreshold) / range).clamp(0.0, 1.0);
                    label = '${nextThreshold - sp} SP PARA SIGUIENTE TÍTULO';
                  }
                }
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      height: 4,
                      color: td.surface,
                      child: FractionallySizedBox(
                        alignment: Alignment.centerLeft,
                        widthFactor: progress,
                        child: Container(
                          decoration: BoxDecoration(
                            gradient: LinearGradient(colors: [td.goldDeep, td.gold]),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(label, style: TdText.mono(10, letterSpacing: 0.6, color: td.muted)),
                  ],
                );
              }),
            ],
          );
        },
      ),
    );
  }
}

class _StatCol extends StatelessWidget {
  const _StatCol({required this.label, required this.value, required this.td, this.large = false});

  final String label;
  final String value;
  final DuelPalette td;
  final bool large;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: TdText.mono(9, letterSpacing: 0.8, color: td.muted)),
          const SizedBox(height: 3),
          Text(value, style: large ? TdText.display(26) : TdText.display(20)),
        ],
      ),
    );
  }
}

// ── Encabezado de columnas ────────────────────────────────────────────────────

class _TableHeader extends StatelessWidget {
  const _TableHeader({required this.mode, required this.td});

  final _RankMode mode;
  final DuelPalette td;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 4),
      decoration: BoxDecoration(border: Border(bottom: BorderSide(color: td.border))),
      child: Row(
        children: [
          SizedBox(width: 40, child: Text('POS', style: TdText.mono(9, letterSpacing: 1.0, color: td.muted))),
          Expanded(child: Text('JUGADOR', style: TdText.mono(9, letterSpacing: 1.0, color: td.muted))),
          if (mode == _RankMode.mmr) ...[
            SizedBox(width: 88, child: Text('RANGO', style: TdText.mono(9, letterSpacing: 1.0, color: td.muted))),
            SizedBox(width: 44, child: Text('MMR', style: TdText.mono(9, letterSpacing: 1.0, color: td.muted), textAlign: TextAlign.right)),
          ] else
            SizedBox(width: 120, child: Text('TÍTULO', style: TdText.mono(9, letterSpacing: 1.0, color: td.muted), textAlign: TextAlign.right)),
        ],
      ),
    );
  }
}

// ── Fila de ranking ───────────────────────────────────────────────────────────

class _RankingRow extends StatelessWidget {
  const _RankingRow({
    required this.position,
    required this.entry,
    required this.mode,
    required this.td,
  });

  final int position;
  final dynamic entry;
  final _RankMode mode;
  final DuelPalette td;

  static String _rankFromMmr(int mmr) {
    if (mmr >= 2000) return 'DIAMANTE';
    if (mmr >= 1800) return 'PLATINO';
    if (mmr >= 1600) return 'ORO';
    if (mmr >= 1400) return 'PLATA';
    if (mmr >= 1200) return 'BRONCE';
    return 'HIERRO';
  }

  @override
  Widget build(BuildContext context) {
    final isTop3 = position <= 3;
    final posColor = isTop3 ? td.gold : td.muted;
    final mmr = entry.mmr as int;
    final sp = entry.sp as int;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 13),
      decoration: BoxDecoration(border: Border(bottom: BorderSide(color: td.border, width: 0.5))),
      child: Row(
        children: [
          SizedBox(
            width: 40,
            child: Text(
              position.toString().padLeft(2, '0'),
              style: TdText.display(isTop3 ? 18 : 14, color: posColor),
            ),
          ),
          Expanded(
            child: Text(entry.name as String, style: TdText.ui(13, weight: FontWeight.w600, color: td.fg)),
          ),
          if (mode == _RankMode.mmr) ...[
            SizedBox(
              width: 88,
              child: Text(
                _rankFromMmr(mmr),
                style: TdText.mono(9, letterSpacing: 0.4, color: isTop3 ? td.gold : td.fg2),
              ),
            ),
            SizedBox(
              width: 44,
              child: Text('$mmr', style: TdText.mono(11, color: isTop3 ? td.gold : td.fg2), textAlign: TextAlign.right),
            ),
          ] else
            SizedBox(
              width: 120,
              child: Text(
                _titleFromSp(sp),
                style: TdText.mono(9, letterSpacing: 0.3, color: isTop3 ? td.gold : td.fg2),
                textAlign: TextAlign.right,
              ),
            ),
        ],
      ),
    );
  }
}

// ── Utilidad ──────────────────────────────────────────────────────────────────

String _fmtNum(int n) {
  if (n >= 1000) return '${n ~/ 1000} ${(n % 1000).toString().padLeft(3, '0')}';
  return '$n';
}

const _kSpTitles = [
  (0,     'Novato'),
  (500,   'Iniciado'),
  (1200,  'Guardián'),
  (2000,  'Duelista'),
  (3500,  'Campeón'),
  (5500,  'Élite'),
  (8500,  'Gran Maestro'),
  (14000, 'Leyenda'),
];

String _titleFromSp(int sp) {
  var title = _kSpTitles[0].$2;
  for (final t in _kSpTitles) {
    if (sp >= t.$1) title = t.$2;
  }
  return title;
}
