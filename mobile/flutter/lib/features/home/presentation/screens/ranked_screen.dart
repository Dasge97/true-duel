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

class _RankedScreenState extends State<RankedScreen> {
  @override
  void initState() {
    super.initState();
    widget.controller.load();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: widget.controller,
      builder: (context, _) {
        final c = widget.controller;
        final duel = context.duel;
        if (c.isLoading) return const Center(child: AsyncStateCard.loading());
        if (c.errorCode != null && c.profile == null && c.ranking.isEmpty) {
          return Center(child: AsyncStateCard.error(message: 'Error cargando ranking', onRetry: c.load));
        }

        return RefreshIndicator(
          onRefresh: c.load,
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 20),
            children: [
              if (c.profile != null)
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Row(
                      children: [
                        Container(
                          width: 44,
                          height: 44,
                          decoration: BoxDecoration(
                            color: duel.surfaceAlt,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: duel.border),
                          ),
                          child: Icon(Icons.emoji_events_outlined, color: duel.accentOrange),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('Tu ranking', style: Theme.of(context).textTheme.titleMedium),
                              const SizedBox(height: 2),
                              Text(c.profile!.rank, style: const TextStyle(fontWeight: FontWeight.w700)),
                            ],
                          ),
                        ),
                        Text(
                          '${c.profile!.mmr} MMR',
                          style: TextStyle(
                            color: duel.neonGreen,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              const SizedBox(height: 12),
              Text('Top Global', style: Theme.of(context).textTheme.titleLarge),
              const SizedBox(height: 8),
              if (c.ranking.isEmpty)
                const AsyncStateCard.empty(message: 'Aún no hay datos de ranking')
              else
                ...c.ranking.asMap().entries.map(
                      (entry) => _RankingRow(
                        position: entry.key + 1,
                        name: entry.value.name,
                        mmr: entry.value.mmr,
                      ),
                    ),
            ],
          ),
        );
      },
    );
  }
}

class _RankingRow extends StatelessWidget {
  const _RankingRow({
    required this.position,
    required this.name,
    required this.mmr,
  });

  final int position;
  final String name;
  final int mmr;

  @override
  Widget build(BuildContext context) {
    final duel = context.duel;
    final highlight = position <= 3;

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: Container(
          width: 36,
          height: 36,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: highlight ? duel.accentOrange.withOpacity(0.2) : duel.surfaceAlt,
            border: Border.all(color: highlight ? duel.accentOrange.withOpacity(0.5) : duel.border),
          ),
          alignment: Alignment.center,
          child: Text(
            '$position',
            style: TextStyle(fontWeight: FontWeight.w800, color: highlight ? duel.accentOrange : duel.textPrimary),
          ),
        ),
        title: Text(name, style: const TextStyle(fontWeight: FontWeight.w700)),
        trailing: Text(
          '$mmr LP',
          style: TextStyle(
            color: duel.accentOrange,
            fontWeight: FontWeight.w800,
          ),
        ),
      ),
    );
  }
}
