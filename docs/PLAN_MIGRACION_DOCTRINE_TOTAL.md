# Plan de Migracion Total a Doctrine

> Estado: activo  
> Objetivo: dejar Doctrine como unica capa oficial de persistencia del backend.

## 1. Objetivo real

No buscamos "tener Doctrine instalado".

Buscamos que:

- el runtime Symfony use Doctrine de forma nativa,
- las entidades de dominio esten mapeadas,
- los repositorios dejen de depender de `PDO` manual,
- las transacciones vivan en `EntityManager` y/o `Doctrine DBAL`,
- y que `PDO` desaparezca del contenedor de la aplicacion.

## 2. Situacion de partida

Hoy el backend ya esta migrado a Symfony como framework:

- `Kernel`, routing, controladores HTTP y `bin/console` son Symfony reales,
- las migraciones ya son `Doctrine Migrations`,
- y ya existe uso real de ORM en `User` y `PlayerProfile`.

Pero la persistencia sigue mezclada:

- parte del dominio usa entidades ORM,
- gran parte de los repositorios siguen en `PDO`,
- y varios servicios siguen abriendo transacciones manuales con `PDO`.

## 3. Criterio de arquitectura

### 3.1 ORM donde aporta mas valor

Usaremos Doctrine ORM para:

- catalogos y tablas maestras,
- perfiles, usuarios e historial,
- partidas y tickets cuando el modelo quede estable,
- agregados con identidad clara.

### 3.2 DBAL donde la operacion sea mas SQL que objeto

Usaremos Doctrine DBAL, pero no `PDO` directo, para:

- bloqueos `FOR UPDATE`,
- operaciones bulk,
- `upsert`,
- queries competitivas y de mantenimiento donde ORM sea torpe o poco claro.

La regla no es "todo con entidades aunque quede peor".
La regla es "todo dentro del stack Doctrine".

## 4. Inventario actual

### 4.1 Ya migrado a ORM

- `auth_users`
- `player_profiles`

### 4.2 Entidades ya existentes pero aun no mapeadas o no explotadas como ORM

- `matches`
- `matchmaking_tickets`
- `match_history`
- `match_settlements`
- `player_champion_ratings`

### 4.3 Repositorios aun dependientes de `PDO`

- catalogos:
  - `CatalogoPersonajesRepositorio`
  - `BonificadorPartidaRepositorio`
  - `StoreCatalogRepository`
  - `MissionCatalogRepository`
  - `MatchOutcomeRuleRepository`
  - `TituloCompetitivoRepositorio`
- progresion e inventario:
  - `PlayerInventoryRepository`
  - `PlayerMissionRepository`
  - `JugadorPersonajeRepositorio`
  - `EquipoJugadorRepositorio`
- gameplay:
  - `GameMatchRepository`
  - `MatchmakingTicketRepository`
  - `TurnRepository`
  - `MatchHistoryRepository`
  - `MatchSettlementRepository`
  - `ChampionRatingRepository`
- soporte:
  - `ApiIdempotencyRepository`
  - `CompetitiveMatchLogRepository`

### 4.4 Servicios aun acoplados a `PDO`

- `AuthService`
- `StoreService`
- `MissionService`
- `GameplayService`
- `ClasificacionCompetitivaService`
- `AdminContentService`
- `HealthController`
- `ProductSeedSupport`

## 5. Orden de migracion

### Fase 1. Catalogos y lecturas simples

- mapear catalogos como entidades ORM,
- migrar sus repositorios a `EntityManagerInterface`,
- migrar admin content para reemplazo de catalogos usando Doctrine,
- eliminar `PDO` de esos flujos.

### Fase 2. Persistencia de progreso

- migrar inventario, misiones diarias, personajes del jugador y equipo,
- mover operaciones de economia/progreso a Doctrine ORM + DBAL,
- sacar `PDO` de `AuthService`, `StoreService` y `MissionService`.

### Fase 3. Gameplay core

- mapear `matches`, `tickets`, `turns`, `history`, `settlements` y `ratings`,
- reemplazar locks/transacciones por `Connection` y `EntityManager`,
- sacar `PDO` de `GameplayService`.

### Fase 4. Competitivo e idempotencia

- migrar `competitive_match_log`, `titulos_competitivos`, `api_idempotency`,
- pasar jobs y recalculos a Doctrine,
- sacar `PDO` de `ClasificacionCompetitivaService`.

### Fase 5. Cierre

- eliminar el servicio `PDO` del contenedor,
- migrar `HealthController` y seed al stack Doctrine/DBAL,
- verificar que no quedan imports `use PDO;` en `src/`.

## 6. Definicion de cierre

La migracion se considerara cerrada cuando:

1. no exista `PDO` inyectado en servicios ni repositorios de `src/`,
2. el contenedor Symfony no registre un servicio `PDO` propio,
3. toda transaccion de aplicacion use `EntityManager` o `Doctrine DBAL`,
4. todas las tablas activas del backend tengan una representacion Doctrine util,
5. las nuevas features se construyan ya sobre Doctrine por defecto.
