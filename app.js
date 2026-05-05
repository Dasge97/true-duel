const state = {
  screen: "home",
  champion: null,
  enemyChampion: null,
  modifierPool: [
    { id: "m1", name: "Ritmo Acelerado", desc: "20% de doble turno." },
    { id: "m2", name: "Alta Volatilidad", desc: "Mas criticos y mas fallos." },
    { id: "m3", name: "Defensa Reforzada", desc: "Todo el dano reducido." },
  ],
  proposed: [],
  selectedModifier: null,
  hp: { player: 1000, enemy: 1000 },
  turns: 0,
  damageDealt: 0,
  log: ["El duelo comenzara pronto..."],
  winner: null,
};

const champions = [
  { id: "assassin", name: "Asesino", icon: "🗡️", desc: "Burst rapido y cierre agresivo." },
  { id: "bruiser", name: "Bruiser", icon: "🛡️", desc: "Presion constante y estable." },
  { id: "control", name: "Control", icon: "🧠", desc: "Niega jugadas y domina ritmo." },
  { id: "sustain", name: "Sustain", icon: "💚", desc: "Aguante y recuperacion." },
];

const phaseLabel = document.getElementById("phaseLabel");
const roots = {
  home: document.getElementById("screen-home"),
  champions: document.getElementById("screen-champions"),
  modifiers: document.getElementById("screen-modifiers"),
  match: document.getElementById("screen-match"),
  result: document.getElementById("screen-result"),
};

function go(screen) {
  state.screen = screen;
  Object.entries(roots).forEach(([key, el]) => {
    el.classList.toggle("active", key === screen);
  });
  phaseLabel.textContent =
    screen === "home"
      ? "Home"
      : screen === "champions"
        ? "Champion Select"
        : screen === "modifiers"
          ? "Modifier Phase"
          : screen === "match"
            ? "Match"
            : "Result";
  render();
}

function randomEnemyChampion() {
  const pool = champions.filter((c) => c.id !== state.champion?.id);
  return pool[Math.floor(Math.random() * pool.length)];
}

function resetMatchData() {
  state.hp.player = 1000;
  state.hp.enemy = 1000;
  state.turns = 0;
  state.damageDealt = 0;
  state.log = ["Comienza el duelo."];
  state.winner = null;
}

function renderHome() {
  roots.home.innerHTML = `
    <div class="card home">
      <div class="hero">
        <h2>1v1 Turn-Based Duel</h2>
        <p>Simple to learn, difficult to master.</p>
      </div>
      <div class="btn-col">
        <button class="btn-primary" id="btnPlay">Play</button>
        <button class="btn-secondary">Profile</button>
        <button class="btn-secondary">History</button>
      </div>
    </div>
  `;
  roots.home.querySelector("#btnPlay").onclick = () => go("champions");
}

function renderChampionSelect() {
  roots.champions.innerHTML = `
    <h3 class="section-title">Choose your champion</h3>
    <div class="list-grid">
      ${champions
        .map(
          (c) => `
        <button class="card champion-card ${state.champion?.id === c.id ? "selected" : ""}" data-id="${c.id}">
          <div class="champion-head">
            <div class="avatar">${c.icon}</div>
            <div>
              <strong>${c.name}</strong>
              <p class="muted">${c.desc}</p>
            </div>
          </div>
        </button>`
        )
        .join("")}
    </div>
    <div style="margin-top:12px;display:grid;gap:8px;">
      <button class="btn-primary" id="toModifiers" ${state.champion ? "" : "disabled"}>Continue</button>
      <button class="btn-secondary" id="backHome">Back</button>
    </div>
  `;

  roots.champions.querySelectorAll(".champion-card").forEach((btn) => {
    btn.onclick = () => {
      state.champion = champions.find((c) => c.id === btn.dataset.id);
      renderChampionSelect();
    };
  });
  roots.champions.querySelector("#toModifiers").onclick = () => go("modifiers");
  roots.champions.querySelector("#backHome").onclick = () => go("home");
}

function renderModifiers() {
  const stepA = state.proposed.length < 2;
  roots.modifiers.innerHTML = `
    <h3 class="section-title">Modifier phase</h3>
    <p class="step-title">${stepA ? "Step A · Player A picks 2" : "Step B · Player B picks 1"}</p>
    <div class="list-grid">
      ${state.modifierPool
        .map(
          (m) => `
        <button class="card modifier-card ${state.proposed.some((p) => p.id === m.id) || state.selectedModifier?.id === m.id ? "selected" : ""}" data-mid="${m.id}">
          <strong>${m.name}</strong>
          <p class="muted">${m.desc}</p>
        </button>`
        )
        .join("")}
    </div>
    <div style="margin-top:12px;display:grid;gap:8px;">
      <button class="btn-primary" id="confirmMod" ${stepA ? (state.proposed.length === 2 ? "" : "disabled") : state.selectedModifier ? "" : "disabled"}>${stepA ? "Confirm 2 modifiers" : "Start match"}</button>
      <button class="btn-secondary" id="resetMods">Reset</button>
    </div>
  `;

  roots.modifiers.querySelectorAll(".modifier-card").forEach((btn) => {
    btn.onclick = () => {
      const picked = state.modifierPool.find((m) => m.id === btn.dataset.mid);
      if (!picked) return;
      if (stepA) {
        const exists = state.proposed.some((p) => p.id === picked.id);
        if (exists) {
          state.proposed = state.proposed.filter((p) => p.id !== picked.id);
        } else if (state.proposed.length < 2) {
          state.proposed.push(picked);
        }
      } else {
        state.selectedModifier = picked;
      }
      renderModifiers();
    };
  });

  roots.modifiers.querySelector("#confirmMod").onclick = () => {
    if (stepA) {
      state.modifierPool = [...state.proposed];
      renderModifiers();
      return;
    }
    state.enemyChampion = randomEnemyChampion();
    resetMatchData();
    go("match");
  };

  roots.modifiers.querySelector("#resetMods").onclick = () => {
    state.modifierPool = [
      { id: "m1", name: "Ritmo Acelerado", desc: "20% de doble turno." },
      { id: "m2", name: "Alta Volatilidad", desc: "Mas criticos y mas fallos." },
      { id: "m3", name: "Defensa Reforzada", desc: "Todo el dano reducido." },
    ];
    state.proposed = [];
    state.selectedModifier = null;
    renderModifiers();
  };
}

function statusBadges() {
  return `<span class="status-badge">${state.selectedModifier?.name || "No modifier"}</span>`;
}

function hpWidth(current) {
  return Math.max(0, Math.min(100, (current / 1000) * 100));
}

function performAction(action) {
  if (state.winner) return;
  state.turns += 1;
  const base = action === "Attack" ? 88 : action === "Skill 1" ? 110 : 145;
  const crit = Math.random() < 0.15;
  const enemyCrit = Math.random() < 0.12;
  const playerDmg = Math.max(40, base + Math.floor(Math.random() * 31) - 15) * (crit ? 1.5 : 1);
  const enemyDmg = Math.max(35, 85 + Math.floor(Math.random() * 31) - 15) * (enemyCrit ? 1.4 : 1);
  state.hp.enemy = Math.max(0, state.hp.enemy - Math.floor(playerDmg));
  state.hp.player = Math.max(0, state.hp.player - Math.floor(enemyDmg));
  state.damageDealt += Math.floor(playerDmg);
  state.log.unshift(
    `T${state.turns} · You used ${action} (${Math.floor(playerDmg)} dmg${crit ? ", CRIT" : ""}) · Enemy hit back (${Math.floor(enemyDmg)}${enemyCrit ? ", CRIT" : ""})`
  );
  if (state.log.length > 6) state.log.pop();

  if (state.hp.enemy <= 0 || state.hp.player <= 0 || state.turns >= 14) {
    state.winner = state.hp.enemy <= 0 ? "Victory" : state.hp.player <= 0 ? "Defeat" : state.hp.enemy < state.hp.player ? "Victory" : "Defeat";
    setTimeout(() => go("result"), 380);
  }
  renderMatch();
}

function renderMatch() {
  roots.match.innerHTML = `
    <div class="match-layout">
      <div class="card fighter">
        <div class="fighter-top"><strong>${state.enemyChampion?.name || "Enemy"}</strong><span>${state.hp.enemy}/1000</span></div>
        <div class="hp-wrap"><div class="hp-bar" style="width:${hpWidth(state.hp.enemy)}%"></div></div>
        <div class="status-row">${statusBadges()}</div>
      </div>

      <div class="card combat-log">
        ${state.log.map((line) => `<p>${line}</p>`).join("")}
      </div>

      <div class="card fighter">
        <div class="fighter-top"><strong>${state.champion?.name || "Player"}</strong><span>${state.hp.player}/1000</span></div>
        <div class="hp-wrap"><div class="hp-bar" style="width:${hpWidth(state.hp.player)}%"></div></div>
        <div class="status-row"><span class="status-badge">Turn ${state.turns + 1}</span></div>
      </div>

      <div class="actions">
        <button class="btn-action glow" data-action="Attack">Attack</button>
        <button class="btn-action" data-action="Skill 1">Skill 1</button>
        <button class="btn-action" data-action="Skill 2">Skill 2</button>
      </div>
    </div>
  `;

  roots.match.querySelectorAll("[data-action]").forEach((btn) => {
    btn.onclick = () => performAction(btn.dataset.action);
  });
}

function renderResult() {
  const victory = state.winner === "Victory";
  roots.result.innerHTML = `
    <div class="card result-box">
      <h2 style="color:${victory ? "var(--accent-2)" : "var(--danger)"};">${state.winner}</h2>
      <div class="stats">
        <div class="stat"><strong>Damage dealt</strong><br>${state.damageDealt}</div>
        <div class="stat"><strong>Turns played</strong><br>${state.turns}</div>
      </div>
      <div class="btn-col">
        <button class="btn-primary" id="again">Play again</button>
        <button class="btn-secondary" id="exit">Exit</button>
      </div>
    </div>
  `;

  roots.result.querySelector("#again").onclick = () => {
    state.proposed = [];
    state.selectedModifier = null;
    state.modifierPool = [
      { id: "m1", name: "Ritmo Acelerado", desc: "20% de doble turno." },
      { id: "m2", name: "Alta Volatilidad", desc: "Mas criticos y mas fallos." },
      { id: "m3", name: "Defensa Reforzada", desc: "Todo el dano reducido." },
    ];
    go("champions");
  };
  roots.result.querySelector("#exit").onclick = () => go("home");
}

function render() {
  if (state.screen === "home") renderHome();
  if (state.screen === "champions") renderChampionSelect();
  if (state.screen === "modifiers") renderModifiers();
  if (state.screen === "match") renderMatch();
  if (state.screen === "result") renderResult();
}

render();
