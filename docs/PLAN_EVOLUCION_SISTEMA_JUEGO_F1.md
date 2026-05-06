# Plan de Evolucion del Sistema de Juego F1

> Estado: implementado  
> Fecha: 2026-05-06  
> Objetivo: ampliar profundidad jugable sin rehacer la base ya estabilizada

## Estado de ejecucion

Implementado en backend sobre Symfony/Doctrine con:

- pasivas activas para los 12 campeones del roster actual
- especiales enriquecidos y diferenciados por rol
- estados persistentes `Expuesto`, `Fortificado`, `Hemorragia`, `Sobrecarga`, `Silencio tactico` y `Escudo`
- snapshots de combate enriquecidos con pasivas y efectos activos por lado
- `recentEvents` ampliado con informacion util para lectura mobile post-turno
- catalogo de personajes enriquecido con `pasiva`, `tags` y `tipoEspecial`
- telemetria agregada por campeon disponible via `telemetria-personajes`

## 1. Alcance de esta fase

Esta fase se centra en dos mejoras acopladas entre si:

- identidad real de campeones
- efectos persistentes y ventanas tacticas mas legibles

No incluye todavia:

- draft/ban ranked
- temporadas competitivas
- cambios grandes en matchmaking
- rework completo de UI mobile

## 2. Resultado buscado

Al terminar esta fase, cada campeon deberia sentirse mas unico y cada combate deberia tener:

- estados mas reconocibles
- decisiones de tempo mas claras
- mas variedad de lineas tacticas
- mejor lectura de por que se gana o se pierde

## 3. Principios de diseño

### 3.1 Profundidad sin complejidad excesiva

No buscamos convertir el juego en un TCG ni en un RPG de 40 estados.
Cada capa nueva debe:

- ser entendible por el jugador
- caber en mobile sin saturar pantalla
- aportar decisiones reales

### 3.2 Persistencia controlada

Los efectos persistentes deben existir, pero con limites:

- duraciones cortas
- caps claros
- pocas excepciones
- sin loops infinitos

### 3.3 Identidad funcional

Cada campeon debe tener:

- una fantasia tactica reconocible
- una pasiva o rasgo estable
- un especial que no sea solo “mismo daño con distinto nombre”

## 4. Objetivos concretos de producto

### 4.1 Campeones

Subir de “kit diferenciado” a “identidad jugable”.

Entregables:

- 1 rasgo pasivo por campeon
- especiales con mejor curva de uso
- roles mas legibles entre starter, amplifier y finisher

### 4.2 Efectos

Introducir un set corto de efectos persistentes reutilizables.

Set inicial recomendado:

- `Expuesto`
- `Fortificado`
- `Hemorragia`
- `Sobrecarga`
- `Silencio tactico`
- `Escudo`

### 4.3 UX de combate

Aunque esta fase sea backend-first, debe dejar lista la informacion necesaria para mobile:

- efectos activos por lado
- duracion restante
- origen relevante
- eventos recientes entendibles

## 5. Propuesta de sistema

## 5.1 Rasgos pasivos por campeon

Modelo recomendado:

- 1 pasiva simple por campeon
- siempre activa o activable por condicion clara
- sin stacks infinitos

Ejemplos iniciales:

- `Vanguard`: primer golpe sobre objetivo sin estado aplica un micro-bonus de apertura
- `Bulwark`: defender con 0 cargas concede mitigacion extra
- `Riftblade`: gana bonus si ataca objetivo marcado
- `Hexa`: debuffs rivales duran +1 turno una vez por ciclo
- `Oracle`: primer especial aliado del combate cuesta 1 menos
- `Revenant`: si deja al rival bajo cierto umbral, gana curacion parcial
- `Warden`: primer pico de daño recibido por ciclo se reduce
- `Spark`: tras especial, el siguiente ataque gana tempo
- `Mender`: al limpiar un debuff concede un escudo pequeño
- `Grim`: aumenta ejecucion contra rivales debilitados
- `Tracer`: replica parcialmente el ultimo buff util aplicado
- `Null`: reduce volatilidad del primer intercambio clave

## 5.2 Estados persistentes

Estados iniciales recomendados:

- `Expuesto`: recibe bonus de daño de finishers y algunas habilidades
- `Fortificado`: reduce daño final recibido durante N turnos
- `Hemorragia`: daño residual moderado por turno
- `Sobrecarga`: mejora daño o carga del siguiente ataque, luego se consume
- `Silencio tactico`: limita bonus ofensivo o especial del rival durante 1 turno
- `Escudo`: absorbe cantidad fija o porcentaje controlado

Reglas comunes:

- duracion de 1 a 2 turnos en esta fase
- stack limitado o no stackeable segun efecto
- prioridad de consumo definida

## 5.3 Ventanas tacticas

Hay que formalizar tres momentos:

1. apertura
2. amplificacion
3. ejecucion

Cada composicion deberia poder expresar una variante de ese ciclo.

## 6. Cambios tecnicos necesarios

## 6.1 Backend combate

Aprovechar la modularizacion ya hecha para introducir:

- catalogo de estados declarativo en BD o config controlada
- soporte de pasivas por campeon en `CombatCharacterService`
- resolucion de estados persistentes en `CombatEffectLifecycleService`
- consumo/aplicacion de ventanas en `CombatRulesEngine`
- nuevos eventos de combate en snapshots y `recentEvents`

## 6.2 Modelo de datos

Cambios probables:

- ampliar payload de `champions/catalog`
  - `pasiva`
  - `tags`
  - `tipoEspecial`
- ampliar estado de combate serializado
  - efectos por actor con duracion y valor
  - metadata de ultimo trigger tactico

## 6.3 API

No hace falta romper rutas.

Cambios previstos:

- enriquecer `GET /v1/champions/catalog`
- enriquecer snapshots de match y turnos
- mantener compatibilidad agregando campos opcionales

## 6.4 Mobile

Necesidades que este plan debe contemplar:

- badges/slots de efectos activos
- tooltip o label corto por efecto
- mejor lectura del log de turno
- pasiva visible en ficha del campeon

## 7. Orden de implementacion

### Fase 1. Infraestructura de efectos

- formalizar estructura unica de efecto persistente
- definir duracion, stack y reglas de consumo
- preparar snapshots para exponerlos a cliente

### Fase 2. Pasivas de campeon

- introducir soporte backend para pasivas
- activar primero en 3 campeones piloto
- validar que no rompen ritmo del combate

### Fase 3. Especiales ampliados

- retocar especiales existentes para aprovechar estados persistentes
- reforzar sinergias starter/amplifier/finisher

### Fase 4. Extender al roster completo

- llevar la nueva estructura a los 12 campeones
- ajustar costes, caps y duraciones

### Fase 5. Exposicion a cliente y telemetria

- enriquecer payloads
- añadir tracking de uso de estados, triggers y winrate por campeon

## 8. Riesgos

### 8.1 Sobrecomplejidad

Riesgo:

- demasiados estados o reglas poco visibles

Mitigacion:

- set corto
- duraciones cortas
- nombres consistentes

### 8.2 Meta rota

Riesgo:

- una composicion domina demasiado pronto

Mitigacion:

- caps de daño y de turno extra
- simulacion offline
- rollout por campeones piloto

### 8.3 UX confusa

Riesgo:

- backend rico pero mobile no lo explica bien

Mitigacion:

- solo añadir al backend lo que luego pueda renderizarse con claridad

## 9. Criterio de exito

Esta fase se considerara exitosa si:

1. 3 campeones piloto ya se sienten claramente distintos
2. existen al menos 4 estados persistentes utiles y legibles
3. el snapshot de combate expone informacion suficiente para UI
4. el combate gana profundidad sin disparar duracion media
5. el sistema sigue siendo balanceable con reglas comprensibles

## 10. Recomendacion de ejecucion

No desplegar todo el roster a la vez.

Secuencia recomendada:

1. `Vanguard`
2. `Bulwark`
3. `Riftblade`

Porque forman el trio base del juego y permiten validar:

- apertura
- amplificacion
- ejecucion

Si ese trio funciona, el resto del roster se expande con mucho menos riesgo.
