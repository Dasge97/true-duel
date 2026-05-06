# Plan de continuidad backend (pre-integración Flutter)

> Estado: **activo**  
> Objetivo: dejar el backend de True Duel **cerrado, estable y listo** para que Flutter solo consuma API (sin depender de mocks).

---

## 1) Objetivo global de esta fase

Cerrar el backend para que soporte un juego jugable end-to-end con datos reales:

- autenticación,
- perfil/ranking/historial,
- personajes (catálogo + desbloqueo + equipo),
- matchmaking/combate/cierre de partida,
- recompensas/misiones/tienda,
- sistema competitivo SP + títulos,
- contratos API estables para cliente móvil.

La app Flutter podrá ir por detrás temporalmente, pero el backend debe quedar como **fuente de verdad funcional**.

---

## 2) Principios de trabajo (importante)

1. **Sin mocks nuevos en backend**.  
   Todo debe persistir en BD real.

2. **Contrato API primero**.  
   Si una respuesta cambia, se documenta en el contrato y se versiona.

3. **Idempotencia en acciones críticas**.  
   Reintentos del cliente no deben romper estado.

4. **Estado de matchmaking/combate robusto**.  
   Debe tolerar cancelaciones, reintentos, cierres abruptos y carreras.

5. **Nombres y código en español** (salvo compatibilidad externa inevitable).

---

## 3) Inventario funcional actual (resumen)

### 3.1 Ya disponible

- Auth: registro/login.
- Perfil (`/v1/profile`), ranking (`/v1/ranking`), historial (`/v1/history`).
- Personajes:
  - catálogo (`/v1/personajes`),
  - personajes del jugador (`/v1/personajes/mios`),
  - desbloqueo (`/v1/personajes/desbloquear`),
  - equipo (`/v1/equipo` GET/PUT).
- Bonificadores (`/v1/bonificadores-partida`).
- Matchmaking:
  - enqueue,
  - estado ticket,
  - cancelación ticket.
- Combate:
  - estado match,
  - resolver turno,
  - completar partida.
- Tienda/inventario/equipado.
- Misiones diarias + claim.
- SP y títulos competitivos por cupos.

### 3.2 Núcleo de combate

- Sistema de 12 personajes con roles.
- Sinergias por composición.
- Bonificador aleatorio por partida (como se decidió).
- Caps anti-volatilidad aplicados:
  - crítico,
  - fallo,
  - repeticiones/eco,
  - daño máximo por acción.

---

## 4) Pendiente para considerar backend “cerrado para app”

## P0 — Bloqueante (debe estar antes de centrarse en Flutter)

### P0.1 Contrato API estable y documentado

**Qué hacer**
- Crear documento de contrato (request/response/error) para cada endpoint.
- Definir formato único de error:
  - `error.code`
  - `error.message`
  - `error.details` (opcional)
  - `traceId` (opcional)
- Definir tipos de estado (cola, match, settlement) sin ambigüedad.

**Criterio de cierre**
- Existe un `.md` de contrato completo.
- Todos los endpoints cumplen el contrato.
- Cambios futuros se consideran breaking/non-breaking explícitamente.

---

### P0.2 Robustez de estado matchmaking/partida

**Qué hacer**
- TTL de tickets en cola.
- Limpieza de tickets huérfanos/caducados.
- Reglas estrictas:
  - cuándo se puede cancelar,
  - cuándo ya no se puede cancelar,
  - transición `queued -> matched -> active/completed`.
- Evitar dobles matches para un mismo jugador en paralelo.

**Criterio de cierre**
- No hay estados imposibles (ej: ticket cancelado y match activo a la vez).
- Reintentos del cliente no crean duplicados.

---

### P0.3 Idempotencia y concurrencia en operaciones críticas

**Qué hacer**
- Blindar:
  - `POST /v1/matches/{id}/turns`
  - `POST /v1/matches/{id}/complete`
  - compras de tienda
  - claims de misión
- Uso consistente de llaves idempotentes o comprobaciones equivalentes.
- Manejo explícito de conflictos de versión de estado.

**Criterio de cierre**
- Doble click/reintento de red no duplica efectos.
- No hay doble reward ni doble settlement.

---

### P0.4 Unificación de esquema de datos

**Qué hacer**
- Revisar y unificar responsabilidades entre:
  - migraciones,
  - `bootstrap_db.php`,
  - `reset_product_seed.php`.
- Garantizar que la estructura de BD nace de migraciones y seed solo carga contenido.

**Criterio de cierre**
- Entorno nuevo se levanta limpio con pasos reproducibles.
- No hay drift entre tablas esperadas y tablas reales.

---

### P0.5 Endpoint agregado para Home móvil

**Qué hacer**
- Crear `/v1/home/resumen` con payload consolidado:
  - perfil corto (nombre, rango/título, monedas, gemas, nivel, xp),
  - progreso diario (misiones),
  - últimas partidas,
  - estado competitivo (SP + posición/título),
  - estado de cola activa (si aplica).

**Criterio de cierre**
- Home Flutter puede montarse con 1 llamada principal.
- Reducir fan-out de requests del cliente.

---

## P1 — Recomendado antes de “producto jugable competitivo”

### P1.1 Job competitivo periódico
- Recalcular ranking/títulos cada X minutos (no solo al cerrar partida).

### P1.2 Reglas de salud del ladder
- Decay por inactividad.
- Actividad mínima para mantener títulos altos.
- Anti-farm por repetición de rival.
- Penalización por abandono.

### P1.3 Administración de contenido
- Endpoint o herramienta para ajustar:
  - personajes,
  - bonificadores,
  - reglas de outcome,
  - tienda/misiones,
sin tocar código en cada ajuste.

---

## 5) Orden recomendado de ejecución (siguiente sprint)

1. **P0.1 Contrato API**
2. **P0.3 Idempotencia (turn/complete/store/claim)**
3. **P0.2 Robustez matchmaking/TTL**
4. **P0.4 Unificación esquema + scripts**
5. **P0.5 Home resumen**
6. P1.1 / P1.2 / P1.3

---

## 6) Checklist de validación final de backend

- [ ] Registro/login y token válidos.
- [ ] Perfil/ranking/history coherentes tras múltiples partidas.
- [ ] Equipo válido obligatorio para matchmaking.
- [ ] Cola robusta con cancelación/TTL sin estados rotos.
- [ ] Combate estable con turnos, resolución y cierre idempotente.
- [ ] Recompensas y progreso aplicados una sola vez.
- [ ] SP/título/posición consistentes en perfil y ranking.
- [ ] Tienda y misiones sin duplicación de efectos.
- [ ] Endpoint home/resumen listo para UI.
- [ ] Contrato API actualizado y versionado.

---

## 7) Definición de “backend listo para app”

Se considera listo cuando:

1. Todo P0 está cerrado.
2. El contrato API está congelado para integración.
3. Se puede levantar entorno local (docker + seed) de forma reproducible.
4. Una app cliente puede jugar flujo completo:
   - login -> home -> selección/equipo -> cola -> combate -> resultado -> progreso persistido
   sin datos falsos y sin inconsistencias de estado.

---

## 8) Nota de continuidad

El ajuste fino de balance de combate (números exactos) **queda aparcado temporalmente** por decisión de planificación.  
Se retomará después del cierre de P0/P1 de backend estructural.

