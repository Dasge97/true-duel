import 'package:flutter/material.dart';
import '../../../../core/theme/duel_theme.dart';

// ── Modelo de datos ────────────────────────────────────────────────────────────

class _LogroData {
  const _LogroData({
    required this.initial,
    required this.title,
    required this.desc,
    this.date,
    this.progress,
  });
  final String initial;
  final String title;
  final String desc;
  final String? date;
  final double? progress;

  bool get isUnlocked => date != null;
}

const _kLogros = [
  _LogroData(initial: 'V', title: 'PRIMERA VICTORIA', desc: 'Gana tu primera partida ranked.', date: '12 ABR'),
  _LogroData(initial: '5', title: 'RACHA DE 5', desc: 'Gana 5 partidas seguidas.', date: '03 MAY'),
  _LogroData(initial: 'S', title: 'SINERGIA TOTAL', desc: 'Gana con un equipo de 3 roles distintos.', date: '17 MAY'),
  _LogroData(initial: '*', title: 'ÉLITE GLOBAL', desc: 'Alcanza el top 1 000 mundial.', progress: 0.18),
  _LogroData(initial: '*', title: '100 PARTIDAS', desc: 'Juega 100 partidas competitivas.', progress: 0.84),
  _LogroData(initial: '*', title: 'INVICTO', desc: 'Gana 10 partidas seguidas sin perder ningún slot.', progress: 0.36),
  _LogroData(initial: '*', title: 'COLECCIONISTA', desc: 'Posee 12 campeones distintos.', progress: 0.56),
  _LogroData(initial: '*', title: 'MAESTRÍA DOBLE', desc: 'Alcanza nivel 10 con 2 campeones.', progress: 0.10),
];

// ── Filtro ─────────────────────────────────────────────────────────────────────

enum _Filter { todos, desbloqueados, enProgreso }

// ── Screen ─────────────────────────────────────────────────────────────────────

class LogrosScreen extends StatefulWidget {
  const LogrosScreen({super.key});

  @override
  State<LogrosScreen> createState() => _LogrosScreenState();
}

class _LogrosScreenState extends State<LogrosScreen> {
  _Filter _filter = _Filter.todos;

  List<_LogroData> get _filtered => switch (_filter) {
        _Filter.todos => _kLogros,
        _Filter.desbloqueados => _kLogros.where((l) => l.isUnlocked).toList(),
        _Filter.enProgreso => _kLogros.where((l) => !l.isUnlocked).toList(),
      };

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final unlocked = _kLogros.where((l) => l.isUnlocked).length;
    final total = _kLogros.length;
    final filtered = _filtered;

    return Scaffold(
      backgroundColor: td.bg,
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Cabecera ──────────────────────────────────────────────────────
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  GestureDetector(
                    onTap: () => Navigator.of(context).pop(),
                    child: Icon(Icons.arrow_back, color: td.fg, size: 20),
                  ),
                  const SizedBox(width: 12),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('LOGROS', style: TdText.display(22, color: td.fg)),
                      Text(
                        '$unlocked / $total',
                        style: TdText.display(16, color: td.muted),
                      ),
                    ],
                  ),
                  const Spacer(),
                  // ── Tabs filtro ──────────────────────────────────────────
                  Row(
                    children: [
                      _FilterChip(
                        label: 'TODOS',
                        selected: _filter == _Filter.todos,
                        onTap: () => setState(() => _filter = _Filter.todos),
                      ),
                      const SizedBox(width: 4),
                      _FilterChip(
                        label: 'DESBLOQUEADOS',
                        selected: _filter == _Filter.desbloqueados,
                        onTap: () => setState(() => _filter = _Filter.desbloqueados),
                      ),
                      const SizedBox(width: 4),
                      _FilterChip(
                        label: 'EN PROGRESO',
                        selected: _filter == _Filter.enProgreso,
                        onTap: () => setState(() => _filter = _Filter.enProgreso),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 10),
            // ── Lista ─────────────────────────────────────────────────────────
            Expanded(
              child: ListView.builder(
                itemCount: filtered.length,
                itemBuilder: (_, i) => _LogroRow(data: filtered[i]),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Chip de filtro ─────────────────────────────────────────────────────────────

class _FilterChip extends StatelessWidget {
  const _FilterChip({required this.label, required this.selected, required this.onTap});
  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
        decoration: BoxDecoration(
          color: selected ? td.gold : td.surface,
          border: Border.all(color: selected ? td.goldDeep : td.border),
        ),
        child: Text(
          label,
          style: TdText.mono(
            8,
            color: selected ? const Color(0xFF16110A) : td.muted,
            weight: FontWeight.w700,
          ),
        ),
      ),
    );
  }
}

// ── Fila de logro ──────────────────────────────────────────────────────────────

class _LogroRow extends StatelessWidget {
  const _LogroRow({required this.data});
  final _LogroData data;

  @override
  Widget build(BuildContext context) {
    final td = context.td;
    final unlocked = data.isUnlocked;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: BoxDecoration(
        border: Border(bottom: BorderSide(color: td.border)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // ── Glyph ─────────────────────────────────────────────────────────
          if (unlocked)
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(
                  colors: [td.gold, td.goldDeep],
                  center: const Alignment(-0.3, -0.3),
                ),
                border: Border.all(color: const Color(0xFF8E6A2C), width: 2),
              ),
              alignment: Alignment.center,
              child: Text(
                data.initial,
                style: TdText.display(20, color: const Color(0xFF1A140A)),
              ),
            )
          else
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: td.surface,
                border: Border.all(color: td.border),
              ),
              alignment: Alignment.center,
              child: Icon(Icons.star_outline, size: 22, color: td.muted),
            ),
          const SizedBox(width: 14),
          // ── Info ──────────────────────────────────────────────────────────
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Text(
                      data.title,
                      style: TdText.ui(13, weight: FontWeight.w700, color: td.fg),
                    ),
                    if (data.date != null) ...[
                      const SizedBox(width: 8),
                      Text(
                        '· ${data.date}',
                        style: TdText.mono(10, color: td.gold),
                      ),
                    ],
                  ],
                ),
                const SizedBox(height: 3),
                Text(data.desc, style: TdText.ui(12, color: td.fg2)),
                if (data.progress != null) ...[
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Expanded(
                        child: Container(
                          height: 4,
                          color: td.surface,
                          child: FractionallySizedBox(
                            alignment: Alignment.centerLeft,
                            widthFactor: data.progress!,
                            child: Container(
                              decoration: BoxDecoration(
                                gradient: LinearGradient(
                                  colors: [td.goldDeep, td.gold],
                                ),
                              ),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Text(
                        '${(data.progress! * 100).round()}%',
                        style: TdText.mono(10, color: td.gold),
                      ),
                    ],
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}
