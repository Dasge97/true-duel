// True Duel — Home, Ranking, Perfil

function TDStatPill({ label, value, sub }) {
  return (
    <div style={{ flex: 1, padding: '10px 12px', background: '#16161C', border: '1px solid var(--border)' }}>
      <div style={{ fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--muted)', letterSpacing: '0.18em', textTransform: 'uppercase' }}>{label}</div>
      <div className="td-num" style={{ fontSize: 18, color: 'var(--fg)', marginTop: 4, fontWeight: 600 }}>{value}</div>
      {sub && <div className="td-num" style={{ fontSize: 10, color: 'var(--muted)', marginTop: 2 }}>{sub}</div>}
    </div>);

}

function TDHome() {
  const missions = [
  { title: 'Gana 3 partidas', prog: 2, total: 3, reward: 150, claimed: false, ready: false },
  { title: 'Inflige 500 de daño', prog: 500, total: 500, reward: 100, claimed: false, ready: true },
  { title: 'Usa 5 especiales', prog: 5, total: 5, reward: 80, claimed: true, ready: false },
  { title: 'Defiende 8 veces', prog: 3, total: 8, reward: 60, claimed: false, ready: false }];

  const recent = [
  { result: 'W', enemy: 'kraken_77', turns: 9, delta: +18 },
  { result: 'L', enemy: 'BOT.iron', turns: 12, delta: -8 },
  { result: 'W', enemy: 'sylv_', turns: 7, delta: +21 },
  { result: 'W', enemy: 'mage9k', turns: 11, delta: +14 }];

  return (
    <TDPhone screenLabel="03 Home" nav="home">
      <div style={{ padding: '14px 14px 20px', display: 'flex', flexDirection: 'column', gap: 14 }}>

        {/* JUGAR module — hero */}
        <div className="td-card td-clip-corners elev" style={{
          padding: 0, position: 'relative', overflow: 'hidden',
          background: 'linear-gradient(135deg, #1F1A12 0%, #15151A 60%)',
          borderColor: 'rgba(229,181,103,0.3)'
        }}>
          {/* diagonal stripes */}
          <div style={{
            position: 'absolute', inset: 0,
            backgroundImage: 'repeating-linear-gradient(135deg, transparent 0 18px, rgba(229,181,103,0.04) 18px 19px)',
            pointerEvents: 'none'
          }} />
          <div style={{ padding: '18px 18px 18px', position: 'relative' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 14 }}>
              
              <button style={{
                background: 'transparent', border: 0, padding: 0, cursor: 'pointer',
                fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--gold)',
                letterSpacing: '0.14em', display: 'inline-flex', alignItems: 'center', gap: 4
              }}>
                VER MODOS DE JUEGO
                
              </button>
            </div>

            <div style={{ display: 'flex', gap: 8, marginBottom: 16 }}>
              <TDStatPill label="Racha" value="--" sub="sin partidas hoy" />
              <TDStatPill label="T. Promedio" value="--" sub="por turno" />
            </div>
            <button className="td-btn primary" style={{
              padding: '22px 18px',
              fontFamily: 'var(--f-display)', fontSize: 38, letterSpacing: '0.08em',
              display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 12, textAlign: "center", height: "3.3333px"
            }}>
              <span>JUGAR</span>
              
            </button>
          </div>
        </div>

        {/* Progress */}
        <div className="td-card">
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline', marginBottom: 8 }}>
            <div>
              <span className="td-eyebrow">NIVEL</span>
              <span className="td-num td-display" style={{ fontSize: 28, color: 'var(--gold)', marginLeft: 8 }}>{TD_PLAYER.level}</span>
            </div>
            <div className="td-num" style={{ fontSize: 11, color: 'var(--muted)' }}>
              <span style={{ color: 'var(--fg)' }}>{TD_PLAYER.xp.toLocaleString()}</span> / {TD_PLAYER.xpNext.toLocaleString()} XP
            </div>
          </div>
          <div className="td-bar thick">
            <div className="fill" style={{ width: `${TD_PLAYER.xp / TD_PLAYER.xpNext * 100}%` }} />
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 6, fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--muted)' }}>
            <span>XP TOTAL</span>
            <span>· 2.580 PARA NIVEL 15</span>
          </div>
        </div>

        {/* Ranking preview — entry to ranking */}
        <button onClick={() => {}} style={{
          appearance: 'none', textAlign: 'left', cursor: 'pointer',
          padding: '14px 16px', background: 'linear-gradient(135deg, #1B1A14 0%, #14141A 70%)',
          border: '1px solid rgba(229,181,103,0.28)', color: 'var(--fg)',
          display: 'flex', alignItems: 'center', gap: 14, position: 'relative', overflow: 'hidden'
        }}>
          <div style={{ position: 'absolute', inset: 0, backgroundImage: 'repeating-linear-gradient(135deg, transparent 0 16px, rgba(229,181,103,0.04) 16px 17px)', pointerEvents: 'none' }}/>
          <div style={{ position: 'relative', width: 48, height: 48, background: '#0E0E12', border: '1px solid var(--gold-deep)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
            <span style={{ fontFamily: 'var(--f-display)', fontSize: 22, color: 'var(--gold)', letterSpacing: '0.04em' }}>P·II</span>
          </div>
          <div style={{ flex: 1, minWidth: 0, position: 'relative' }}>
            <div className="td-eyebrow" style={{ color: 'var(--gold)' }}>RANKING · PLATA II</div>
            <div style={{ display: 'flex', alignItems: 'baseline', gap: 8, marginTop: 3 }}>
              <span className="td-num td-display" style={{ fontSize: 22, color: 'var(--fg)' }}>#4 821</span>
              <span style={{ fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--win)', letterSpacing: '0.1em' }}>↑ 38</span>
            </div>
            <div className="td-bar" style={{ marginTop: 6 }}><div className="fill" style={{ width: '72%' }}/></div>
            <div style={{ fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--muted)', marginTop: 4, letterSpacing: '0.1em' }}>72/100 LP · 28 PARA PLATA I</div>
          </div>
          <span style={{ fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--gold)', letterSpacing: '0.14em', position: 'relative' }}>VER</span>
          <svg width="9" height="9" viewBox="0 0 9 9" fill="none" stroke="var(--gold)" strokeWidth="1.6" strokeLinecap="round" style={{ position: 'relative' }}><path d="M2 1l4 3.5L2 8"/></svg>
        </button>

        {/* Misiones */}
        <div>
          <div className="td-section-title">
            <h3>MISIONES DIARIAS</h3>
            <span className="meta td-num">1 / 4</span>
          </div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
            {missions.map((m, i) =>
            <div key={i} className="td-card" style={{ padding: '12px 14px' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 6, gap: 12 }}>
                  <div style={{ fontSize: 13, color: m.claimed ? 'var(--muted)' : 'var(--fg)', textDecoration: m.claimed ? 'line-through' : 'none' }}>{m.title}</div>
                  <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                    <span className="td-num" style={{ fontSize: 11, color: 'var(--muted)' }}>{m.prog}/{m.total}</span>
                    <span style={{ display: 'flex', alignItems: 'center', gap: 4, fontFamily: 'var(--f-mono)', fontSize: 11, color: 'var(--gold)', fontWeight: 600 }}>
                      <span style={{ width: 8, height: 8, borderRadius: '50%', background: 'var(--gold)' }} />
                      {m.reward}
                    </span>
                  </div>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                  <div className="td-bar" style={{ flex: 1 }}>
                    <div className="fill" style={{ width: `${m.prog / m.total * 100}%`, background: m.claimed ? 'var(--muted-2)' : undefined }} />
                  </div>
                  {m.claimed ?
                <span style={{ fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--muted)', letterSpacing: '0.16em' }}>RECLAMADA</span> :
                m.ready ?
                <button className="td-btn sm" style={{ width: 'auto', background: 'var(--gold)', color: '#16110A', fontFamily: 'var(--f-display)' }}>RECLAMAR</button> :

                <span style={{ fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--muted-2)', letterSpacing: '0.16em' }}>EN CURSO</span>
                }
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Últimas partidas */}
        <div>
          <div className="td-section-title">
            <h3>ÚLTIMAS PARTIDAS</h3>
            <span className="meta">VER TODAS</span>
          </div>
          <div style={{ display: 'flex', flexDirection: 'column' }}>
            {recent.map((r, i) =>
            <div key={i} style={{
              display: 'flex', alignItems: 'center', gap: 12,
              padding: '12px 14px',
              background: i % 2 ? 'transparent' : '#13131A',
              borderTop: i === 0 ? '1px solid var(--border)' : 'none',
              borderBottom: '1px solid var(--border)'
            }}>
                <div style={{
                width: 28, height: 28,
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                fontFamily: 'var(--f-display)', fontSize: 18,
                color: r.result === 'W' ? 'var(--win)' : 'var(--loss)',
                background: r.result === 'W' ? 'rgba(123,184,147,0.08)' : 'rgba(199,110,110,0.08)',
                border: `1px solid ${r.result === 'W' ? 'rgba(123,184,147,0.3)' : 'rgba(199,110,110,0.3)'}`
              }}>{r.result}</div>
                <div style={{ flex: 1 }}>
                  <div style={{ fontSize: 13, color: 'var(--fg)' }}>{r.enemy}</div>
                  <div className="td-num" style={{ fontSize: 10, color: 'var(--muted)', letterSpacing: '0.06em' }}>{r.turns} TURNOS</div>
                </div>
                <div className="td-num" style={{
                fontSize: 14, fontWeight: 600,
                color: r.delta > 0 ? 'var(--win)' : 'var(--loss)'
              }}>{r.delta > 0 ? '+' : ''}{r.delta} MMR</div>
              </div>
            )}
          </div>
        </div>

      </div>
    </TDPhone>);

}

function TDRanking() {
  const top = [
  { pos: 1, name: 'krxn_void', mmr: 2891, lp: 'MAESTRO' },
  { pos: 2, name: 'sylvania.tv', mmr: 2784, lp: 'MAESTRO' },
  { pos: 3, name: 'NULL_protocol', mmr: 2702, lp: 'DIAMANTE I' },
  { pos: 4, name: 'fenix___', mmr: 2640, lp: 'DIAMANTE I' },
  { pos: 5, name: 'Mariana_GG', mmr: 2598, lp: 'DIAMANTE II' },
  { pos: 6, name: 'apex.duelist', mmr: 2541, lp: 'DIAMANTE II' },
  { pos: 7, name: 'kojimaaa', mmr: 2503, lp: 'DIAMANTE III' },
  { pos: 8, name: 'noir.q', mmr: 2487, lp: 'DIAMANTE III' },
  { pos: 9, name: 'helix__', mmr: 2451, lp: 'DIAMANTE III' },
  { pos: 10, name: 'astra.io', mmr: 2422, lp: 'DIAMANTE IV' },
  { pos: 11, name: 'glasshart', mmr: 2390, lp: 'DIAMANTE IV' }];

  return (
    <TDPhone screenLabel="04 Ranking" nav="rank">
      <div style={{ padding: '14px 14px 20px', display: 'flex', flexDirection: 'column', gap: 14 }}>

        {/* Tu ranking */}
        <div className="td-card td-clip-corners" style={{
          padding: 18,
          background: 'linear-gradient(135deg, #1B1A14 0%, #14141A 60%)',
          borderColor: 'rgba(229,181,103,0.3)'
        }}>
          <div className="td-eyebrow" style={{ color: 'var(--gold)' }}>TU RANKING</div>
          <div style={{ display: 'flex', alignItems: 'baseline', gap: 14, marginTop: 6 }}>
            <h2 className="td-display" style={{ fontSize: 38, margin: 0 }}>PLATA II</h2>
            <span className="td-num" style={{ fontSize: 13, color: 'var(--muted)' }}>· #4 821 GLOBAL</span>
          </div>
          <hr className="td-rule" style={{ margin: '12px 0' }} />
          <div style={{ display: 'flex', justifyContent: 'space-between', gap: 10 }}>
            <div>
              <div className="td-eyebrow">MMR</div>
              <div className="td-num td-display" style={{ fontSize: 26, color: 'var(--fg)' }}>1 248</div>
            </div>
            <div>
              <div className="td-eyebrow">LP</div>
              <div className="td-num td-display" style={{ fontSize: 26, color: 'var(--fg)' }}>72/100</div>
            </div>
            <div>
              <div className="td-eyebrow">RACHA</div>
              <div className="td-num td-display" style={{ fontSize: 26, color: 'var(--win)' }}>+3W</div>
            </div>
          </div>
          <div className="td-bar" style={{ marginTop: 12 }}>
            <div className="fill" style={{ width: '72%' }} />
          </div>
          <div style={{ fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--muted)', marginTop: 6, letterSpacing: '0.08em' }}>
            28 LP PARA PROMOCIÓN A PLATA I
          </div>
        </div>

        {/* Top global */}
        <div>
          <div className="td-section-title">
            <h3>TOP GLOBAL</h3>
            <span className="meta">SEMANA 14</span>
          </div>
          <div style={{ background: '#13131A', border: '1px solid var(--border)' }}>
            <div style={{ display: 'grid', gridTemplateColumns: '40px 1fr auto auto', padding: '8px 14px', fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--muted)', letterSpacing: '0.18em', borderBottom: '1px solid var(--border)' }}>
              <span>POS</span><span>JUGADOR</span><span style={{ marginRight: 14 }}>RANGO</span><span>MMR</span>
            </div>
            {top.map((p) =>
            <div key={p.pos} style={{
              display: 'grid', gridTemplateColumns: '40px 1fr auto auto',
              alignItems: 'center', gap: 10,
              padding: '11px 14px',
              borderBottom: '1px solid var(--border)',
              background: p.pos <= 3 ? 'rgba(229,181,103,0.04)' : 'transparent'
            }}>
                <span className="td-num td-display" style={{
                fontSize: 22,
                color: p.pos === 1 ? 'var(--gold)' : p.pos <= 3 ? 'var(--gold-2)' : 'var(--muted)'
              }}>{String(p.pos).padStart(2, '0')}</span>
                <div>
                  <div style={{ fontSize: 13, color: 'var(--fg)' }}>{p.name}</div>
                </div>
                <span style={{ fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--fg-2)', letterSpacing: '0.1em', marginRight: 14 }}>{p.lp}</span>
                <span className="td-num" style={{ fontSize: 13, color: 'var(--fg)', fontWeight: 600 }}>{p.mmr}</span>
              </div>
            )}
          </div>
        </div>

      </div>
    </TDPhone>);

}

const TD_PROFILE_ACHIEVEMENTS = [
  { name: 'Primera victoria',   unlocked: true,  initial: 'V', desc: 'Gana tu primera partida ranked.', date: '12 ABR' },
  { name: 'Racha de 5',         unlocked: true,  initial: '5', desc: 'Gana 5 partidas seguidas.',       date: '03 MAY' },
  { name: 'Sinergia total',     unlocked: true,  initial: 'S', desc: 'Gana con un equipo de 3 roles distintos.', date: '17 MAY' },
  { name: 'Élite global',       unlocked: false, initial: 'É', desc: 'Alcanza el top 1 000 mundial.',  progress: 0.18 },
  { name: '100 partidas',       unlocked: false, initial: 'C', desc: 'Juega 100 partidas competitivas.', progress: 0.84 },
  { name: 'Invicto',            unlocked: false, initial: '∞', desc: 'Gana 10 partidas seguidas sin perder ningún slot.', progress: 0.30 },
  { name: 'Coleccionista',      unlocked: false, initial: 'X', desc: 'Posee 12 campeones distintos.',  progress: 0.50 },
  { name: 'Maestría doble',     unlocked: false, initial: 'M', desc: 'Alcanza maestría 5 con 2 campeones.', progress: 0.40 },
];

const TD_PROFILE_TOP_CHAMPS = [
  { initial: 'V', name: 'Vanguard', rol: 'INIC', games: 38, wr: 0.66, slot: 'SLOT 1' },
  { initial: 'B', name: 'Bulwark',  rol: 'CENT', games: 29, wr: 0.62, slot: 'SLOT 2' },
  { initial: 'M', name: 'Mira',     rol: 'AMPL', games: 21, wr: 0.52, slot: 'SLOT 3' },
];

const TD_PROFILE_HISTORY = ['W','W','L','W','D','W','L','W','W','L','W','W'];

function TDProfile() {
  const achievements = TD_PROFILE_ACHIEVEMENTS;
  const unlockedCount = achievements.filter(a => a.unlocked).length;

  return (
    <TDPhone screenLabel="08 Perfil" nav="profile">
      <div style={{ padding: '14px 14px 20px', display: 'flex', flexDirection: 'column', gap: 14 }}>

        {/* Header card */}
        <div className="td-card td-clip-corners elev" style={{ padding: 18, position: 'relative', overflow: 'hidden' }}>
          <div style={{ position: 'absolute', inset: 0, background: 'radial-gradient(ellipse at top right, rgba(229,181,103,0.08), transparent 60%)', pointerEvents: 'none' }} />
          <div style={{ display: 'flex', gap: 16, position: 'relative' }}>
            <div className="td-glyph" style={{ width: 80, height: 80, flexShrink: 0 }}>
              <div className="stripe" />
              <span className="corner tl" /><span className="corner tr" /><span className="corner bl" /><span className="corner br" />
              <span style={{ fontSize: 44, color: 'var(--gold)' }}>V</span>
            </div>
            <div style={{ flex: 1, minWidth: 0 }}>
              <div className="td-eyebrow">DUELISTA · NV {TD_PLAYER.level}</div>
              <div className="td-display" style={{ fontSize: 28, marginTop: 4 }}>VALEN.DX</div>
              <div style={{ fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--gold)', letterSpacing: '0.14em', marginTop: 4 }}>EL CENTINELA DE PLATA</div>
              <div style={{ display: 'flex', gap: 14, marginTop: 8, fontFamily: 'var(--f-mono)', fontSize: 11, color: 'var(--fg-2)' }}>
                <span><span style={{ color: 'var(--muted)' }}>SP</span> <span className="td-num" style={{ color: 'var(--fg)', fontWeight: 600 }}>2 480</span></span>
                <span><span style={{ color: 'var(--muted)' }}>POS</span> <span className="td-num" style={{ color: 'var(--fg)', fontWeight: 600 }}>#4 821</span></span>
              </div>
            </div>
          </div>
        </div>

        {/* Stats */}
        <div>
          <div className="td-section-title" style={{ alignItems: 'center' }}>
            <h3>ESTADÍSTICAS</h3>
            <div style={{ display: 'flex', gap: 6 }}>
              <button style={{
                display: 'inline-flex', alignItems: 'center', gap: 6,
                background: 'transparent', color: 'var(--gold)', border: '1px solid var(--gold-deep)',
                padding: '6px 10px',
                fontFamily: 'var(--f-display)', fontSize: 13, letterSpacing: '0.14em', cursor: 'pointer',
              }}>
                <svg width="10" height="10" viewBox="0 0 10 10" fill="none" stroke="currentColor" strokeWidth="1.6"><path d="M2 2h6v6H2zM2 5h6M5 2v6"/></svg>
                LOGROS
              </button>
              <button style={{
                display: 'inline-flex', alignItems: 'center', gap: 6,
                background: 'var(--gold)', color: '#16110A', border: 0,
                padding: '6px 12px',
                fontFamily: 'var(--f-display)', fontSize: 14,
                letterSpacing: '0.14em', cursor: 'pointer',
                boxShadow: '0 0 0 1px var(--gold-deep) inset'
              }}>
                <svg width="11" height="11" viewBox="0 0 11 11" fill="currentColor"><path d="M5.5 0l1.4 3.6L10.5 4 7.7 6.4l1 4.1L5.5 8.5 2.3 10.5l1-4.1L.5 4l3.6-.4z" /></svg>
                TÍTULOS
              </button>
            </div>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 6 }}>
            <TDStatPill label="Partidas" value="142" />
            <TDStatPill label="Victorias" value="84" />
            <TDStatPill label="Derrotas" value="58" />
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: 6, marginTop: 6 }}>
            <div style={{ padding: '12px', background: '#16161C', border: '1px solid var(--border)', display: 'flex', alignItems: 'center', gap: 12 }}>
              {/* circular winrate */}
              <svg width="44" height="44" viewBox="0 0 44 44">
                <circle cx="22" cy="22" r="18" fill="none" stroke="rgba(255,255,255,0.07)" strokeWidth="3" />
                <circle cx="22" cy="22" r="18" fill="none" stroke="var(--gold)" strokeWidth="3"
                strokeDasharray={`${0.59 * 113} 113`} strokeLinecap="butt" transform="rotate(-90 22 22)" />
              </svg>
              <div>
                <div className="td-eyebrow">WIN</div>
                <div className="td-num td-display" style={{ fontSize: 22, color: 'var(--fg)' }}>59%</div>
              </div>
            </div>
            <TDStatPill label="Racha actual" value="3" sub="placeholder" />
            <TDStatPill label="Récord" value="7" sub="placeholder" />
          </div>
        </div>

        {/* Progreso */}
        <div className="td-card">
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline', marginBottom: 8 }}>
            <div className="td-eyebrow">PROGRESO · NIVEL {TD_PLAYER.level}</div>
            <div className="td-num" style={{ fontSize: 11, color: 'var(--muted)' }}>{TD_PLAYER.xp}/{TD_PLAYER.xpNext} XP</div>
          </div>
          <div className="td-bar thick"><div className="fill" style={{ width: `${TD_PLAYER.xp / TD_PLAYER.xpNext * 100}%` }} /></div>
          <div style={{ fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--muted)', marginTop: 6 }}>2 580 XP PARA NIVEL 15</div>
        </div>

        {/* Medallas expuestas */}
        <div style={{
          padding: '12px 14px 14px',
          border: '1px solid rgba(229,181,103,0.35)',
          background: 'linear-gradient(135deg, rgba(229,181,103,0.04) 0%, transparent 70%)',
          position: 'relative'
        }}>
          <div className="td-section-title" style={{ marginBottom: 10 }}>
            <h3>MEDALLAS</h3>
            <span className="meta" style={{ color: 'var(--gold)' }}>EXPUESTAS · 5 / 5</span>
          </div>
          <div style={{ display: 'flex', gap: 8, justifyContent: 'space-between' }}>
            {achievements.filter(a => a.unlocked).slice(0, 3).concat([
              { initial: '7', name: '7 victorias seguidas', desc: 'Mejor racha personal.', date: '21 MAY', unlocked: true },
              { initial: 'A', name: 'Apertura magistral',    desc: 'Gana 10 partidas en SLOT 1.', date: '02 MAY', unlocked: true },
            ]).slice(0, 5).map((a, i) => (
              <div key={i} style={{ flex: 1, position: 'relative', display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
                <div style={{
                  width: 46, height: 46, borderRadius: '50%',
                  background: 'radial-gradient(circle at 30% 25%, #F0CB80, #B98A3E 70%, #6E4F1E 100%)',
                  border: '1px solid var(--gold-deep)',
                  boxShadow: '0 0 0 2px #0E0E12, inset 0 0 0 2px rgba(255,255,255,0.1)',
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                  fontFamily: 'var(--f-display)', fontSize: 20, color: '#16110A',
                  letterSpacing: '0.02em',
                }}>{a.initial}</div>
                {i === 1 && (
                  <div style={{
                    position: 'absolute', top: 56, left: '50%', transform: 'translateX(-50%)',
                    minWidth: 168, padding: '8px 10px', zIndex: 5,
                    background: '#16161C', border: '1px solid var(--gold-deep)',
                    boxShadow: '0 8px 24px rgba(0,0,0,0.5)',
                  }}>
                    <div style={{
                      position: 'absolute', top: -5, left: '50%', transform: 'translateX(-50%) rotate(45deg)',
                      width: 8, height: 8, background: '#16161C', borderTop: '1px solid var(--gold-deep)', borderLeft: '1px solid var(--gold-deep)'
                    }}/>
                    <div className="td-eyebrow" style={{ color: 'var(--gold)' }}>{a.date}</div>
                    <div style={{ fontFamily: 'var(--f-display)', fontSize: 13, color: 'var(--fg)', letterSpacing: '0.04em', marginTop: 1 }}>{a.name.toUpperCase()}</div>
                    <div style={{ fontSize: 10, color: 'var(--muted)', marginTop: 2, lineHeight: 1.4 }}>{a.desc}</div>
                  </div>
                )}
              </div>
            ))}
          </div>
          <div style={{ fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--muted)', letterSpacing: '0.12em', marginTop: 8, textAlign: 'center' }}>
            TOCA UNA MEDALLA PARA VER DETALLE · MANTÉN PARA REORDENAR
          </div>
        </div>

        {/* Personajes más usados — 3 cards en una línea */}
        <div>
          <div className="td-section-title">
            <h3>PERSONAJES MÁS USADOS</h3>
            <span className="meta">ÚLT. 30 DÍAS</span>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 6 }}>
            {TD_PROFILE_TOP_CHAMPS.map((c, i) => (
              <div key={c.initial} style={{
                padding: '10px 8px 12px',
                background: '#16161C', border: '1px solid var(--border)',
                display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 6,
                position: 'relative',
              }}>
                <span style={{ position: 'absolute', top: 6, left: 8, fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--gold)', letterSpacing: '0.14em' }}>0{i+1}</span>
                <TDChampionGlyph initial={c.initial} size={48}/>
                <div style={{ fontFamily: 'var(--f-display)', fontSize: 13, color: 'var(--fg)', letterSpacing: '0.04em', textAlign: 'center', lineHeight: 1 }}>{c.name.toUpperCase()}</div>
                <div style={{ fontFamily: 'var(--f-mono)', fontSize: 8, color: 'var(--muted)', letterSpacing: '0.12em' }}>{c.rol} · {c.games} P.</div>
                <div className="td-bar" style={{ width: '100%', marginTop: 2 }}><div className="fill" style={{ width: `${c.wr*100}%` }}/></div>
                <div className="td-num td-display" style={{ fontSize: 16, color: 'var(--gold)', lineHeight: 1 }}>{Math.round(c.wr*100)}%</div>
              </div>
            ))}
          </div>
        </div>

        {/* Últimas partidas + Roles */}
        <div className="td-card" style={{ padding: 14 }}>
          <div className="td-section-title" style={{ marginBottom: 8 }}>
            <h3 style={{ fontSize: 16 }}>ÚLTIMAS 12 PARTIDAS</h3>
            <span className="meta" style={{ color: 'var(--gold)' }}>+3 RACHA</span>
          </div>
          <div style={{ display: 'flex', gap: 4 }}>
            {TD_PROFILE_HISTORY.map((r, i) => {
              const c = r === 'W' ? 'var(--gold)' : r === 'L' ? '#3A2A2A' : 'var(--border-strong)';
              const fg = r === 'W' ? '#16110A' : r === 'L' ? '#7A5454' : 'var(--fg-2)';
              return (
                <div key={i} style={{
                  flex: 1, height: 26, background: c, color: fg,
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                  fontFamily: 'var(--f-display)', fontSize: 12, letterSpacing: '0.06em'
                }}>{r}</div>
              );
            })}
          </div>

          <hr className="td-rule" style={{ margin: '14px 0 12px' }}/>

          <div className="td-eyebrow" style={{ marginBottom: 8 }}>DISTRIBUCIÓN DE ROL</div>
          <div style={{ display: 'flex', height: 8, marginBottom: 8, border: '1px solid var(--border)' }}>
            <div style={{ width: '46%', background: 'var(--role-iniciador, #C97A4A)' }}/>
            <div style={{ width: '32%', background: 'var(--role-centinela, #5A8EC0)' }}/>
            <div style={{ width: '22%', background: 'var(--role-amplificador, #B07ACE)' }}/>
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', fontFamily: 'var(--f-mono)', fontSize: 10 }}>
            {[
              { c: 'var(--role-iniciador, #C97A4A)', l: 'INICIADOR', v: '46%' },
              { c: 'var(--role-centinela, #5A8EC0)', l: 'CENTINELA', v: '32%' },
              { c: 'var(--role-amplificador, #B07ACE)', l: 'AMPLIFICADOR', v: '22%' },
            ].map((r, i) => (
              <div key={i} style={{ display: 'flex', alignItems: 'center', gap: 5 }}>
                <span style={{ width: 6, height: 6, background: r.c }}/>
                <span style={{ color: 'var(--muted)', letterSpacing: '0.1em' }}>{r.l}</span>
                <span className="td-num" style={{ color: 'var(--fg)' }}>{r.v}</span>
              </div>
            ))}
          </div>
        </div>

        {/* Logros — discrete row removed (button moved to stats header) */}

      </div>
    </TDPhone>);

}

function TDProfileLogros() {
  const list = TD_PROFILE_ACHIEVEMENTS;
  const unlocked = list.filter(a => a.unlocked).length;
  return (
    <TDPhoneBare screenLabel="08c Perfil · logros · sheet">
      {/* Dimmed underlay (suggests overlay over Profile) */}
      <div style={{ position: 'absolute', inset: 0, background: '#0A0A0B' }}>
        <div style={{ padding: 14, opacity: 0.35, filter: 'blur(1px)' }}>
          <div style={{ height: 110, background: '#16161C', border: '1px solid var(--border)', marginBottom: 10 }}/>
          <div style={{ height: 80, background: '#16161C', border: '1px solid var(--border)', marginBottom: 10 }}/>
          <div style={{ height: 120, background: '#16161C', border: '1px solid var(--border)' }}/>
        </div>
        <div style={{ position: 'absolute', inset: 0, background: 'rgba(8,8,10,0.65)', backdropFilter: 'blur(2px)' }}/>
      </div>

      {/* Sheet */}
      <div style={{
        position: 'absolute', left: 0, right: 0, bottom: 0,
        height: '78%',
        background: '#101015',
        borderTop: '1px solid rgba(229,181,103,0.35)',
        display: 'flex', flexDirection: 'column',
        boxShadow: '0 -20px 60px rgba(0,0,0,0.6)'
      }}>
        {/* Handle */}
        <div style={{ display: 'flex', justifyContent: 'center', padding: '10px 0 6px' }}>
          <div style={{ width: 44, height: 3, background: 'var(--border-strong)' }}/>
        </div>

        {/* Header */}
        <div style={{ display: 'flex', alignItems: 'baseline', justifyContent: 'space-between', padding: '4px 18px 12px' }}>
          <div>
            <div className="td-eyebrow" style={{ color: 'var(--gold)' }}>LOGROS</div>
            <div className="td-display" style={{ fontSize: 26, lineHeight: 1, marginTop: 2 }}>{unlocked} <span style={{ color: 'var(--muted)' }}>/ {list.length}</span></div>
          </div>
          <div style={{ display: 'flex', gap: 6 }}>
            {['TODOS','DESBLOQUEADOS','EN PROGRESO'].map((t, i) => (
              <span key={t} style={{
                fontFamily: 'var(--f-mono)', fontSize: 9, letterSpacing: '0.12em',
                padding: '4px 8px',
                background: i === 0 ? 'var(--gold)' : 'transparent',
                color: i === 0 ? '#16110A' : 'var(--muted)',
                border: `1px solid ${i === 0 ? 'var(--gold)' : 'var(--border-2)'}`,
              }}>{t}</span>
            ))}
          </div>
        </div>

        <hr className="td-rule" style={{ margin: 0 }}/>

        {/* List */}
        <div style={{ flex: 1, overflowY: 'auto', padding: '8px 14px 20px', display: 'flex', flexDirection: 'column', gap: 6 }}>
          {list.map((a, i) => (
            <div key={i} style={{
              display: 'flex', alignItems: 'center', gap: 12,
              padding: '12px',
              background: a.unlocked ? '#16161C' : 'transparent',
              border: `1px solid ${a.unlocked ? 'var(--border)' : 'var(--border-2)'}`,
            }}>
              <div style={{
                width: 44, height: 44, flexShrink: 0,
                background: a.unlocked ? 'rgba(229,181,103,0.1)' : '#0E0E12',
                border: `1px solid ${a.unlocked ? 'var(--gold)' : 'var(--border-2)'}`,
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                fontFamily: 'var(--f-display)', fontSize: 22,
                color: a.unlocked ? 'var(--gold)' : 'var(--muted)',
              }}>{a.unlocked ? a.initial : '·'}</div>
              <div style={{ flex: 1, minWidth: 0 }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                  <span style={{ fontFamily: 'var(--f-display)', fontSize: 15, color: a.unlocked ? 'var(--fg)' : 'var(--fg-2)', letterSpacing: '0.04em' }}>{a.name.toUpperCase()}</span>
                  {a.unlocked && <span style={{ fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--gold)', letterSpacing: '0.14em' }}>· {a.date}</span>}
                </div>
                <div style={{ fontSize: 11, color: 'var(--muted)', marginTop: 2, lineHeight: 1.4 }}>{a.desc}</div>
                {!a.unlocked && (
                  <div style={{ marginTop: 6, display: 'flex', alignItems: 'center', gap: 8 }}>
                    <div className="td-bar" style={{ flex: 1 }}><div className="fill" style={{ width: `${a.progress*100}%` }}/></div>
                    <span className="td-num" style={{ fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--gold)' }}>{Math.round(a.progress*100)}%</span>
                  </div>
                )}
              </div>
            </div>
          ))}
        </div>
      </div>
    </TDPhoneBare>
  );
}

Object.assign(window, { TDHome, TDRanking, TDProfile, TDProfileLogros, TDStatPill });