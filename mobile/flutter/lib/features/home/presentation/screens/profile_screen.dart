import 'package:flutter/material.dart';

import '../../../../core/widgets/async_state_card.dart';
import '../../../../core/widgets/stat_chip.dart';
import '../controllers/profile_controller.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key, required this.controller});

  final ProfileController controller;

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
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
        if (c.isLoading) return const Center(child: AsyncStateCard.loading());
        if (c.error != null || c.profile == null) return Center(child: AsyncStateCard.error(message: 'Error cargando perfil', onRetry: c.load));
        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Text(c.profile!.name, style: Theme.of(context).textTheme.headlineSmall),
            const SizedBox(height: 12),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                StatChip(label: 'Rango', value: c.profile!.rank),
                StatChip(label: 'MMR', value: '${c.profile!.mmr}'),
                StatChip(label: 'Nivel', value: '${c.profile!.level}'),
                StatChip(label: 'XP total', value: '${c.profile!.experienceTotal}'),
                StatChip(label: 'XP sig. nivel', value: '${c.profile!.experienceToNextLevel}'),
                StatChip(label: 'Wins', value: '${c.profile!.wins}'),
                StatChip(label: 'Losses', value: '${c.profile!.losses}'),
                StatChip(label: 'Monedas', value: '${c.profile!.coins}'),
                StatChip(label: 'Gemas', value: '${c.profile!.gems}'),
              ],
            ),
          ],
        );
      },
    );
  }
}
