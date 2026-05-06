Sistema de títulos exclusivos por cupos (basado en SP)
1) Variable principal
- SP (Skill Points): puntuación competitiva acumulada.
- Se gana/pierde en cada batalla según rendimiento.
---
2) Cálculo de SP por batalla
2.1 Battle Score
Primero se calcula un BattleScore en rango [0,100]:
BattleScore = clamp( Resultado + Ejecucion + Eficiencia + Riesgo - Penalizacion, 0, 100 )
Componentes sugeridos:
- Resultado [0..35]
- Ejecucion [0..25]
- Eficiencia [0..20]
- Riesgo [0..10]
- Penalizacion [0..20]
2.2 Delta de SP
Luego se transforma en cambio de SP:
SP_delta = round((BattleScore - 50) * K)
- K recomendado inicial: 0.6
- Resultado:
  - Score > 50 => sube SP
  - Score < 50 => baja SP
  - Score = 50 => neutro
2.3 Actualización
SP_nuevo = max(0, SP_actual + SP_delta)
---
3) Leaderboard global
- Todos los jugadores se ordenan por SP descendente.
- Ranking por posición real: posicion = 1, 2, 3, ...
Desempates (en orden):
1. SP_reciente (últimas N partidas)
2. BattleScore medio reciente
3. timestamp de alcanzar SP actual
---
4) Sistema de títulos por cupo
No hay rangos abiertos por umbral fijo.  
Cada título tiene plazas limitadas (cupo).
Asignación:
- Se recorren títulos de mayor a menor.
- Se asignan plazas por posición acumulada.
Ejemplo lógico:
- Título 1: cupo 1 -> posición 1
- Título 2: cupo 5 -> posiciones 2-6
- Título 3: cupo 25 -> posiciones 7-31
- etc.
---
5) Mantenimiento dinámico del estatus
- Recalculo periódico del título (ej. cada 10 min).
- Si un jugador cae fuera del cupo, pierde ese título automáticamente.
- Si otro sube por encima, lo reemplaza.
---
6) Anti-abuso / salud competitiva
- MinPartidasSemana: mínimo de actividad para mantener títulos altos.
- DecayInactividad: reducción de SP por inactividad prolongada (solo élite o global suave).
- ProteccionAntifarm: menor impacto de partidas repetidas contra mismos rivales.
- PenalizacionAbandono: reducción SP adicional por surrender/quit abusivo.
---
7) Fórmulas auxiliares sugeridas
7.1 Decay por inactividad (opcional)
Si diasInactivo > D:
SP = SP - floor((diasInactivo - D) * decayRate)
7.2 Factor de repetición rival (anti-farm)
Si repites rival muchas veces:
SP_delta_final = SP_delta * repeatFactor
- repeatFactor en [0.5, 1.0] según frecuencia reciente.
---
8) Salida visible para jugador
- SP actual
- Posición actual
- Título actual
- SP para entrar al siguiente título superior
- SP de margen para defender el título actual
---
Lista vacía de títulos (para definir nombres)
- Título 1 (cupo: ___)
- Título 2 (cupo: ___)
- Título 3 (cupo: ___)
- Título 4 (cupo: ___)
- Título 5 (cupo: ___)
- Título 6 (cupo: ___)
- Título 7 (cupo: ___)
- Título 8 (cupo: ___)
- Título 9 (cupo: ___)
- Título 10 (cupo: ___)