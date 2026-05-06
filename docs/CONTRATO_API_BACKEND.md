# Contrato API Backend

> Version: `v1`  
> Estado: activo  
> Ultima revision: `2026-05-06`

Este documento fija el contrato actual de la API backend de True Duel para la integracion movil. Mientras no se versionen rutas nuevas, este archivo es la referencia de compatibilidad para Flutter.

## Convenciones generales

- Base URL local: `http://localhost:8080`
- Content-Type de request y response: `application/json`
- Auth protegida con header `Authorization: Bearer <token>`
- Operaciones criticas aceptan `Idempotency-Key: <clave-estable>` en header.
- Todas las respuestas de error usan el mismo esquema:

```json
{
  "error": {
    "code": "CODIGO_ESTABLE",
    "message": "Mensaje legible",
    "details": {
      "campoOpcional": "valor"
    }
  },
  "traceId": "9f3b6f54d1a247ee"
}
```

- `error.details` y `traceId` son opcionales, pero `traceId` se devuelve en errores generados por la API actual.
- Recomendacion de cliente:
  - usar `Idempotency-Key` en `POST /v1/matches/{matchId}/turns`
  - usar `Idempotency-Key` en `POST /v1/store/purchase`
  - usar `Idempotency-Key` en `POST /v1/missions/claim`
- Estados canonicos:
  - Ticket matchmaking: `queued`, `matched`, `cancelled`, `expired`
  - Match: `active`, `completed`
  - Settlement: idempotente por pareja `matchId + playerId`
- Breaking change: renombrar campos, cambiar tipos, borrar valores permitidos o cambiar semantica.
- Non-breaking change: anadir campos opcionales nuevos o nuevos codigos de error no usados por flujos existentes.

## Salud

### `GET /health`

Response `200`:

```json
{
  "service": "true-duel-api",
  "status": "ok",
  "db": "up",
  "timestamp": "2026-05-06T10:15:00Z"
}
```

Response `503`: mismo payload con `status=degraded` y `db=down`.

## Auth

### `POST /v1/auth/register`

Request:

```json
{
  "username": "duelist01",
  "email": "duelist01@local",
  "password": "123456",
  "displayName": "Duelist 01"
}
```

Response `201`:

```json
{
  "token": "jwt",
  "user": {
    "playerId": "uuid",
    "name": "Duelist 01",
    "username": "duelist01"
  }
}
```

Errores: `INVALID_REGISTER`, `WEAK_PASSWORD`, `USER_ALREADY_EXISTS`, `REGISTER_FAILED`.

### `POST /v1/auth/login`

Request:

```json
{
  "username": "duelist01",
  "password": "123456"
}
```

Response `200`: mismo esquema de `register`.

Errores: `INVALID_LOGIN`, `INVALID_CREDENTIALS`.

## Perfil y ranking

### `GET /v1/home/resumen`

Response `200`:

```json
{
  "profile": {
    "playerId": "uuid",
    "name": "Duelist 01",
    "rank": "Bronce I",
    "tituloCompetitivo": "Combatiente",
    "coins": 1200,
    "gems": 40,
    "level": 3,
    "experienceTotal": 250,
    "experienceToNextLevel": 150
  },
  "misionesDiarias": {
    "date": "2026-05-06",
    "summary": {
      "completed": 1,
      "total": 3
    },
    "missions": []
  },
  "ultimasPartidas": [],
  "competitivo": {
    "sp": 120,
    "titulo": "Aspirante",
    "posicion": 42
  },
  "actividad": {
    "ticket": null,
    "match": null
  }
}
```

Notas:
- `actividad.ticket` devuelve el ticket activo si existe.
- `actividad.match` devuelve partida activa si existe.
- Este endpoint esta pensado como carga principal de Home mobile.

### `GET /v1/profile`

Response `200`:

```json
{
  "playerId": "uuid",
  "name": "Duelist 01",
  "rank": "Bronce I",
  "mmrGlobal": 1000,
  "puntosHabilidad": 120,
  "tituloCompetitivo": "Aspirante",
  "posicionCompetitiva": 42,
  "level": 3,
  "experienceTotal": 250,
  "experienceToNextLevel": 150,
  "coins": 1200,
  "gems": 40,
  "stats": {
    "matches": 12,
    "wins": 7,
    "losses": 5
  },
  "mmrByChampion": {},
  "freshnessSeconds": 30,
  "isFresh": true
}
```

Errores: `UNAUTHORIZED`, `INVALID_TOKEN`, `PROFILE_NOT_FOUND`.

### `GET /v1/profile/{playerId}`

Response `200`: mismo payload que `GET /v1/profile`.

### `GET /v1/ranking`

Response `200`:

```json
{
  "ranking": [
    {
      "playerId": "uuid",
      "name": "Duelist 01",
      "mmr": 1000,
      "sp": 120,
      "titulo": "Aspirante",
      "posicion": 42,
      "level": 3
    }
  ]
}
```

### `GET /v1/users`

Response `200`:

```json
{
  "users": [
    {
      "playerId": "uuid",
      "name": "Duelist 01",
      "rank": "Bronce I",
      "mmr": 1000,
      "sp": 120,
      "titulo": "Aspirante",
      "posicion": 42,
      "level": 3,
      "region": "eu-west"
    }
  ]
}
```

### `GET /v1/history`

Response `200`:

```json
{
  "matches": [
    {
      "matchId": "uuid",
      "result": "win",
      "enemy": "SparringBot",
      "turns": 6,
      "mmrDelta": 24
    }
  ]
}
```

## Personajes y equipo

### `GET /v1/personajes`

Response `200`:

```json
{
  "personajes": [
    {
      "id": "vanguard",
      "rolSinergia": "iniciador",
      "nombre": "Vanguard",
      "descripcion": "Abre ventanas tacticas aplicando presion inicial.",
      "habilidadEspecialNombre": "Golpe balistico",
      "habilidadEspecialDescripcion": "Dano medio y aplica Expuesto durante 1 turno.",
      "tipoEspecial": "apertura",
      "pasiva": {
        "id": "apertura_de_choque",
        "nombre": "Apertura de choque",
        "descripcion": "El primer golpe sobre un objetivo limpio abre Expuesto y gana dano extra."
      },
      "tags": ["starter", "mark", "pressure"],
      "efectoEspecial": {
        "tipo": "aplicar_estado",
        "tipoEspecial": "apertura",
        "estado": "expuesto",
        "duracion_turnos": 1
      },
      "desbloqueado": true,
      "nivelMaestria": 1,
      "xpMaestria": 0,
      "costeCargas": 2,
      "desbloqueadoInicial": true,
      "precioMonedas": 0,
      "orden": 1
    }
  ]
}
```

Notas:
- El catalogo de personajes ya incluye metadata jugable suficiente para renderizar fichas y tooltips en mobile.
- `pasiva`, `tags` y `tipoEspecial` son campos additive y estables a nivel de contrato.

### `GET /v1/personajes/mios`

Response `200`: mismo payload que `GET /v1/personajes`.

### `POST /v1/personajes/desbloquear`

Request:

```json
{
  "personajeId": "control"
}
```

Response `200`:

```json
{
  "personajeId": "control",
  "desbloqueado": true
}
```

Errores: `PERSONAJE_INVALIDO`.

### `GET /v1/equipo`

Response `200`:

```json
{
  "equipo": [
    {
      "slot": 1,
      "personajeId": "vanguard",
      "personaje": {
        "id": "vanguard"
      }
    }
  ]
}
```

### `PUT /v1/equipo`
### `POST /v1/equipo`

Request:

```json
{
  "personajes": ["vanguard", "bulwark", "riftblade"]
}
```

Response `200`: mismo payload que `GET /v1/equipo`.

Errores: `EQUIPO_INCOMPLETO`, `EQUIPO_DUPLICADO`, `PERSONAJE_INVALIDO`, `PERSONAJE_BLOQUEADO`.

## Bonificadores

### `GET /v1/bonificadores-partida`

Response `200`:

```json
{
  "bonificadores": []
}
```

## Tienda

### `GET /v1/store/catalog`

Response `200`:

```json
{
  "wallet": {
    "coins": 1200,
    "gems": 40
  },
  "items": [
    {
      "id": "avatar_legendario",
      "type": "avatar",
      "priceCoins": 500,
      "owned": false,
      "quantity": 0,
      "equipped": false
    }
  ]
}
```

### `GET /v1/store/inventory`

Response `200`:

```json
{
  "items": []
}
```

### `POST /v1/store/purchase`

Request:

```json
{
  "itemId": "avatar_legendario"
}
```

Header opcional recomendado:

```text
Idempotency-Key: purchase-avatar-legendario-001
```

Response `200`:

```json
{
  "itemId": "avatar_legendario",
  "coins": 700
}
```

Errores: `INVALID_ITEM`, `INSUFFICIENT_COINS`, `PURCHASE_FAILED`.

Si se reintenta con la misma `Idempotency-Key` y el mismo payload, la API devuelve la misma respuesta `200` sin duplicar el cobro.

### `POST /v1/store/equip`

Request:

```json
{
  "itemId": "avatar_legendario"
}
```

Response `200`:

```json
{
  "itemId": "avatar_legendario",
  "equipped": true
}
```

Errores: `INVALID_ITEM`, `ITEM_NOT_OWNED`.

## Misiones

### `GET /v1/missions/daily`

Response `200`:

```json
{
  "date": "2026-05-06",
  "summary": {
    "completed": 1,
    "total": 3
  },
  "missions": []
}
```

### `POST /v1/missions/claim`

Request:

```json
{
  "missionId": "win_3_matches"
}
```

Header opcional recomendado:

```text
Idempotency-Key: mission-win-3-matches-001
```

Response `200`:

```json
{
  "missionId": "win_3_matches",
  "claimed": true,
  "rewards": {
    "xp": 120,
    "coins": 200
  },
  "wallet": {
    "coins": 1400,
    "gems": 40
  },
  "profile": {
    "level": 3,
    "experienceTotal": 250,
    "experienceToNextLevel": 150
  }
}
```

Errores: `INVALID_MISSION`, `MISSION_NOT_FOUND`, `MISSION_ALREADY_CLAIMED`, `MISSION_NOT_COMPLETED`, `MISSION_CLAIM_FAILED`.

Si se reintenta con la misma `Idempotency-Key` y el mismo payload, la API devuelve la misma respuesta `200` sin reaplicar recompensas.

## Matchmaking y combate

### `POST /v1/matchmaking/enqueue`

Request:

```json
{
  "mode": "ranked_pvp",
  "region": "eu-west"
}
```

Valores validos de `mode`:
- `normal_bot`
- `ranked_pvp`
- `ranked_bot`

Compatibilidad legacy aceptada:
- `queue`: `normal` o `ranked`
- `vsBot`: `true` o `false`

Response `200` o `202`:

```json
{
  "ticketId": "uuid",
  "status": "queued",
  "queue": "ranked_pvp",
  "matchId": null,
  "matchStatus": null,
  "etaSec": 20,
  "expiresInSec": 90,
  "canCancel": true,
  "region": "eu-west"
}
```

Errores: `EQUIPO_INCOMPLETO`, `MATCH_ALREADY_ACTIVE`.

### `GET /v1/matchmaking/tickets/{ticketId}`

Response `200`: mismo payload que `enqueue`.

Errores: `TICKET_NOT_FOUND`, `FORBIDDEN`.

### `POST /v1/matchmaking/tickets/{ticketId}/cancel`

Response `200`:

```json
{
  "ticketId": "uuid",
  "status": "cancelled",
  "queue": "ranked_pvp",
  "matchId": null,
  "matchStatus": null,
  "etaSec": 0,
  "expiresInSec": 0,
  "canCancel": false,
  "region": "eu-west"
}
```

Notas:
- La cancelacion es idempotente.
- Solo se puede cancelar un ticket en `queued`.
- Si el ticket ya esta `matched`, la API responde `409 TICKET_CANNOT_CANCEL` con el ticket actual en `error.details.ticket`.

### `GET /v1/matches/{matchId}`

Response `200`:

```json
{
  "matchId": "uuid",
  "queue": "ranked",
  "status": "active",
  "p1Id": "uuid-a",
  "p2Id": "uuid-b",
  "botName": null,
  "state": {
    "serverStateVersion": 1,
    "turnNo": 0,
    "winner": null,
    "playerPassive": {
      "id": "apertura_de_choque",
      "nombre": "Apertura de choque",
      "descripcion": "El primer golpe sobre un objetivo limpio abre Expuesto y gana dano extra."
    },
    "enemyPassive": {
      "id": "reserva_bastion",
      "nombre": "Reserva bastion",
      "descripcion": "Defender sin cargas genera una capa extra de fortificacion."
    },
    "playerActiveEffects": [
      {
        "id": "bloqueo_rng",
        "nombre": "Bloqueo RNG",
        "turns": 1,
        "value": 1
      }
    ],
    "enemyActiveEffects": []
  },
  "recentEvents": [],
  "lastRivalAction": ""
}
```

Errores: `MATCH_NOT_FOUND`, `FORBIDDEN`.

Notas:
- En partidas PvP, los equivalentes son `p1Passive`, `p2Passive`, `p1ActiveEffects` y `p2ActiveEffects`.
- Los efectos activos exponen solo la vista cliente del estado persistente: `id`, `nombre`, `turns` y `value` cuando aplica.

### `POST /v1/matches/{matchId}/turns`

Request:

```json
{
  "action": "attack",
  "clientStateVersion": 1
}
```

Header opcional recomendado:

```text
Idempotency-Key: match-turn-001
```

Valores validos de `action`:
- `attack`
- `defend`
- `special`

Response `200`:

```json
{
  "turnNo": 1,
  "result": "ok",
  "serverStateVersion": 2,
  "snapshot": {
    "serverStateVersion": 2,
    "winner": null,
    "playerActiveEffects": [
      {
        "id": "fortificado",
        "nombre": "Fortificado",
        "turns": 1,
        "value": 1
      }
    ],
    "enemyActiveEffects": [
      {
        "id": "hemorragia",
        "nombre": "Hemorragia",
        "turns": 2,
        "value": 3
      }
    ]
  },
  "nextPlayerId": "uuid-b"
}
```

Para partidas bot, la respuesta puede devolver `botAction` en lugar de `nextPlayerId`.

Errores:
- `MATCH_NOT_FOUND`
- `FORBIDDEN`
- `MATCH_FINISHED`
- `STATE_VERSION_CONFLICT`
- `INVALID_ACTION`
- `PVP_MATCH_INVALID`
- `NOT_YOUR_TURN`

En errores de concurrencia o version, `error.details` puede incluir:

```json
{
  "authoritativeState": {
    "serverStateVersion": 3
  }
}
```

Notas:
- Si se reintenta el mismo turno con la misma `Idempotency-Key`, la API devuelve la misma respuesta `200`.
- Si no se envia `Idempotency-Key`, el backend intenta reidentificar automaticamente reintentos equivalentes por `clientStateVersion + action + actor`.
- `snapshot` replica el estado autoritativo completo del combate y ahora incluye pasivas visibles y efectos persistentes activos por lado.
- `recentEvents` del match se actualiza con el post-turno y puede incluir `playerActiveEffects`/`enemyActiveEffects` en bot o `actorActiveEffects`/`targetActiveEffects` en PvP.

### `POST /v1/matches/{matchId}/complete`

Response `200`:

```json
{
  "winner": "uuid-a",
  "mmr": {
    "globalDelta": 24,
    "championDelta": 18
  },
  "rewards": {
    "coins": 120,
    "gems": 0,
    "xp": 80,
    "masteryXp": 20
  },
  "result": "win",
  "mmrDelta": 24,
  "xp": 80,
  "coins": 120,
  "gems": 0,
  "competitivo": {},
  "damageDealt": 55,
  "damageTaken": 31,
  "turns": 6,
  "attackCount": 4,
  "defendCount": 1,
  "specialCount": 1,
  "mitigationTotal": 12
}
```

Notas:
- La operacion es idempotente por jugador.
- Reintentos devuelven el mismo settlement ya persistido.

Errores:
- `MATCH_NOT_FOUND`
- `FORBIDDEN`
- `MATCH_NOT_FINISHED`
- `OUTCOME_RULE_MISSING`
- `SETTLEMENT_FAILED`

### `POST /v1/matches/{matchId}/abandon`

Solo permitido para `ranked` PvP activa.

Response `200`:

```json
{
  "matchId": "uuid",
  "abandoned": true,
  "winner": "p2",
  "competitivo": {
    "deltaSp": -30,
    "spActual": 970,
    "tituloCompetitivo": "Combatiente",
    "abandoned": true
  }
}
```

Errores:
- `MATCH_NOT_FOUND`
- `FORBIDDEN`
- `ABANDON_NOT_ALLOWED`
- `MATCH_FINISHED`

## Administracion

Todos los endpoints admin requieren:

```text
X-Admin-Key: <admin-key>
```

### `POST /v1/admin/competitivo/recalcular`

Ejecuta el job competitivo:
- decay por inactividad,
- recalculo de titulos,
- validacion de actividad minima para mantener titulos.

Response `200`:

```json
{
  "decayedProfiles": 12,
  "decayDays": 14,
  "decaySpPenalty": 20,
  "titleActivityWindowDays": 7,
  "titleActivityMinMatches": 3
}
```

### `GET /v1/admin/catalogos/{catalogo}`

Catalogos soportados:
- `personajes`
- `bonificadores`
- `store`
- `misiones`
- `outcome-rules`
- `titulos`
- `telemetria-personajes`

Response `200`:

```json
{
  "items": [
    {
      "championId": "vanguard",
      "matchesPlayed": 12,
      "wins": 7,
      "losses": 4,
      "draws": 1,
      "winRate": 58.33,
      "actions": {
        "attack": 25,
        "defend": 8,
        "special": 10
      },
      "effects": {
        "expuesto": 6,
        "fortificado": 2,
        "hemorragia": 0,
        "sobrecarga": 1,
        "silencioTactico": 0,
        "escudo": 0
      }
    }
  ]
}
```

Notas:
- `telemetria-personajes` es de solo lectura y agrega uso real por campeon a partir de las partidas jugadas.
- El tracking actual cubre winrate, acciones base y aplicacion de estados persistentes del sistema F1.

### `PUT /v1/admin/catalogos/{catalogo}`
### `POST /v1/admin/catalogos/{catalogo}`

Request:

```json
{
  "items": []
}
```

Reemplaza el catalogo completo en BD.

## Reglas competitivas P1

- Job competitivo periodico disponible por script y endpoint admin.
- Decay por inactividad: `14` dias sin actividad ranked aplica `-20 SP`.
- Actividad minima para conservar titulos: `3` partidas ranked en ventana de `7` dias.
- Anti-farm: repetir rival en ranked dentro de `3` dias penaliza delta SP a partir de la tercera repeticion.
- Abandono ranked PvP: `-30 SP` y registro en log competitivo.

## Errores transversales

Estos codigos pueden aparecer en multiples endpoints protegidos:

- `UNAUTHORIZED`
- `INVALID_TOKEN`
- `FORBIDDEN`
- `NOT_FOUND`
- `DB_UNAVAILABLE`
- `INTERNAL_ERROR`
- `INVALID_RESPONSE`
- `ADMIN_UNAUTHORIZED`
