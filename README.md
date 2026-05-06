# True Duel

MVP jugable de combate 1v1 por turnos con flujo API-first (sin fallback offline/mock en la app).

Este repositorio se enfoca en **Flutter mobile (`mobile/flutter`) + API PHP (`backend/symfony`)**.
No se mantiene frontend web estático en la raíz del proyecto.

## Estado MVP

- Flujo jugable end-to-end en Flutter: inicio -> cola -> combate por turnos -> resultado.
- Roster MVP de 4 campeones seleccionables.
- Pool MVP de 10 modificadores aplicables en partida (se seleccionan 3 por combate).
- Defensa con cargas jugable (acumula hasta 2 y mitiga golpes siguientes).
- Recompensas post-partida visibles (monedas, gemas y delta MMR).
- Flujo mobile conectado al backend local (Docker) para estado real de juego.

## Ejecutar local (Flutter)

Requisitos:
- Flutter SDK estable instalado en host o usar Docker.
- Android SDK + emulador Android (si se ejecuta en host).

Comandos:

```bash
cd mobile/flutter
flutter pub get
flutter run
```

La app necesita API disponible para login/juego.

## Backend + BD local con Docker (recomendado)

Requisitos:
- Docker Desktop activo.

Desde la raiz del repo:

```bash
docker compose up -d
```

Servicios:
- API: `http://localhost:8080`
- Healthcheck API: `http://localhost:8080/health`
- PostgreSQL: `localhost:5432` (db/user/pass: `true_duel`)

El contenedor API inicializa esquema base y crea usuarios seed:
- `playerone` / `123456`
- `raven` / `123456`
- `nova` / `123456`

Notas backend actuales:
- `POST /v1/matchmaking/enqueue` soporta `vsBot=true` para cola ranked de pruebas sin rival humano.
- `GET /v1/matchmaking/tickets/{ticketId}` permite consultar estado de ticket en cola ranked.
- `GET /v1/matches/{matchId}` devuelve estado del match para jugadores participantes.
- `POST /v1/matches/{id}/complete` es idempotente (si llamas dos veces, no duplica recompensas/MMR).
- `POST /v1/matchmaking/enqueue` con `queue=ranked` sin `vsBot` ya empareja jugadores reales por ventana MMR en la misma region.
- Los turnos PvP ranked se resuelven por turnos alternos (`currentPlayerId` en estado).
- En matches PvP, ambos jugadores pueden llamar a `POST /v1/matches/{id}/complete` y reciben su liquidacion propia (MMR/recompensas) de forma idempotente.
- Perfil incluye progresion real (`level`, `experienceTotal`, `experienceToNextLevel`) y economia (`coins`, `gems`).
- Sistema de campeones disponible:
  - `GET /v1/champions/catalog`
  - `GET /v1/champions/me`
  - `POST /v1/champions/unlock`
  - `POST /v1/champions/select`
- Sistema de tienda cosmética:
  - `GET /v1/store/catalog`
  - `GET /v1/store/inventory`
  - `POST /v1/store/purchase`
  - `POST /v1/store/equip`
- Sistema de misiones diarias:
  - `GET /v1/missions/daily`
  - `POST /v1/missions/claim`
- Reglas de recompensas/MMR de partida en BD (`match_outcome_rules`) para no hardcodear economía.

### Reset de datos base de producto (1 comando)

Si quieres limpiar datos de pruebas y dejar un baseline mínimo jugable (usuarios/items/misiones):

```bash
docker exec true-duel-api php scripts/reset_product_seed.php
```

Para parar:

```bash
docker compose down
```

### Ejecutar app contra API local

Lanza con:

```bash
cd mobile/flutter
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8080
```

Credenciales seed para login API:
- `playerone` / `123456`
- `raven` / `123456`
- `nova` / `123456`

## Generar APK debug (instalable)

Desde `mobile/flutter`:

```bash
flutter build apk --debug
```

APK esperado:

`mobile/flutter/build/app/outputs/flutter-apk/app-debug.apk`

Instalacion manual en dispositivo/emulador conectado:

```bash
adb install -r build/app/outputs/flutter-apk/app-debug.apk
```

## Validacion rapida recomendada

```bash
cd mobile/flutter
flutter analyze
flutter test
```

Integracion API end-to-end (requiere `docker compose up -d`):

```bash
php backend/symfony/tests/api_contracts_integration.php
```
