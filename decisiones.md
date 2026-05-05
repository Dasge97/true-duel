# Decisiones y contexto de `true-duel`

> Documento de traspaso generado desde el estado actual del repo + memoria persistente de Engram.
> Objetivo: poder continuar el proyecto en otro entorno sin perder decisiones ya tomadas.

## 1. Qué es el proyecto

- Nombre: **True Duel**.
- Tipo: **MVP jugable de combate 1v1 por turnos**.
- Cliente principal: **Flutter**.
- Backend: **Symfony/PHP** con evolución desde mock local hacia API real.
- Objetivo actual: tener un flujo completo jugable en móvil, con soporte offline y con backend/API real para evolución de producto.

## 2. Estado funcional que está claro

Según `README.md`, ahora mismo está claro que existe:

- Flujo jugable end-to-end en Flutter: **inicio -> cola -> combate por turnos -> resultado**.
- **4 campeones** seleccionables en el roster MVP.
- **10 modificadores** disponibles, de los que se seleccionan **3 por combate**.
- Mecánica de **defensa con cargas**: acumula hasta 2 y mitiga golpes siguientes.
- Pantalla de recompensas post-partida con **monedas, gemas y delta MMR**.
- Modo **API** y modo **offline local**, siendo el offline el arranque por defecto para pruebas rápidas.

## 3. Decisión macro de producto / arquitectura

### 3.1 Separación de runtime

Decisión tomada:

- El proyecto debe operar como **app Flutter cliente + backend + base de datos**.
- La app Flutter consume el backend por API.
- Backend y base de datos deben poder levantarse en **contenedores Docker locales**.

Motivo:

- Se quiso abandonar el enfoque puramente mock/monolítico para trabajar con una base más realista de producto.

## 4. Estado del backend

### 4.1 Situación anterior detectada

Se confirmó que el backend anterior:

- usaba `MvpApiKernel` con arrays hardcodeados,
- se reconstruía en cada request,
- **no tenía persistencia real**,
- y no estaba integrado con ORM/BD en runtime.

Archivos clave de esa situación:

- `backend/symfony/src/Api/MvpApiKernel.php`
- `backend/symfony/public/index.php`
- `backend/symfony/composer.json`

### 4.2 Evolución realizada

Se dejó encaminado un backend más real con estas decisiones/implementaciones:

- Auth real con PostgreSQL para API local.
- Endpoints reales para:
  - `POST /v1/auth/register`
  - `POST /v1/auth/login`
- Consumo de datos reales para:
  - `/v1/profile`
  - `/v1/ranking`
  - `/v1/users`
  - `/v1/history`

### 4.3 Refactor estructural decidido y aplicado

El backend se reestructuró hacia una arquitectura clásica y legible:

- `Controller/Api`
  - Auth
  - Profile
  - Gameplay
- `Service`
  - Auth
  - Profile
  - Token
- `Repository`
  - User
  - PlayerProfile
  - MatchHistory
- `Entity`
  - User
  - PlayerProfile
  - MatchHistoryEntry

Punto importante:

- `MvpApiKernel` pasa a actuar como **router/entrypoint** delegando a controladores.

Motivo de esta decisión:

- Se buscó una estructura más familiar, convencional y fácil de seguir.
- Preferencia explícita del usuario: arquitectura de **controladores + entidades + capas claras**.

Aprendizaje importante:

- Se está usando un **autoloader PSR-4 simple en `public/index.php`** para modularizar sin meter todavía todo el stack Symfony completo.
- Esto facilita una migración gradual posterior si se quiere endurecer la estructura.

## 5. Estado del frontend Flutter

### 5.1 Decisión de modo de juego

Se habilitó que Flutter pueda arrancar en modo API mediante:

```bash
flutter run --dart-define=GAME_MODE=api --dart-define=API_BASE_URL=http://10.0.2.2:8080
```

Decisión técnica aplicada:

- `GameModeConfig` ya no depende solo de un modo offline hardcodeado.
- El modo API se puede activar con `GAME_MODE=api`.

Aprendizaje clave:

- **Solo** pasar `API_BASE_URL` no bastaba.
- Si el modo seguía en offline, la app no entraba realmente en modo API.

Archivo clave:

- `mobile/flutter/lib/config/game_mode_config.dart`

### 5.2 Base visual de producto ya encaminada

Se implementó una primera base visual más cercana a producto:

- tema centralizado con `DuelTheme` / `DuelPalette`,
- shell principal con navegación inferior,
- secciones principales:
  - Inicio
  - Campeones
  - Ranked
  - Tienda
  - Perfil
- nuevo flujo visual de juego:
  - selección de campeón,
  - matchmaking,
  - combate,
  - resultado.

Archivos clave:

- `mobile/flutter/lib/core/theme/duel_theme.dart`
- `mobile/flutter/lib/features/home/presentation/product_home_shell.dart`
- `mobile/flutter/lib/features/play/presentation/visual_play_flow.dart`
- `mobile/flutter/lib/main.dart`

Decisión importante:

- Mantener el rediseño en rutas/archivos separados del monolito existente para iterar más rápido y romper menos.

### 5.3 Errores corregidos en Flutter

Se corrigieron al menos estos bloqueos de compilación:

- cierre incorrecto de `children` en `LoginScreen` (`)` en vez de `]`),
- acceso nullable conflictivo en selección de campeón (`selected!.name`).

Archivos afectados:

- `mobile/flutter/lib/main.dart`
- `mobile/flutter/lib/features/play/presentation/visual_play_flow.dart`

## 6. Dirección visual del juego

La dirección visual objetivo que sí está clara es esta:

- tema oscuro,
- acentos **verde neón** y **naranja**,
- tarjetas con gradientes suaves,
- bordes sutiles,
- navegación inferior fija,
- consistencia de tokens visuales antes de rematar cada pantalla.

La referencia funcional/visual detectada está en `prototipo/`.

## 7. Relación entre prototipo y app real

Se confirmó una brecha clara entre:

- el prototipo React (`prototipo/`), que ya define muchas pantallas/componentes,
- y la app Flutter previa, que concentraba demasiada UI en `lib/main.dart`.

Pantallas identificadas en el prototipo:

- Lobby
- Campeones
- Ranked
- Tienda
- Perfil
- Combate
- Matchmaking
- Resultado
- Splash

Conclusión importante:

- La migración visual no debe seguir creciendo sobre un `main.dart` monolítico.
- Primero hay que extraer **tokens, tema y componentes**; después migrar pantalla a pantalla.

## 8. Plan de migración visual acordado

Plan definido por fases:

1. Foundation de **design tokens**, tema y componentes base.
2. Shell de navegación y layout.
3. Migración pantalla a pantalla desde prototipo/capturas.
4. Pulido de estados y animaciones.

Orden recomendado de pantallas:

- primero: Inicio / Campeones / Ranked / Tienda / Perfil,
- después: Splash / Matchmaking / Combate / Resultado.

Motivo:

- minimiza riesgo,
- permite validar rápido en Android,
- evita regresiones UX grandes.

## 9. Infra local y ejecución

### 9.1 Flutter

Ruta del SDK que consta en memoria:

- `C:\Users\Practicas 2025\development\flutter`

Además, se dejó configuración de VS Code para forzar el SDK en el workspace:

- `.vscode/settings.json`
- valor: `dart.flutterSdkPath = C:\Users\Practicas 2025\development\flutter`

Aprendizaje:

- En Windows, VS Code puede no heredar bien el `PATH`; fijar `dart.flutterSdkPath` evita depender del entorno del proceso.

### 9.2 Estado de ejecución móvil

Está confirmado en memoria que:

- `flutter run` llegó a funcionar,
- había dispositivo Android abierto en Android Studio,
- ya no había bloqueo base de instalación.

### 9.3 Docker local

Se creó stack local con:

- `docker-compose.yml`
- `backend/symfony/Dockerfile`
- `backend/symfony/scripts/bootstrap_db.php`
- endpoint `GET /health`

Servicios documentados en README:

- API: `http://localhost:8080`
- Healthcheck: `http://localhost:8080/health`
- PostgreSQL: `localhost:5432`
- db/user/pass: `true_duel`

Usuarios seed documentados:

- `playerone` / `123456`
- `raven` / `123456`
- `nova` / `123456`

Bloqueo detectado:

- `docker compose up -d` falló porque **Docker Desktop / engine Linux no estaba arrancado** (`dockerDesktopLinuxEngine` no encontrado).

Conclusión práctica:

- Antes de probar compose, arrancar Docker Desktop y verificar contexto Linux activo.

## 10. Preferencias de trabajo ya conocidas

Preferencias del usuario que conviene conservar:

- Quiere una arquitectura backend **clásica y legible**.
- Quiere que los cambios visuales los implemente el asistente.
- Quiere probar la app móvil con **Android Studio**.
- Prefiere evitar **comandos largos bloqueantes** y recibir estado claro de lo aplicado.

## 11. Estado del árbol de trabajo al generar este documento

Al crear este documento, el repositorio ya tenía cambios sin commit en curso.
Snapshot detectado:

- Modificados:
  - `README.md`
  - `backend/symfony/public/index.php`
  - `backend/symfony/src/Api/MvpApiKernel.php`
  - `mobile/flutter/lib/config/game_mode_config.dart`
  - `mobile/flutter/lib/features/mvp/data/mvp_api_repository.dart`
  - `mobile/flutter/lib/main.dart`
  - varios archivos generados de `linux/`, `macos/`, `windows/` en Flutter
- No trackeados:
  - `backend/symfony/Dockerfile`
  - `backend/symfony/scripts/`
  - `backend/symfony/src/Controller/`
  - `backend/symfony/src/Entity/`
  - `backend/symfony/src/Repository/`
  - `backend/symfony/src/Service/`
  - `docker-compose.yml`
  - `mobile/flutter/lib/core/`
  - `mobile/flutter/lib/features/home/`
  - `mobile/flutter/lib/features/play/`
  - `prototipo/`

Interpretación:

- Hay trabajo avanzado localmente que probablemente forma parte de la transición de MVP/mock a producto con backend real y rediseño visual.
- Este documento **no implica que eso esté committeado**, solo que estaba presente en el árbol al generarlo.

## 12. Archivos y rutas clave para retomar el proyecto

### Repo raíz

- `README.md`
- `docker-compose.yml`
- `decisiones.md` (este archivo)

### Flutter

- `mobile/flutter/pubspec.yaml`
- `mobile/flutter/lib/main.dart`
- `mobile/flutter/lib/config/game_mode_config.dart`
- `mobile/flutter/lib/core/theme/duel_theme.dart`
- `mobile/flutter/lib/features/home/presentation/product_home_shell.dart`
- `mobile/flutter/lib/features/play/presentation/visual_play_flow.dart`
- `mobile/flutter/lib/features/mvp/data/mvp_api_repository.dart`

### Backend

- `backend/symfony/public/index.php`
- `backend/symfony/src/Api/MvpApiKernel.php`
- `backend/symfony/src/Controller/`
- `backend/symfony/src/Service/`
- `backend/symfony/src/Repository/`
- `backend/symfony/src/Entity/`
- `backend/symfony/scripts/bootstrap_db.php`

### Prototipo de referencia

- `prototipo/`

## 13. Qué parece decidido y qué no

### Sí parece decidido

- El juego es un **duelo 1v1 por turnos**.
- El cliente principal es Flutter.
- Debe existir modo offline para desarrollo rápido.
- Se quiere migrar a backend/API real con PostgreSQL.
- La arquitectura backend debe ser clásica por capas.
- La UI objetivo debe seguir la línea visual oscura/neón.
- El prototipo React sirve como referencia funcional/visual.

### Aún no consta como cerrado en memoria

- Modelo de dominio completo de combate más allá del MVP actual.
- Balance final de campeones/modificadores.
- Roadmap de monetización/tienda más allá de existencia visual.
- Contratos definitivos del backend para producción completa.
- Si habrá migración posterior a Symfony “full” o se seguirá con esta base ligera modularizada.

## 14. Resumen ejecutivo corto

`true-duel` es un MVP móvil de combate por turnos que ya tiene flujo jugable completo en Flutter y una transición en marcha desde mocks hacia una arquitectura real con API + PostgreSQL. El backend se ha llevado a una estructura clásica por capas, el frontend ha empezado un rediseño fuerte guiado por un prototipo React, y la dirección visual objetivo está clara. El siguiente entorno de trabajo debería retomar desde esta base, revisar los cambios locales no committeados y decidir si consolida primero backend real, cierre de flujo API o terminación de la migración visual.

## 15. Fuente de este documento

Este archivo se ha construido con:

- `README.md` del repositorio.
- Estado git local observado al generarlo.
- Memoria persistente de Engram del proyecto `true-duel`.

Si algo no aparece aquí, es porque **no estaba suficientemente claro** o **no constaba en memoria persistida**.
