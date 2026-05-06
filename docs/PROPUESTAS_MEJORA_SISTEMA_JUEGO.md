# Propuestas de Mejora del Sistema de Juego

> Base: sistema actual ya jugable y desacoplado  
> Enfoque: mejoras con retorno de producto y coste razonable

## 1. Prioridad alta

### 1.1 Especiales con identidad real por campeon

Hoy los personajes ya tienen diferencias utiles, pero todavia se puede subir mucho la expresividad.

Propuesta:

- hacer que cada campeon tenga una curva tactica mas marcada
- introducir 1 rasgo pasivo por campeon
- reforzar counters y sinergias entre roles

Impacto:

- mas profundidad con poco coste de UX
- mas variedad real de composiciones
- mejor sensacion de roster

### 1.2 Efectos persistentes y ventanas tacticas

Ahora el sistema ya soporta estados, pero todavia puede ganar capas:

- veneno/quemadura/control suave
- buffs temporales visibles
- marcas detonables
- estados que alteren economia de cargas

Impacto:

- turnos con mas lectura y planificacion
- menos sensacion de intercambio lineal

### 1.3 Draft o ban ligero en ranked

Cuando el roster crezca un poco, ranked mejora mucho si no todo es “cola y pelea directa”.

Propuesta simple:

- 1 ban ciego por jugador
- o seleccion de equipo con orden oculto y reveal

Impacto:

- mas expresion competitiva
- menos composiciones dominantes

## 2. Prioridad media

### 2.1 Rework de bonificadores de partida

Los modificadores ya ayudan a variar combates, pero conviene separarlos en familias:

- tacticos
- explosivos
- control de RNG
- economia de cargas

Y ademas:

- limitar mejor volatilidad en ranked
- dejar los mas locos para modos casuales/evento

### 2.2 Misiones mas ligadas al estilo de juego

Ahora las misiones cumplen funcion operativa.

Mejora:

- misiones por arquetipo
- misiones por campeon
- misiones por decision tactica

Ejemplos:

- ganar mitigando X dano
- rematar con especial
- activar una sinergia concreta

### 2.3 Historial y telemetria de combate

El juego ganaria mucho si cada partida deja mejor huella:

- timeline de turnos
- dano por campeon
- uso de especiales
- mitigacion total
- causa principal de victoria/derrota

Impacto:

- mejor UX post-partida
- mejor balance interno
- mejor base para analytics

## 3. Prioridad alta cuando haya masa de jugadores

### 3.1 Temporadas competitivas

Sistema recomendado:

- reset parcial por temporada
- recompensas de cierre
- titulos cosmeticos temporales o permanentes
- historial de mejor rango

### 3.2 MMR por composicion o por campeon mas visible

Ya existe base de rating por campeon.

Se puede aprovechar mejor mostrando:

- campeon mas fuerte del jugador
- winrate por campeon
- especializacion visible en perfil

### 3.3 Protecciones competitivas extra

- dodge/afk tracking
- colas regionales o por latencia real
- deteccion de patrones de farm coordinado

## 4. Mejoras de sistema recomendadas

### 4.1 Eventos internos de dominio

Seria la siguiente mejora tecnica con impacto funcional.

Eventos sugeridos:

- `MatchFinished`
- `MatchAbandoned`
- `TurnResolved`
- `RewardGranted`

Eso desacoplaria aun mas:

- misiones
- competitivo
- historial
- notificaciones
- telemetria

### 4.2 Simulacion y balance offline

Muy recomendable crear herramientas para:

- simular miles de combates
- medir winrate por campeon
- detectar loops rotos
- probar combinaciones de bonificadores

### 4.3 Test suite de reglas de combate

Ahora que combate esta modularizado, ya compensa mucho meter tests por:

- especiales
- efectos
- mitigaciones
- turnos extra
- caps de RNG

## 5. Roadmap sugerido

Orden que yo seguiria:

1. profundizar identidad de campeones
2. ampliar sistema de efectos persistentes
3. reordenar bonificadores por familias y modos
4. mejorar post-partida e historial
5. introducir eventos internos
6. preparar temporadas competitivas

## 6. Recomendacion principal

Si tuviera que elegir una sola mejora para empezar ya:

`identidad de campeones + efectos persistentes`

Es la que mejor mezcla:

- valor para jugador
- impacto jugable
- reutilizacion de la arquitectura que ya dejamos lista
