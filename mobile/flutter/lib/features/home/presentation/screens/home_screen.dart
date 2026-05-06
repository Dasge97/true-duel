import 'package:flutter/material.dart';

import '../../../../core/theme/duel_theme.dart';
import '../../../../core/widgets/async_state_card.dart';
import '../controllers/home_controller.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key, required this.controller, required this.onPlay});

  final HomeController controller;
  final VoidCallback onPlay;

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  String? _claimingMissionId;

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
        if (!c.hasAnyData) return Center(child: AsyncStateCard.error(message: 'Error cargando Home', onRetry: c.load));

        final profile = c.profile;
        final missions = c.missions;
        final avatarLetter = (profile?.name.isNotEmpty ?? false) ? profile!.name.substring(0, 1).toUpperCase() : 'G';
        final xpTotal = profile?.experienceTotal ?? 0;
        final xpToNext = profile?.experienceToNextLevel ?? 0;
        final xpDenominator = xpTotal + xpToNext;
        final xpProgress = xpDenominator > 0 ? (xpTotal / xpDenominator) : 0.0;

        return RefreshIndicator(
          onRefresh: c.load,
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(16, 10, 16, 20),
            children: [
            if (profile == null)
              AsyncStateCard.error(message: 'Perfil no disponible', onRetry: c.load)
            else ...[
              Row(
                children: [
                  CircleAvatar(
                    radius: 21,
                    backgroundColor: duel.neonGreen,
                    child: Text(
                      avatarLetter,
                      style: TextStyle(color: duel.bg, fontWeight: FontWeight.w800, fontSize: 18),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(profile.name, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700)),
                        const SizedBox(height: 1),
                        Text('${profile.rank} · ${profile.mmr} MMR', style: TextStyle(fontSize: 12, color: duel.textSecondary)),
                      ],
                    ),
                  ),
                  _WalletPill(icon: Icons.monetization_on_outlined, value: '${profile.coins}'),
                  const SizedBox(width: 6),
                  _WalletPill(icon: Icons.diamond_outlined, value: '${profile.gems}'),
                ],
              ),
              const SizedBox(height: 18),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(16),
                  gradient: LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [
                      duel.surfaceAlt.withOpacity(0.85),
                      duel.surface.withOpacity(0.95),
                    ],
                  ),
                  border: Border.all(color: duel.border),
                ),
                child: Column(
                  children: [
                    const Icon(Icons.sports_martial_arts, size: 28),
                    const SizedBox(height: 6),
                    const Text('JUGAR', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 28)),
                    const SizedBox(height: 4),
                    Text('Buscar rival competitivo', style: TextStyle(color: duel.textSecondary)),
                    const SizedBox(height: 14),
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton(
                        onPressed: widget.onPlay,
                        child: const Text('Entrar a partida'),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 10),
              Row(
                children: [
                  Icon(Icons.local_fire_department_outlined, size: 14, color: duel.textSecondary),
                  const SizedBox(width: 4),
                  Text('Racha: --', style: TextStyle(color: duel.textSecondary, fontSize: 12)),
                  const SizedBox(width: 14),
                  Icon(Icons.schedule_outlined, size: 14, color: duel.textSecondary),
                  const SizedBox(width: 4),
                  Text('Tiempo promedio: --', style: TextStyle(color: duel.textSecondary, fontSize: 12)),
                ],
              ),
            ],
            const SizedBox(height: 16),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Nivel ${profile?.level ?? '-'}', style: const TextStyle(fontWeight: FontWeight.w600)),
                    const SizedBox(height: 8),
                    ClipRRect(
                      borderRadius: BorderRadius.circular(8),
                      child: LinearProgressIndicator(
                        value: xpProgress,
                        minHeight: 8,
                        backgroundColor: duel.surfaceAlt,
                        valueColor: AlwaysStoppedAnimation<Color>(duel.accentOrange),
                      ),
                    ),
                    const SizedBox(height: 6),
                    Align(
                      alignment: Alignment.centerRight,
                      child: Text(
                        '$xpTotal XP total · $xpToNext para siguiente nivel',
                        style: TextStyle(color: duel.textSecondary, fontSize: 12),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 14),
            if (missions == null)
              AsyncStateCard.error(message: 'Misiones no disponibles', onRetry: c.load)
            else ...[
              Row(
                children: [
                  Text('Misiones Diarias', style: Theme.of(context).textTheme.titleMedium),
                  const Spacer(),
                  Text(
                    '${missions.completed}/${missions.total} completadas',
                    style: TextStyle(color: duel.textSecondary, fontSize: 12),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              ...missions.missions.map((m) {
                final progress = m.target <= 0 ? 0.0 : (m.progress / m.target).clamp(0.0, 1.0);
                final canClaim = m.completed && !m.claimed;
                final isClaiming = _claimingMissionId == m.missionId;

                return Card(
                  margin: const EdgeInsets.only(bottom: 8),
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(m.title, style: const TextStyle(fontWeight: FontWeight.w700)),
                                  const SizedBox(height: 2),
                                  Text('${m.progress}/${m.target}', style: TextStyle(fontSize: 12, color: duel.textSecondary)),
                                ],
                              ),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(color: duel.surfaceAlt, borderRadius: BorderRadius.circular(8), border: Border.all(color: duel.border)),
                              child: Text('+${m.rewardCoins}', style: TextStyle(fontSize: 12, color: duel.neonGreen, fontWeight: FontWeight.w700)),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        ClipRRect(
                          borderRadius: BorderRadius.circular(10),
                          child: LinearProgressIndicator(
                            value: progress,
                            minHeight: 6,
                            backgroundColor: duel.surfaceAlt,
                            valueColor: AlwaysStoppedAnimation<Color>(duel.neonGreen),
                          ),
                        ),
                        if (canClaim || m.claimed) ...[
                          const SizedBox(height: 8),
                          Align(
                            alignment: Alignment.centerRight,
                            child: canClaim
                                ? OutlinedButton(
                                    onPressed: isClaiming
                                        ? null
                                        : () async {
                                            setState(() => _claimingMissionId = m.missionId);
                                            try {
                                              await c.claim(m.missionId);
                                            } finally {
                                              if (mounted) setState(() => _claimingMissionId = null);
                                            }
                                          },
                                    child: isClaiming
                                        ? const SizedBox(
                                            width: 14,
                                            height: 14,
                                            child: CircularProgressIndicator(strokeWidth: 2),
                                          )
                                        : const Text('Reclamar'),
                                  )
                                : Text('Reclamada', style: TextStyle(fontSize: 12, color: duel.textSecondary)),
                          ),
                        ],
                      ],
                    ),
                  ),
                );
              }),
            ],
            const SizedBox(height: 10),
            Text('Últimas partidas', style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 8),
            if (c.history.isEmpty && c.historyError != null)
              AsyncStateCard.error(message: 'Historial no disponible', onRetry: c.load)
            else if (c.history.isEmpty)
              const AsyncStateCard.empty(message: 'Todavía no hay partidas recientes')
            else
              ...c.history.map(
                (h) => Card(
                  margin: const EdgeInsets.only(bottom: 8),
                  child: ListTile(
                    dense: true,
                    leading: Icon(
                      h.result.toLowerCase() == 'win' ? Icons.trending_up_rounded : Icons.trending_down_rounded,
                      color: h.result.toLowerCase() == 'win' ? duel.success : duel.danger,
                    ),
                    title: Text('vs ${h.enemy}', style: const TextStyle(fontWeight: FontWeight.w600)),
                    subtitle: Text('${h.turns} turnos'),
                    trailing: Text(
                      '${h.mmrDelta >= 0 ? '+' : ''}${h.mmrDelta}',
                      style: TextStyle(fontWeight: FontWeight.w700, color: h.mmrDelta >= 0 ? duel.success : duel.danger),
                    ),
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

class _WalletPill extends StatelessWidget {
  const _WalletPill({required this.icon, required this.value});

  final IconData icon;
  final String value;

  @override
  Widget build(BuildContext context) {
    final duel = context.duel;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: duel.surfaceAlt,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: duel.border),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: duel.textSecondary),
          const SizedBox(width: 4),
          Text(value, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
        ],
      ),
    );
  }
}
