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

---

## 16. Guía de diseño de combate (acordada)

> Esta sección se considera guía activa de UX/flujo para el combate a partir de ahora.

### 16.1 Objetivo

Mejorar el combate para hacerlo más claro, más satisfactorio visualmente y más testeable funcionalmente, alineado con el flujo definido del proyecto.

### 16.2 Requisitos base obligatorios

1. Incluir fase de **modificadores** antes del combate (selección de 3 por partida).
2. En combate deben existir acciones visibles: **Atacar**, **Defender**, **Especial**.
3. Mostrar siempre las **cargas** del jugador de forma explícita.
4. Mostrar siempre el **nombre del rival** (incluyendo bot).
5. Pantalla de resultado con recompensas y resumen de rendimiento.

### 16.3 UX mínima de combate

#### Estado visible permanente

- HP de ambos lados (barra + valor numérico).
- Turno actual.
- Cargas actuales.
- Última acción del rival.
- Estado defensivo activo (si corresponde).

#### Feedback al usar acciones

- Animación corta de pulsación/glow en botón.
- Texto flotante de resultado (ej.: `-12`, `-18`, `BLOQUEADO`).
- Flash visual de daño/mitigación en barras de vida.
- Bloqueo de inputs mientras se resuelve turno + estado “Resolviendo turno…”.

#### Log de combate

- Mostrar **6 eventos** recientes para facilitar lectura y cálculo manual de la partida.

### 16.4 Decisiones cerradas de comportamiento

1. **Cap de cargas: 2**.
2. **Especial consume 2 cargas**.
3. **Defender mitiga 50%** (temporal; se balanceará más adelante).
4. **Log visible: 6 eventos**.

### 16.5 Pantalla final “épica” (diseño objetivo)

La pantalla final debe sentirse de alto impacto visual (victoria/derrota marcada y cierre emocional de partida), manteniendo utilidad para testeo.

#### Métricas recomendadas para mostrar

- Resultado: Victoria / Derrota / Empate.
- Delta MMR.
- XP ganada.
- Monedas ganadas.
- Gemas ganadas (si aplica en la liquidación).
- Daño total infligido.
- Daño total recibido.
- Turnos totales.
- Uso de acciones (ataques / defensas / especiales).
- Mitigación total por defensas (% o valor acumulado).

#### Recursos UX para “épica”

- Título grande + iconografía prominente.
- Colores de resultado claros (victoria vs derrota).
- Entrada animada del panel de recompensas.
- CTA principal claro (“Continuar”) y secundario (“Jugar de nuevo”).

### 16.6 Nota de evolución

Los valores de balance (daños, mitigaciones, ritmos) se consideran provisionales. Primero se prioriza claridad UX + testabilidad; el balance fino se hará en iteraciones posteriores.

---

## 17. Guía de diseño — Pantalla de Inicio (Home)

> Objetivo: que la Home sea bonita, clara y útil para testear rápido el juego sin perder contexto del progreso.

### 17.1 Objetivos de producto

1. Ser el **hub principal** para entrar a jugar en 1 toque.
2. Mostrar estado del jugador (progreso/economía) de forma resumida.
3. Exponer señales de actividad (misiones, historial corto, ranking) para motivar retorno.
4. Facilitar testing manual con navegación clara hacia flujo de combate.

### 17.2 Jerarquía visual (de arriba a abajo)

1. **Header de perfil**
   - Avatar placeholder.
   - Nombre de jugador.
   - Rango + MMR.
   - Monedas y gemas visibles en chips.

2. **CTA principal “JUGAR”** (hero card)
   - Botón principal dominante y siempre visible en primer viewport.
   - Subtexto contextual (por ejemplo: cola recomendada / campeón seleccionado).

3. **Tarjeta de progreso**
   - Nivel actual.
   - Barra de XP hacia siguiente nivel.
   - Texto numérico de progreso (`XP actual / XP objetivo`).

4. **Misiones diarias (resumen)**
   - Progreso global (`completadas/total`).
   - Hasta 2 misiones destacadas con progreso visual.
   - CTA “Ver todas” hacia la sección completa.

5. **Actividad reciente**
   - Últimas 3 partidas (resultado, rival, turnos, delta MMR).

6. **Accesos rápidos secundarios**
   - Campeones.
   - Ranked.
   - Tienda.
   - Perfil.

### 17.3 Estilo visual objetivo

- Mantener línea oscura/neón ya acordada.
- Tarjetas con gradiente sutil y borde fino.
- Contraste alto para texto clave (MMR, recompensas, CTA).
- Espaciado generoso para lectura rápida en móvil.
- Evitar saturación: máximo 1 CTA primario por pantalla.

### 17.4 Estados UX obligatorios

1. **Loading inicial**
   - Skeleton cards para header, CTA y módulos principales.

2. **Error de carga**
   - Mensaje claro + botón “Reintentar”.
   - No bloquear navegación base si hay datos parciales.

3. **Empty state (sin historial / sin misiones)**
   - Mensaje motivacional + acción sugerida (`Jugar primera partida`).

4. **Offline / API no disponible**
   - Banner no intrusivo en la parte superior.
   - CTA de reintento sin romper la Home.

### 17.5 Microinteracciones

- Animación de entrada suave de tarjetas (fade + slide corto).
- Press feedback consistente en botones y cards.
- Actualización de wallet/progreso con transición breve al volver de una partida.

### 17.6 Datos mínimos que debe mostrar

- `name`, `rank`, `mmrGlobal`, `level`, `experienceTotal`, `experienceToNextLevel`, `coins`, `gems`.
- Resumen de misiones diarias (`completed`, `total`).
- Últimas partidas (`result`, `enemy`, `turns`, `mmrDelta`).

### 17.7 Criterios de aceptación (Home)

1. El usuario puede iniciar flujo de juego desde Home en 1 toque.
2. La Home muestra perfil y economía sin entrar a otras pestañas.
3. Se ve progreso de nivel y misiones de forma inmediata.
4. Se visualizan últimas partidas para facilitar validación de pruebas.
5. Los estados loading/error/empty están contemplados visualmente.
6. La pantalla mantiene coherencia con tema global del juego.

### 17.8 Nota de implementación

La Home prioriza testabilidad funcional + claridad. El objetivo no es solo estética: debe permitir validar rápidamente que backend, progreso y economía se actualizan tras cada combate.

---

## 18. Guía de diseño — Pantalla de Campeones

> Objetivo: que la pantalla de Campeones sea visualmente potente y útil para decidir rápido con qué jugar, validar unlock/select y entender progreso por campeón.

### 18.1 Objetivos de producto

1. Elegir campeón para jugar en pocos toques.
2. Entender de un vistazo cuáles están bloqueados/desbloqueados/seleccionados.
3. Mostrar progreso y rol de cada campeón para decisiones de uso.
4. Facilitar test funcional de `catalog`, `unlock` y `select`.

### 18.2 Jerarquía visual (de arriba a abajo)

1. **Header contextual**
   - Título “Campeones”.
   - Subtexto corto (“Elige tu estilo de combate”).

2. **Campeón activo (hero card)**
   - Nombre, rol, estado y métricas principales.
   - Indicador claro de “Seleccionado actual”.

3. **Grid/lista de campeones**
   - Card por campeón con:
     - nombre,
     - rol,
     - maestría (nivel + XP),
     - MMR del campeón,
     - estado (`BLOQUEADO`, `DISPONIBLE`, `SELECCIONADO`).

4. **Panel de acción fijo inferior**
   - CTA primario según estado:
     - `Seleccionar` si está desbloqueado,
     - `Desbloquear (X monedas)` si está bloqueado.
   - CTA secundario: `Jugar con este campeón`.

### 18.3 Estados UX obligatorios

1. **Loading**
   - Skeleton para hero card y cards de campeones.

2. **Error**
   - Mensaje claro + `Reintentar`.

3. **Sin catálogo**
   - Empty state con acción para recargar.

4. **Wallet insuficiente al desbloquear**
   - Feedback claro de monedas faltantes.

### 18.4 Microinteracciones

- Cambio visual fuerte al seleccionar card activa (borde/neón + escala sutil).
- Confirmación de `unlock` y `select` con toast/snackbar legible.
- Transición breve del estado al actualizar (bloqueado -> disponible -> seleccionado).

### 18.5 Datos mínimos a mostrar

- `id`, `name`, `role`, `owned`, `selected`, `priceCoins`, `masteryLevel`, `masteryXp`, `mmr`.

### 18.6 Criterios de aceptación (Campeones)

1. Se distingue visualmente el campeón seleccionado sin ambigüedad.
2. El usuario puede desbloquear un campeón y seleccionarlo desde la misma pantalla.
3. La UI refleja correctamente estados de backend tras `unlock/select`.
4. La navegación a flujo de juego desde campeón elegido es directa.
5. La pantalla mantiene coherencia visual con Home y Combate.

### 18.7 Nota de implementación

Esta pantalla es clave para test funcional porque valida economía (monedas), propiedad de campeones y selección persistente antes de entrar a matchmaking/combate.

---

## 19. Guía de diseño — Pantalla Ranked

> Objetivo: que Ranked sea aspiracional, clara y confiable para entrar a cola competitiva, entender progreso ELO/MMR y validar el flujo real de matchmaking PvP.

### 19.1 Objetivos de producto

1. Mostrar el estado competitivo del jugador de forma inmediata.
2. Permitir entrar a cola ranked sin fricción.
3. Dar visibilidad de progreso de MMR y metas cercanas.
4. Hacer testeable el flujo de ticket, espera, match encontrado y reintento.

### 19.2 Jerarquía visual (de arriba a abajo)

1. **Header competitivo**
   - Rango actual.
   - MMR global.
   - Delta reciente (últimas partidas) si está disponible.

2. **Hero card Ranked**
   - Estado de cola actual (`No en cola`, `Buscando`, `Partida encontrada`).
   - Campeón seleccionado para entrar.
   - Región/cola activa (si aplica).

3. **CTA principal**
   - `Entrar a Ranked` cuando no hay cola activa.
   - `Cancelar búsqueda` cuando está buscando.
   - `Ir a partida` cuando ya existe `matchId`.

4. **Bloque de progreso competitivo**
   - Meta próxima de MMR.
   - Racha reciente (si aplica).
   - Resumen corto de resultados recientes.

5. **Leaderboard resumido**
   - Top N jugadores (nombre + MMR).
   - CTA secundario para ver ranking completo.

### 19.3 Estados UX obligatorios

1. **Loading**
   - Skeleton para header, hero card y leaderboard.

2. **Error de red/API**
   - Mensaje claro + `Reintentar`.
   - Mantener navegación operativa.

3. **Sin ranking disponible**
   - Empty state con copy explicativo y acción de recarga.

4. **Cola en progreso**
   - Temporizador visible.
   - Estado textual de matchmaking.
   - Botón cancelar siempre accesible.

5. **Partida encontrada**
   - Feedback destacado (visual + texto) y transición clara a combate.

### 19.4 Microinteracciones

- Animación de “buscando rival” (pulse/neón suave).
- Actualización del estado de cola sin saltos bruscos.
- Confirmación visual al entrar/salir de cola.

### 19.5 Datos mínimos a mostrar

- `rank`, `mmrGlobal`.
- `ticketId`, `status`, `queue`, `matchId` (cuando aplique).
- Ranking resumido (`name`, `mmr`).
- Campeón seleccionado para entrar a cola.

### 19.6 Criterios de aceptación (Ranked)

1. Se entiende en menos de 3 segundos el estado competitivo del jugador.
2. Entrar/salir de cola ranked funciona con feedback claro.
3. Si se encuentra partida, la UI guía al usuario a combate sin ambigüedad.
4. El estado de ticket/match se refleja fielmente al backend.
5. La pantalla conserva coherencia visual con Home y Campeones.

### 19.7 Nota de implementación

Ranked debe priorizar fiabilidad visual del estado de cola (source of truth en backend). La UX no debe ocultar estados intermedios importantes para QA (ticket creado, en cola, match asignado, error/reintento).

---

## 20. Guía de diseño — Pantalla Tienda

> Objetivo: que la Tienda sea clara, atractiva y segura para probar economía (compra/equipado) sin confusión de estados.

### 20.1 Objetivos de producto

1. Permitir comprar y equipar ítems en pocos toques.
2. Mostrar de forma transparente el estado de wallet (monedas/gemas).
3. Diferenciar claramente ítems `no comprados`, `comprados` y `equipados`.
4. Facilitar test funcional de `catalog`, `purchase` y `equip`.

### 20.2 Jerarquía visual (de arriba a abajo)

1. **Header de tienda**
   - Título “Tienda”.
   - Chips de wallet siempre visibles (`Monedas`, `Gemas`).

2. **Filtros rápidos**
   - Por tipo de ítem (si aplica).
   - Toggle simple: `Todos` / `Disponibles` / `Comprados`.

3. **Grid/lista de ítems**
   - Card por ítem con:
     - nombre,
     - tipo,
     - precio,
     - estado visual (`NUEVO`, `COMPRADO`, `EQUIPADO`),
     - cantidad (si aplica en inventario).

4. **Panel de acción contextual**
   - `Comprar` si no es owned.
   - `Equipar` si es owned y no equipado.
   - `Equipado` deshabilitado si ya está activo.

### 20.3 Estados UX obligatorios

1. **Loading**
   - Skeleton de wallet + cards.

2. **Error**
   - Mensaje claro + `Reintentar`.

3. **Sin ítems**
   - Empty state con copy y recarga.

4. **Fondos insuficientes**
   - Feedback explícito de costo y saldo faltante.

5. **Compra/equipación exitosa**
   - Confirmación visual inmediata + refresco de wallet.

### 20.4 Microinteracciones

- Animación corta al comprar (highlight de card + actualización de wallet).
- Estado de botón cambia instantáneamente tras éxito (`Comprar` -> `Equipar` -> `Equipado`).
- Feedback con snackbar claro para éxito/error.

### 20.5 Datos mínimos a mostrar

- Wallet: `coins`, `gems`.
- Ítems: `id`, `name`, `type`, `priceCoins`, `owned`, `quantity`, `equipped`.

### 20.6 Criterios de aceptación (Tienda)

1. El usuario entiende su saldo de un vistazo.
2. Comprar y equipar se puede realizar desde la misma pantalla.
3. Los cambios de estado del ítem se reflejan sin ambigüedad.
4. El saldo se actualiza correctamente después de compra.
5. La UI evita compras inválidas por fondos insuficientes con feedback claro.

### 20.7 Nota de implementación

La Tienda debe privilegiar confianza del usuario: el estado mostrado (wallet + ownership + equipado) debe venir de backend y refrescarse tras cada acción para evitar desincronizaciones durante QA.

---

## 21. Guía de diseño — Pantalla Perfil

> Objetivo: que Perfil sea el “panel de identidad y progreso” del jugador, con lectura rápida de rendimiento y trazabilidad para testing.

### 21.1 Objetivos de producto

1. Mostrar identidad del jugador y progreso global.
2. Hacer visible el rendimiento acumulado (partidas, victorias, derrotas).
3. Exponer recursos y avance de nivel en un solo lugar.
4. Permitir validación funcional de datos de perfil tras combates.

### 21.2 Jerarquía visual (de arriba a abajo)

1. **Header de identidad**
   - Avatar placeholder.
   - Nombre de invocador.
   - Rango + MMR global.

2. **Resumen de progresión**
   - Nivel actual.
   - Barra de XP a siguiente nivel.
   - Texto de progreso (`XP total`, `XP restante`).

3. **Wallet y recursos**
   - Monedas.
   - Gemas.

4. **Estadísticas globales**
   - Partidas totales.
   - Victorias.
   - Derrotas.
   - Winrate (%).

5. **Bloque de actividad reciente**
   - Últimos resultados compactos o CTA hacia historial completo.

### 21.3 Estados UX obligatorios

1. **Loading**
   - Skeleton de header, progresión y stats.

2. **Error**
   - Mensaje claro + `Reintentar`.

3. **Perfil incompleto/vacío**
   - Estado vacío amable con acción sugerida (`Jugar primera partida`).

### 21.4 Microinteracciones

- Animación suave al actualizar XP/nivel tras una partida.
- Resaltado breve de cambio en MMR cuando sube o baja.
- Feedback visual consistente en tarjetas de estadísticas.

### 21.5 Datos mínimos a mostrar

- `name`, `rank`, `mmrGlobal`.
- `level`, `experienceTotal`, `experienceToNextLevel`.
- `coins`, `gems`.
- `stats.matches`, `stats.wins`, `stats.losses`.

### 21.6 Criterios de aceptación (Perfil)

1. El usuario entiende su estado de cuenta/progreso en menos de 5 segundos.
2. Tras terminar una partida, el Perfil refleja cambios de MMR/XP/economía.
3. El winrate se calcula y muestra correctamente cuando hay partidas.
4. Los estados loading/error/empty están cubiertos sin romper navegación.
5. La pantalla mantiene coherencia visual con Home, Ranked y Tienda.

### 21.7 Nota de implementación

Perfil debe ser el punto de verificación de consistencia de datos post-combate. Si Home resume, Perfil confirma el detalle de progreso persistido por backend.

---

## 22. Guía de diseño — Pantalla Matchmaking

> Objetivo: que Matchmaking sea clara, emocionante y, sobre todo, permita elegir y entender **diferentes modos de juego** antes de entrar en cola.

### 22.1 Objetivos de producto

1. Permitir al usuario elegir modo de juego de forma explícita.
2. Mostrar estado de cola en tiempo real con buena legibilidad.
3. Reducir incertidumbre durante espera (temporizador + estado + acciones disponibles).
4. Hacer testeable cada variante de matchmaking por separado.

### 22.2 Modos de juego (diseño objetivo)

La pantalla debe destacar un **selector de modos** como elemento principal.

Modos iniciales a contemplar:

1. **Normal vs Bot**
   - Entrada rápida para pruebas de flujo y combate.
   - Prioridad: tiempo de entrada bajo.

2. **Ranked PvP**
   - Cola competitiva real entre jugadores.
   - Debe mostrar claramente que afecta MMR.

3. **Ranked vs Bot (práctica competitiva)**
   - Útil para validar UX ranked cuando no hay rivales.
   - Debe indicarse que es modo de práctica/controlado.

> Nota: la arquitectura visual debe quedar preparada para añadir nuevos modos sin rediseñar la pantalla (eventos, arenas, etc.).

### 22.3 Jerarquía visual (de arriba a abajo)

1. **Header**
   - Título “Matchmaking”.
   - Campeón actualmente seleccionado.

2. **Selector de modo (bloque principal)**
   - Tarjetas o segmented control grande con cada modo.
   - Cada modo debe mostrar: nombre, descripción corta, impacto (MMR sí/no).

3. **Panel de cola activa**
   - Estado (`Creando ticket`, `En cola`, `Partida encontrada`, `Error`).
   - Temporizador visible.
   - Región/cola y `ticketId` (al menos en modo QA o detalle expandible).

4. **CTAs contextuales**
   - `Entrar a cola`.
   - `Cancelar` (si hay cola activa).
   - `Ir a partida` (si hay `matchId`).

5. **Tips contextuales**
   - Mensaje corto por modo (ej. “Ranked PvP puede tardar más pero impacta MMR”).

### 22.4 Estados UX obligatorios

1. **Sin cola activa**
   - Selector de modo habilitado.
   - CTA primario visible.

2. **Creando/esperando cola**
   - Bloquear cambio de modo hasta cancelar.
   - Mostrar estado y tiempo transcurrido.

3. **Partida encontrada**
   - Estado destacado + transición clara a combate.

4. **Error/reintento**
   - Mensaje explícito y acción para reintentar sin perder contexto.

### 22.5 Microinteracciones

- Animación sutil al seleccionar modo.
- Pulso visual del estado “Buscando rival”.
- Cambio visual de CTA según estado de cola.

### 22.6 Datos mínimos a mostrar

- Modo seleccionado (`normal_bot`, `ranked_pvp`, `ranked_bot` a nivel de UX interna).
- Campeón seleccionado.
- Estado de ticket: `ticketId`, `status`, `queue`, `matchId`.
- Temporizador de búsqueda.

### 22.7 Criterios de aceptación (Matchmaking)

1. El usuario puede distinguir y seleccionar modos sin ambigüedad.
2. Cada modo muestra su intención (casual/práctica/competitivo) y efecto en MMR.
3. El estado de cola se actualiza correctamente hasta entrar a combate o cancelar.
4. La UI evita acciones inválidas (ej. cambiar modo durante cola activa).
5. La pantalla soporta ampliación futura de modos sin rehacer estructura base.

### 22.8 Nota de implementación

Matchmaking se define como pantalla estratégica de control de flujo: además de estética, debe ser una herramienta de QA para validar rápidamente comportamiento por modo y transición a combate.

---

## 23. Guía de diseño — Pantalla de Resultado (épica)

> Objetivo: cerrar cada partida con una pantalla impactante, emocional y extremadamente clara para validar recompensas, rendimiento y progresión.

### 23.1 Objetivos de producto

1. Dar sensación de cierre potente de partida (victoria/derrota/empate).
2. Mostrar recompensas y cambios de progreso sin ambigüedad.
3. Permitir lectura rápida de rendimiento para mejorar decisiones del jugador.
4. Facilitar test funcional post-combate (economía, MMR, XP, historial).

### 23.2 Jerarquía visual (de arriba a abajo)

1. **Hero de resultado**
   - Título grande: `¡VICTORIA!`, `DERROTA` o `EMPATE`.
   - Iconografía protagonista y color contextual (verde victoria, rojo derrota, neutro empate).

2. **Resumen de recompensas (bloque premium)**
   - Delta MMR.
   - XP ganada.
   - Monedas ganadas.
   - Gemas ganadas (si aplica).

3. **Resumen de rendimiento de combate**
   - Daño total infligido.
   - Daño total recibido.
   - Turnos jugados.
   - Uso de acciones (ataques/defensas/especiales).
   - Mitigación total por defensa.

4. **Comparativa rápida (opcional si hay datos)**
   - “Tu mejor métrica de esta partida”.
   - “Mejorable en la siguiente”.

5. **CTAs finales**
   - Primario: `Continuar` (volver a Home/flujo principal).
   - Secundario: `Jugar de nuevo` (reenganche rápido a selección/matchmaking).

### 23.3 Estilo visual “épico”

- Entrada animada del panel principal (scale/fade).
- Brillo/neón moderado en resultado y recompensas.
- Contadores animados para números clave (MMR, XP, monedas, gemas).
- Sonido/háptica opcional según resultado (si se integra más adelante).

### 23.4 Estados UX obligatorios

1. **Loading de cierre**
   - Estado breve mientras llega liquidación (`complete`).

2. **Error de liquidación**
   - Mensaje claro + `Reintentar` sin perder partida cerrada.

3. **Recompensas parciales**
   - Si faltan campos (ej. gemas), mostrar fallback elegante (`—` o `0`) sin romper layout.

### 23.5 Microinteracciones

- Número de recompensas con count-up.
- Destello breve en métrica más relevante (ej. `+MMR`).
- Feedback claro al pulsar CTA para evitar doble navegación.

### 23.6 Datos mínimos a mostrar

- Resultado (`victory/defeat/draw`).
- `mmrDelta`.
- `xp`.
- `coins`.
- `gems` (si disponible).
- `damageDealt`, `damageTaken`, `turns`.
- `attackCount`, `defendCount`, `specialCount`.
- `mitigationTotal`.

### 23.7 Criterios de aceptación (Resultado)

1. El resultado de partida se identifica visualmente al instante.
2. Recompensas y progreso quedan claros sin entrar a otras pantallas.
3. Se muestran métricas de rendimiento suficientes para análisis de partida.
4. El usuario puede reengancharse al juego en 1 toque (`Jugar de nuevo`).
5. La pantalla mantiene coherencia con la guía visual global del juego.

### 23.8 Nota de implementación

La pantalla de Resultado es crítica para percepción de calidad del juego: debe combinar espectáculo visual con precisión de datos para que QA y jugador confíen en el cierre de cada combate.
