# True Duel - Diseño de Personajes, Bonificadores, Combate y Sinergias

## 1) Núcleo de combate

Sistema base por turno:

- **Ataque básico**
- **Defensa** (probabilidad de bloqueo; al bloquear genera carga)
- **Especial** (requiere **2 cargas**)

Principios:

- RNG controlado, no dominante.
- Decisiones simples con profundidad matemática.
- El poder real viene de la **composición de 3 campeones** y su sinergia.

---

## 2) Estructura de composición (3 campeones)

Roles de sinergia:

- **Starter**: abre ventana táctica (marca, debuff, control de ritmo)
- **Amplifier**: escala la ventana (economía de cargas, mitigación, tempo)
- **Finisher**: convierte ventana en ventaja/kill

Recomendación base de construcción:

- **1 Starter + 1 Amplifier + 1 Finisher**

---

## 3) Campeones (12)

Todos comparten:

- Ataque básico
- Defensa (bloqueo + carga)
- Especial (2 cargas)

### 3.1 Vanguard (Starter)

- **Especial**: Golpe balístico
- **Efecto**: daño medio + aplica `Expuesto` (1 turno)
- **Aporta**: abre ventana para finishers

### 3.2 Bulwark (Amplifier)

- **Especial**: Muralla cinética
- **Efecto**: mitigación global 1 turno + mejora ganancia de carga al bloquear
- **Aporta**: estabilidad y economía defensiva

### 3.3 Riftblade (Finisher)

- **Especial**: Corte de fase
- **Efecto**: daño alto; daño extra si objetivo está `Expuesto`
- **Aporta**: cierre explosivo

### 3.4 Hexa (Starter)

- **Especial**: Marca de entropy
- **Efecto**: reduce bloqueo rival + aumenta fallo de especial rival
- **Aporta**: disrupción y apertura anti-defensa

### 3.5 Oracle (Amplifier)

- **Especial**: Precognición
- **Efecto**: el próximo especial aliado cuesta 1 carga menos
- **Aporta**: aceleración de ciclo de especiales

### 3.6 Revenant (Finisher)

- **Especial**: Deuda de sangre
- **Efecto**: daño + cura proporcional al daño infligido
- **Aporta**: cierre con sustain

### 3.7 Warden (Amplifier)

- **Especial**: Interceptar
- **Efecto**: anula el próximo bonus ofensivo rival
- **Aporta**: control de picos de daño

### 3.8 Spark (Amplifier)

- **Especial**: Sobrecarga
- **Efecto**: turno extra con daño reducido
- **Aporta**: tempo y presión

### 3.9 Mender (Amplifier)

- **Especial**: Reforja
- **Efecto**: cura + limpia debuff relevante
- **Aporta**: consistencia y supervivencia

### 3.10 Grim (Finisher)

- **Especial**: Veredicto
- **Efecto**: daño escalado por cargas gastadas en el ciclo
- **Aporta**: finisher matemático, castiga metas de alto gasto

### 3.11 Tracer (Amplifier)

- **Especial**: Eco táctico
- **Efecto**: repite último efecto no-dañino aliado al 70%
- **Aporta**: multiplicación de utilidades de combo

### 3.12 Null (Starter)

- **Especial**: Zona muerta
- **Efecto**: 1 turno sin críticos ni turnos extra (ambos)
- **Aporta**: neutralización contextual

---

## 4) Bonificadores de partida (10 seleccionables)

Regla: alteran **las reglas del combate**, no stats permanentes del perfil.

### Baja volatilidad (4)

1. **Defensa reforzada**
   - Daño final global x0.85
2. **Fatiga suave**
   - Desde turno 4: +5% daño global acumulativo por turno
3. **Pulso estable**
   - -5 pp crítico global
4. **Cadencia táctica**
   - -50% relativo a probabilidad de repetición de acción

### Media volatilidad (4)

5. **Ritmo acelerado**
   - 20% de doble turno (máx 1 extra)
6. **Eco de acción moderado**
   - 18% de repetir habilidad al 70% de potencia
7. **Crítico inestable**
   - +10 pp crítico y +5 pp fallo de habilidades
8. **Escudo intermitente**
   - 25% de reducir 40% del próximo daño recibido ese turno

### Alta volatilidad (2)

9. **Alta volatilidad**
   - +15 pp crítico, +10 pp fallo, crítico x1.7
10. **Doble filo**
   - +20% daño de acciones; si falla habilidad, auto-daño 8% vida máxima

---

## 5) Sinergias clave

### 5.1 Tipos de sinergia

- **Sinergia de recurso**: alterar coste/ganancia de cargas
- **Sinergia de ventana**: abrir y escalar una ventana de ejecución
- **Sinergia de estado**: aplicar, mantener y consumir estados

### 5.2 Ejemplos de tríos

- **Vanguard + Oracle + Riftblade**
  - Apertura (`Expuesto`) + aceleración de especial + ejecución
- **Hexa + Tracer + Grim**
  - Disrupción + eco de utilidad + finisher escalado por ciclo
- **Null + Bulwark + Revenant**
  - Neutralizar RNG + estabilizar + cerrar con sustain

### 5.3 Reglas anti-degeneración

- Máximo 1 turno extra encadenado
- Crítico total capado
- Fallo de habilidad capado
- Sin buffs permanentes por perfil/cuenta

---

## 6) Métricas de equilibrio

Métricas objetivo para balance:

- Duración media de partida
- Turnos medios hasta primer especial
- Frecuencia de especiales por partida
- Valor esperado de defender (mitigación/carga)
- Winrate por composición y por bonificador

Objetivo competitivo:

- Muchas composiciones viables
- Solo un subconjunto pequeño top
- Tops igualados en techo (sin romper márgenes de fairness)
- El bonificador de partida desplaza ventajas de forma contextual
