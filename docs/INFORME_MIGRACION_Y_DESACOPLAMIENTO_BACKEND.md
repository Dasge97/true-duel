# Informe de Migracion y Desacoplamiento Backend

> Estado: completado  
> Fecha de cierre: 2026-05-06

## 1. Objetivo

Este proceso perseguia tres metas:

- migrar el backend artesanal a Symfony real,
- unificar persistencia en Doctrine,
- y reducir los acoplamientos estructurales que hacian caro evolucionar el proyecto.

## 2. Resultado final

El backend queda ahora en un estado mucho mas estandar y mantenible:

- runtime HTTP servido por Symfony real
- routing y controladores gestionados por Symfony
- `bin/console` como camino operativo principal
- migraciones nativas de Doctrine
- persistencia unificada en Doctrine DBAL/ORM
- desaparicion del runtime legacy basado en kernel/router manual
- reduccion importante de servicios-orquesta y duplicidades transversales

## 3. Cambios estructurales realizados

### 3.1 Migracion a Symfony real

Se completo la transicion desde una capa HTTP manual a una app Symfony funcional:

- `App\Kernel` y `public/index.php` Symfony
- `config/` y `services.yaml` reales
- rutas HTTP nativas
- comandos Symfony para migraciones, seed y jobs

El antiguo `MvpApiKernel` y la infraestructura HTTP manual dejaron de ser el centro del backend y terminaron siendo eliminados del runtime principal.

### 3.2 Migracion de persistencia

Se consolido Doctrine como capa oficial de acceso a datos:

- migraciones portadas a Doctrine Migrations
- repositorios y servicios runtime sin `PDO` directo
- `Doctrine\DBAL\Connection` y entidades ORM ya integradas en el backend activo

Ademas, se elimino la duplicidad de creacion de esquema entre scripts y migraciones.

### 3.3 Unificacion de arranque y seed

Se dejo un flujo coherente de proyecto nuevo:

1. BD vacia
2. migraciones
3. seed
4. API funcional

Tambien se elimino logica duplicada de titulos competitivos dentro del seed, reutilizando el mismo servicio de negocio del runtime.

## 4. Desacoples ejecutados

### 4.1 Gameplay

`GameplayService` dejo de concentrar matchmaking, turnos, settlement y abandono.

Servicios extraidos:

- `MatchmakingService`
- `TurnResolutionService`
- `MatchSettlementService`
- `MatchAbandonService`

`GameplayService` queda como fachada de coordinacion ligera.

### 4.2 Combate

El antiguo motor monolitico de combate se partio por responsabilidades:

- `CombatRulesEngine`
- `CombatStateFactory`
- `CombatStateMapper`
- `CombatCharacterService`
- `CombatEffectLifecycleService`
- `CombatRandomizerService`
- `BotCombatResolverService`
- `PvpCombatResolverService`

Esto deja el sistema preparado para introducir nuevas mecanicas sin inflar otra vez un unico archivo gigante.

### 4.3 Perfil y resumen home

`ProfileService` dejo de mezclar agregado, lectura y presentacion:

- `ProfileViewFactory`
- `HomeSummaryService`

Con eso se aisla mejor el shape de respuesta API de la coordinacion de datos.

### 4.4 Competitivo

`ClasificacionCompetitivaService` dejo de mezclar scoring, anti-farm, resolucion de rival y asignacion de titulos.

Servicios extraidos:

- `CompetitiveBattleScoreService`
- `CompetitivePenaltyService`
- `CompetitiveTitleService`
- `CompetitiveOpponentResolver`

`ClasificacionCompetitivaService` queda como orquestador del flujo competitivo.

### 4.5 Soporte transversal

Tambien se eliminaron duplicidades transversales:

- `UuidGenerator` para UUID v4 compartido
- `ApiControllerSupport` para soporte comun de controladores HTTP

Eso redujo repeticion en:

- controladores HTTP
- auth
- matchmaking
- turnos
- settlement
- competitivo

## 5. Estado actual de acoplamiento

Lectura honesta actual:

- ya no quedan cuellos de botella estructurales del nivel que habia al principio
- el backend esta bastante mas modular por dominio y por responsabilidad
- los servicios que siguen siendo grandes ahora lo son mas por densidad de negocio que por mezcla tecnica

Los modulos que todavia pueden seguir refinandose en el futuro, pero ya sin urgencia arquitectonica, son:

- `MatchmakingService`
- `TurnResolutionService`
- `MissionService`
- `StoreService`

## 6. Deuda tecnica que ya no es critica

Quedan mejoras posibles, pero ya no bloquean evolucion:

- introducir eventos internos de dominio para post-combate
- aumentar cobertura de tests de integracion y contratos
- seguir moviendo algunas lecturas/payloads repetidas a factories o assemblers pequenos

## 7. Conclusiones

El backend ya no esta en modo MVP improvisado.

Ahora mismo la base permite:

- meter features nuevas con menos impacto en cascada
- evolucionar combate sin tocar media aplicacion
- cambiar payloads y agregados sin mezclar dominio con HTTP
- mantener una ruta de despliegue y bootstrap coherente

La siguiente etapa natural ya no es otra gran migracion tecnica, sino evolucion funcional del juego sobre una arquitectura bastante mas sana.
