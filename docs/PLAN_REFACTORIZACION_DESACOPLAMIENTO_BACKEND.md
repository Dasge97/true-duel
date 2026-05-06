# Plan de Refactorizacion de Desacoplamiento Backend

> Estado: activo  
> Objetivo: reducir acoplamiento esencial sin fragmentar el proyecto por deporte.

## 1. Criterio rector

No buscamos "desacoplar por desacoplar".  
Buscamos que una feature nueva:

- pueda entrar tocando pocas piezas,
- tenga fronteras claras,
- no obligue a editar archivos gigantes,
- y no mezcle HTTP, dominio, persistencia y reglas de negocio en el mismo sitio.

## 2. Problemas reales detectados

### 2.1 `MvpApiKernel`

Hoy mezcla en un solo archivo:

- conexion a BD,
- composicion manual de dependencias,
- construccion de controladores,
- autenticacion/admin auth,
- enrutado HTTP,
- normalizacion de respuestas.

Eso lo convierte en un cuello de botella para cualquier cambio transversal.

### 2.2 `GameplayService`

Hoy concentra demasiadas responsabilidades:

- matchmaking,
- ticket lifecycle,
- estado de partida,
- turnos,
- cierre de partida,
- abandono,
- idempotencia,
- coordinacion con progreso competitivo.

Es el principal punto de acoplamiento funcional.

### 2.3 `MotorCombateService`

Ya esta mejor aislado, pero sigue creciendo como motor unico.
Si seguimos metiendo mecanicas sin modularizar efectos y resolutores, se volvera caro de tocar.

## 3. Objetivo de arquitectura

### 3.1 Capa HTTP

Separar:

- composicion de dependencias,
- enrutado,
- auth/admin auth,
- formateo de respuestas.

### 3.2 Capa de aplicacion

Separar casos de uso grandes en servicios mas pequenos:

- `MatchmakingService`
- `TurnResolutionService`
- `MatchSettlementService`
- `MatchAbandonService`

`GameplayService` deberia acabar siendo un facade fino o desaparecer.

### 3.3 Capa de combate

Dividir el motor en modulos por responsabilidad:

- estado inicial,
- resolucion base,
- efectos/estados,
- bonificadores,
- habilidades/personajes.

### 3.4 Capa de integracion interna

Cuando una accion produzca muchas consecuencias, tender a eventos internos simples:

- `PartidaFinalizada`
- `PartidaAbandonada`
- `TurnoResuelto`

No hace falta montar un bus complejo al principio; basta con contratos internos claros.

## 4. Orden de ejecucion recomendado

### Fase 1. Sacar `MvpApiKernel` del centro

- crear `ApiApplication`
- crear bootstrap de dependencias
- crear router por grupos de rutas
- crear formateador de respuestas
- dejar `MvpApiKernel` como wrapper fino temporal o retirarlo del entrypoint

### Fase 2. Cortar `GameplayService`

- extraer matchmaking
- extraer settlement
- extraer abandon
- extraer resolucion de turno

### Fase 3. Cortar `MotorCombateService`

- extraer resolucion de acciones
- extraer manejo de efectos
- extraer bonificadores
- extraer catalogo de habilidades/personajes

### Fase 4. Reducir integraciones rigidas

- introducir eventos internos minimos para post-combate
- desacoplar misiones, competitivo, historial y rewards del cierre directo

## 5. Regla de seguridad

Cada refactor debe:

- preservar contrato API,
- ser incremental,
- compilar por si mismo,
- y no bloquear nuevas features.

## 6. Definicion de cierre

Esta refactorizacion se considerara sana cuando:

1. el entrypoint HTTP no dependa de un kernel monolitico,
2. una feature nueva de combate no obligue a tocar `public/index.php`, routing, gameplay y motor a la vez,
3. `GameplayService` y `MotorCombateService` dejen de ser archivos-orquesta,
4. los cambios funcionales se puedan hacer por modulo y no por cascada.
