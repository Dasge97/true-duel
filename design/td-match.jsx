// True Duel — Matchmaking, Combate, Resultado

function TDBackBar({ title }) {
  return (
    <div style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '12px 16px', borderBottom: '1px solid var(--border)' }}>
      <button style={{ width: 32, height: 32, background: 'transparent', border: '1px solid var(--border-2)', color: 'var(--fg)', display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer' }}>
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round"><path d="M9 2L4 7l5 5"/></svg>
      </button>
      <div style={{ fontFamily: 'var(--f-display)', fontSize: 22, letterSpacing: '0.06em' }}>{title.toUpperCase()}</div>
      <div style={{ flex: 1 }}/>
      <span className="td-eyebrow">SHELL</span>
    </div>
  );
}

function TDMatchmaking() {
  const modes = [
    { id: 'normal_bot', name: 'Casual vs Bot',     desc: 'Sin riesgo de MMR. Ideal para probar equipo.', selected: false },
    { id: 'ranked_pvp', name: 'Ranked',            desc: 'Competitivo. Premia LP y maestría.',           selected: true },
    { id: 'ranked_bot', name: 'Ranked vs Bot',     desc: 'Bot competitivo. Otorga MMR reducido.',        selected: false },
  ];
  return (
    <TDPhoneBare screenLabel="09 Matchmaking · selección">
      <TDBackBar title="Matchmaking"/>
      <div style={{ padding: '14px 14px 24px', display: 'flex', flexDirection: 'column', gap: 14 }}>

        {/* Captain card */}
        <div className="td-card td-clip-corners" style={{
          padding: 16, display: 'flex', gap: 14, alignItems: 'center',
          background: 'linear-gradient(135deg, #1B1A14 0%, #14141A 70%)',
          borderColor: 'rgba(229,181,103,0.3)'
        }}>
          <TDChampionGlyph initial="V" size={72} accent="var(--role-iniciador)"/>
          <div style={{ flex: 1, minWidth: 0 }}>
            <div className="td-eyebrow" style={{ color: 'var(--gold)' }}>CAPITÁN</div>
            <div className="td-display" style={{ fontSize: 26, marginTop: 2 }}>VANGUARD</div>
            <div style={{ marginTop: 4 }}><TDRolePill role="iniciador"/></div>
            <div style={{ display: 'flex', gap: 12, marginTop: 6, fontFamily: 'var(--f-mono)', fontSize: 11, color: 'var(--fg-2)' }}>
              <span>PLATA II</span>
              <span style={{ color: 'var(--muted)' }}>·</span>
              <span>MMR 1 248</span>
            </div>
          </div>
        </div>

        {/* Mode selection */}
        <div>
          <div className="td-section-title"><h3>MODO</h3><span className="meta">3 DISPONIBLES</span></div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
            {modes.map(m => (
              <div key={m.id} style={{
                padding: 14,
                background: m.selected ? 'linear-gradient(180deg, #1F1A12, #14141A)' : '#16161C',
                border: `1px solid ${m.selected ? 'var(--gold)' : 'var(--border)'}`,
                display: 'flex', alignItems: 'center', gap: 14, position: 'relative'
              }}>
                {m.selected && <div style={{ position: 'absolute', top: 0, left: 0, bottom: 0, width: 3, background: 'var(--gold)' }}/>}
                <div style={{
                  width: 40, height: 40,
                  background: m.selected ? 'rgba(229,181,103,0.1)' : '#0E0E12',
                  border: `1px solid ${m.selected ? 'var(--gold)' : 'var(--border-2)'}`,
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                  color: m.selected ? 'var(--gold)' : 'var(--muted)'
                }}>
                  {m.id === 'ranked_pvp' && <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" strokeWidth="1.6"><path d="M9 1l2 5h5l-4 3 1.5 5.5L9 11l-4.5 3.5L6 9 2 6h5z"/></svg>}
                  {m.id === 'normal_bot' && <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" strokeWidth="1.6"><rect x="3" y="5" width="12" height="9"/><circle cx="7" cy="9" r="1"/><circle cx="11" cy="9" r="1"/><path d="M9 1v4"/></svg>}
                  {m.id === 'ranked_bot' && <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" strokeWidth="1.6"><rect x="3" y="5" width="12" height="9"/><circle cx="7" cy="9" r="1"/><circle cx="11" cy="9" r="1"/><path d="M9 1l1 3h-2zM2 17l3-3"/></svg>}
                </div>
                <div style={{ flex: 1, minWidth: 0 }}>
                  <div style={{ display: 'flex', alignItems: 'baseline', gap: 8 }}>
                    <span className="td-display" style={{ fontSize: 18, color: 'var(--fg)' }}>{m.name.toUpperCase()}</span>
                    <span style={{ fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--muted)', letterSpacing: '0.1em' }}>{m.id}</span>
                  </div>
                  <div style={{ fontSize: 12, color: 'var(--fg-2)', marginTop: 2 }}>{m.desc}</div>
                </div>
                <div style={{
                  width: 18, height: 18, borderRadius: '50%',
                  border: `1.5px solid ${m.selected ? 'var(--gold)' : 'var(--border-strong)'}`,
                  display: 'flex', alignItems: 'center', justifyContent: 'center'
                }}>
                  {m.selected && <span style={{ width: 8, height: 8, borderRadius: '50%', background: 'var(--gold)' }}/>}
                </div>
              </div>
            ))}
          </div>
        </div>

        <div style={{ fontFamily: 'var(--f-mono)', fontSize: 11, color: 'var(--fg-2)', textAlign: 'center', letterSpacing: '0.04em', padding: '4px 0' }}>
          Elige modo y pulsa buscar partida.
        </div>

        <button className="td-btn primary">BUSCAR PARTIDA →</button>
      </div>
    </TDPhoneBare>
  );
}

function TDMatchSearching() {
  return (
    <TDPhoneBare screenLabel="10 Matchmaking · buscando">
      <TDBackBar title="Buscando"/>
      <div style={{ padding: '14px 14px', display: 'flex', flexDirection: 'column', gap: 18, height: '100%' }}>

        {/* Status hero */}
        <div style={{
          flex: 1,
          display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center',
          textAlign: 'center', position: 'relative', overflow: 'hidden'
        }}>
          {/* Concentric pulse rings */}
          <div style={{ position: 'relative', width: 200, height: 200, marginBottom: 24 }}>
            <div style={{ position: 'absolute', inset: 0, border: '1px solid var(--gold-deep)', opacity: 0.2 }}/>
            <div style={{ position: 'absolute', inset: 18, border: '1px solid var(--gold-deep)', opacity: 0.4 }}/>
            <div style={{ position: 'absolute', inset: 38, border: '1px solid var(--gold)', opacity: 0.7 }}/>
            <div style={{ position: 'absolute', inset: 58, background: 'rgba(229,181,103,0.06)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
              <TDChampionGlyph initial="V" size={68} accent="var(--gold)"/>
            </div>
            {/* Corners */}
            <span style={{ position: 'absolute', top: -1, left: -1, width: 14, height: 14, borderTop: '2px solid var(--gold)', borderLeft: '2px solid var(--gold)' }}/>
            <span style={{ position: 'absolute', top: -1, right: -1, width: 14, height: 14, borderTop: '2px solid var(--gold)', borderRight: '2px solid var(--gold)' }}/>
            <span style={{ position: 'absolute', bottom: -1, left: -1, width: 14, height: 14, borderBottom: '2px solid var(--gold)', borderLeft: '2px solid var(--gold)' }}/>
            <span style={{ position: 'absolute', bottom: -1, right: -1, width: 14, height: 14, borderBottom: '2px solid var(--gold)', borderRight: '2px solid var(--gold)' }}/>
          </div>

          <div className="td-eyebrow" style={{ color: 'var(--gold)' }}>RANKED · 3 V 3</div>
          <h2 className="td-display" style={{ fontSize: 38, margin: '6px 0 4px' }}>BUSCANDO RIVAL</h2>
          <div style={{ fontFamily: 'var(--f-mono)', fontSize: 13, color: 'var(--fg-2)', display: 'flex', alignItems: 'center', gap: 8 }}>
            <span className="td-loading-dots"><span/><span/><span/></span>
            <span>en cola desde 00:42</span>
          </div>

          <div className="td-num td-display" style={{ fontSize: 64, color: 'var(--fg)', marginTop: 20, letterSpacing: '0.04em' }}>00:42</div>

          {/* Progress */}
          <div style={{ width: '100%', marginTop: 18 }}>
            <div className="td-bar"><div className="fill" style={{ width: '38%' }}/></div>
            <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 6, fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--muted)' }}>
              <span>RANGO MMR ±150</span>
              <span>ETA · 00:30</span>
            </div>
          </div>
        </div>

        <button className="td-btn ghost">CANCELAR BÚSQUEDA</button>

        {/* Tip */}
        <div style={{
          padding: '8px 12px',
          background: '#13131A', border: '1px solid var(--border)',
          fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--muted)',
          letterSpacing: '0.04em', textAlign: 'center'
        }}>
          TIP · Bulwark gana 1 carga extra cada 30 daño bloqueado.
        </div>
      </div>
    </TDPhoneBare>
  );
}

// ─────────────── Combate ───────────────

function TDCombatChamp({ champ, side, targeted, active, ko, action }) {
  const hpPct = (champ.hp / 100) * 100;
  const hpStat = champ.hp > 60 ? '' : champ.hp > 30 ? 'mid' : 'low';
  return (
    <div style={{
      flex: 1,
      padding: 8,
      background: ko ? '#0F0E10' : (active ? 'linear-gradient(180deg, #1F1A12, #14141A)' : '#15151B'),
      border: `1px solid ${active ? 'var(--gold)' : (targeted ? 'var(--loss)' : 'var(--border-2)')}`,
      position: 'relative',
      opacity: ko ? 0.45 : 1
    }}>
      {active && <div style={{ position: 'absolute', top: 0, left: 0, right: 0, height: 2, background: 'var(--gold)' }}/>}
      {targeted && <div style={{ position: 'absolute', top: -2, right: -2, fontFamily: 'var(--f-mono)', fontSize: 9, padding: '1px 4px', background: 'var(--loss)', color: '#fff', letterSpacing: '0.12em', zIndex: 2 }}>TARGET</div>}
      <div style={{ display: 'flex', justifyContent: 'center' }}>
        <TDChampionGlyph
          initial={champ.initial}
          size={48}
          accent={side === 'enemy' ? 'var(--loss)' : 'var(--gold)'}
          subtle={ko}
        />
      </div>
      <div style={{ fontFamily: 'var(--f-display)', fontSize: 13, textAlign: 'center', marginTop: 4, color: ko ? 'var(--muted)' : 'var(--fg)', letterSpacing: '0.04em' }}>
        {ko ? 'KO' : champ.name.toUpperCase()}
      </div>

      {/* HP */}
      <div style={{ marginTop: 4 }}>
        <div className={`td-bar hp ${hpStat}`}>
          <div className="fill" style={{ width: `${ko ? 0 : hpPct}%` }}/>
        </div>
        <div className="td-num" style={{ fontSize: 10, color: 'var(--fg-2)', textAlign: 'right', marginTop: 2 }}>{ko ? '0' : champ.hp}/100</div>
      </div>

      {/* Cargas (player only) */}
      {side === 'player' && !ko && (
        <div style={{ display: 'flex', gap: 2, justifyContent: 'center', marginTop: 4 }}>
          {[0,1,2].map(i => (
            <span key={i} style={{
              flex: 1, height: 4,
              background: i < champ.cargas ? 'var(--gold)' : 'var(--muted-2)'
            }}/>
          ))}
        </div>
      )}

      {/* Action badge */}
      {action && !ko && (
        <div style={{
          marginTop: 6, padding: '3px 6px',
          background: '#0E0E12', border: '1px solid var(--gold-deep)',
          fontFamily: 'var(--f-mono)', fontSize: 10,
          color: 'var(--gold)', textAlign: 'center', letterSpacing: '0.08em',
        }}>{action}</div>
      )}
      {champ.guarding && !action && (
        <div style={{ marginTop: 6, fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--info)', textAlign: 'center', letterSpacing: '0.14em' }}>GUARDING</div>
      )}
    </div>
  );
}

function TDCombat() {
  const enemies = [
    { id: 'e0', initial: 'K', name: 'Krxn',    hp: 78, cargas: 1, ko: false },
    { id: 'e1', initial: 'N', name: 'Null',    hp: 42, cargas: 2, ko: false, targeted: true },
    { id: 'e2', initial: 'G', name: 'Grim',    hp: 0,  cargas: 0, ko: true },
  ];
  const players = [
    { id: 's0', initial: 'V', name: 'Vanguard',  hp: 64, cargas: 1, ko: false, action: 'ATK→E1' },
    { id: 's1', initial: 'B', name: 'Bulwark',   hp: 88, cargas: 3, ko: false, guarding: true, active: true },
    { id: 's2', initial: 'R', name: 'Riftblade', hp: 21, cargas: 2, ko: false },
  ];
  const activeIdx = 1;
  return (
    <TDPhoneBare screenLabel="11 Combate">
      {/* Header */}
      <div style={{
        display: 'flex', alignItems: 'center', justifyContent: 'space-between',
        padding: '10px 14px', borderBottom: '1px solid var(--border-2)',
        background: '#0F0F13'
      }}>
        <div>
          <span className="td-eyebrow">TURNO</span>
          <span className="td-num td-display" style={{ fontSize: 22, color: 'var(--gold)', marginLeft: 8 }}>04</span>
        </div>
        <div style={{ fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--muted)', letterSpacing: '0.1em' }}>
          RANKED · MMR 1 248
        </div>
        <button style={{ background: 'transparent', border: '1px solid var(--border-2)', color: 'var(--fg)', padding: '4px 8px', fontFamily: 'var(--f-mono)', fontSize: 10, letterSpacing: '0.12em', cursor: 'pointer' }}>LOG</button>
      </div>

      {/* Feedback */}
      <div style={{ padding: '6px 14px', fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--win)', letterSpacing: '0.06em', background: 'rgba(123,184,147,0.05)', borderBottom: '1px solid rgba(123,184,147,0.18)' }}>
        T3 · S2→E1 24 dmg crítico · GRIM eliminado
      </div>

      {/* Battlefield */}
      <div style={{ flex: 1, display: 'flex', flexDirection: 'column', padding: '14px 12px 0', gap: 10 }}>
        {/* Enemy row */}
        <div>
          <div style={{ fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--loss)', letterSpacing: '0.18em', marginBottom: 6, paddingLeft: 2 }}>RIVALES · KRXN_VOID · 1 248 MMR</div>
          <div style={{ display: 'flex', gap: 6 }}>
            {enemies.map(e => <TDCombatChamp key={e.id} champ={e} side="enemy" targeted={e.targeted} ko={e.ko}/>)}
          </div>
        </div>

        {/* VS */}
        <div style={{ display: 'flex', alignItems: 'center', gap: 10, margin: '4px 0' }}>
          <hr className="td-rule" style={{ flex: 1 }}/>
          <span className="td-display" style={{ fontSize: 18, color: 'var(--gold)', letterSpacing: '0.1em' }}>VS</span>
          <hr className="td-rule" style={{ flex: 1 }}/>
        </div>

        {/* Player row */}
        <div>
          <div style={{ fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--gold)', letterSpacing: '0.18em', marginBottom: 6, paddingLeft: 2 }}>TU EQUIPO · ACCIÓN PENDIENTE EN S1</div>
          <div style={{ display: 'flex', gap: 6 }}>
            {players.map(p => <TDCombatChamp key={p.id} champ={p} side="player" active={p.active} ko={p.ko} action={p.action}/>)}
          </div>
        </div>

        {/* Action selector */}
        <div style={{ marginTop: 8, padding: 10, background: '#13131A', border: '1px solid var(--border-2)', position: 'relative' }}>
          <div style={{ display: 'flex', alignItems: 'baseline', justifyContent: 'space-between', marginBottom: 8 }}>
            <div>
              <span className="td-eyebrow" style={{ color: 'var(--gold)' }}>SLOT 1 · BULWARK</span>
            </div>
            <span style={{ fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--muted)' }}>ELIGE ACCIÓN</span>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 6 }}>
            <button style={{
              padding: '12px 8px', background: '#1A1A22', border: '1px solid var(--border-2)',
              fontFamily: 'var(--f-display)', fontSize: 16, color: 'var(--fg)', cursor: 'pointer', letterSpacing: '0.06em'
            }}>
              <div>ATK</div>
              <div style={{ fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--muted)', marginTop: 2 }}>requiere objetivo</div>
            </button>
            <button style={{
              padding: '12px 8px', background: 'rgba(110,157,199,0.06)', border: '1px solid var(--info)',
              fontFamily: 'var(--f-display)', fontSize: 16, color: 'var(--info)', cursor: 'pointer', letterSpacing: '0.06em'
            }}>
              <div>DEF</div>
              <div style={{ fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--muted)', marginTop: 2 }}>sin objetivo</div>
            </button>
            <button style={{
              padding: '12px 8px',
              background: 'linear-gradient(180deg, rgba(229,181,103,0.12), rgba(229,181,103,0.04))',
              border: '1px solid var(--gold)',
              fontFamily: 'var(--f-display)', fontSize: 16, color: 'var(--gold)', cursor: 'pointer', letterSpacing: '0.06em'
            }}>
              <div>SPE</div>
              <div style={{ fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--gold)', marginTop: 2 }}>3 cargas · listo</div>
            </button>
          </div>
        </div>
      </div>

      {/* End turn */}
      <div style={{ padding: '12px 12px 14px', display: 'flex', gap: 8, background: '#0F0F13', borderTop: '1px solid var(--border-2)' }}>
        <button className="td-btn ghost sm" style={{ width: 'auto', padding: '14px 14px' }}>
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" strokeWidth="1.6"><path d="M2 7l4-4M2 7l4 4M2 7h10"/></svg>
        </button>
        <button className="td-btn primary" style={{ flex: 1 }}>FINALIZAR TURNO →</button>
      </div>

      {/* Side log peek */}
      <div style={{
        position: 'absolute', right: 0, top: 70, bottom: 70,
        width: 26, background: '#15151B', borderLeft: '1px solid var(--gold-deep)',
        display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center',
        cursor: 'pointer', zIndex: 1
      }}>
        <span style={{ fontFamily: 'var(--f-display)', fontSize: 13, color: 'var(--gold)', letterSpacing: '0.1em', writingMode: 'vertical-rl', transform: 'rotate(180deg)' }}>LOG · 12</span>
      </div>
    </TDPhoneBare>
  );
}

function TDCombatLog() {
  const log = [
    { t: 4, txt: 'S1:DEF · escudo +20 a S2', side: 'p' },
    { t: 3, txt: 'S2→E1 24 dmg crítico', side: 'p' },
    { t: 3, txt: 'GRIM eliminado',           side: 'k' },
    { t: 3, txt: 'E0→S0 14 dmg',             side: 'e' },
    { t: 2, txt: 'S0→E2 12 dmg · MARK',      side: 'p' },
    { t: 2, txt: 'S1:DEF',                   side: 'p' },
    { t: 1, txt: 'S2 cargas +1',             side: 'i' },
    { t: 1, txt: '¡Combate iniciado!',       side: 'i' },
  ];
  return (
    <TDPhoneBare screenLabel="11b Combate · log">
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '10px 14px', borderBottom: '1px solid var(--border-2)', background: '#0F0F13' }}>
        <div><span className="td-eyebrow">TURNO</span><span className="td-num td-display" style={{ fontSize: 22, color: 'var(--gold)', marginLeft: 8 }}>04</span></div>
        <div style={{ fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--muted)' }}>RANKED · MMR 1 248</div>
        <button style={{ background: 'var(--gold)', border: 0, color: '#16110A', padding: '4px 8px', fontFamily: 'var(--f-mono)', fontSize: 10, letterSpacing: '0.12em', cursor: 'pointer', fontWeight: 700 }}>CERRAR</button>
      </div>

      <div style={{ flex: 1, display: 'flex' }}>
        <div style={{ flex: 1, opacity: 0.5, padding: 14, fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--muted)' }}>
          {/* Mini battlefield silhouette */}
          <div style={{ display: 'flex', gap: 4, marginBottom: 8 }}>
            {[0,1,2].map(i => <div key={i} style={{ flex: 1, height: 50, background: '#15151B', border: '1px solid var(--border)', display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--muted)' }}>E{i}</div>)}
          </div>
          <div style={{ display: 'flex', gap: 4 }}>
            {[0,1,2].map(i => <div key={i} style={{ flex: 1, height: 50, background: '#15151B', border: '1px solid var(--border)', display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--muted)' }}>S{i}</div>)}
          </div>
        </div>
        <div style={{
          width: 240, background: '#13131A',
          borderLeft: '1px solid var(--gold-deep)',
          display: 'flex', flexDirection: 'column'
        }}>
          <div style={{ padding: '12px 14px 8px', borderBottom: '1px solid var(--border)' }}>
            <div className="td-eyebrow" style={{ color: 'var(--gold)' }}>LOG DE COMBATE</div>
            <div className="td-display" style={{ fontSize: 18, marginTop: 2 }}>HISTORIAL</div>
          </div>
          <div style={{ flex: 1, overflowY: 'auto', padding: '8px 0' }}>
            {log.map((l, i) => (
              <div key={i} style={{
                padding: '8px 14px',
                borderLeft: `2px solid ${l.side === 'p' ? 'var(--gold)' : l.side === 'e' ? 'var(--loss)' : l.side === 'k' ? 'var(--win)' : 'var(--muted-2)'}`,
                marginBottom: 1,
                background: '#15151B',
                fontFamily: 'var(--f-mono)', fontSize: 11, color: 'var(--fg-2)'
              }}>
                <span style={{ color: 'var(--muted)', marginRight: 8 }}>T{l.t}</span>
                {l.txt}
              </div>
            ))}
          </div>
        </div>
      </div>

      <div style={{ height: 56, background: '#0F0F13', borderTop: '1px solid var(--border-2)' }}/>
    </TDPhoneBare>
  );
}

// ─────────────── Resultado ───────────────

function TDResultRow({ label, value, accent }) {
  return (
    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline', padding: '8px 0', borderBottom: '1px solid var(--border)' }}>
      <span style={{ fontFamily: 'var(--f-mono)', fontSize: 11, color: 'var(--muted)', letterSpacing: '0.1em' }}>{label.toUpperCase()}</span>
      <span className="td-num td-display" style={{ fontSize: 20, color: accent || 'var(--fg)' }}>{value}</span>
    </div>
  );
}

function TDResultado({ kind = 'victory' }) {
  const config = {
    victory: { title: '¡VICTORIA LEGENDARIA!', sub: 'Has dominado el duelo', color: 'var(--win)', tag: 'W' },
    defeat:  { title: 'DERROTA HONORABLE',     sub: 'Toca volver más fuerte', color: 'var(--loss)', tag: 'L' },
    draw:    { title: 'EMPATE ÉPICO',          sub: 'La batalla terminó igualada', color: 'var(--gold)', tag: '=' },
  }[kind];
  const isWin = kind === 'victory';
  return (
    <TDPhoneBare screenLabel={kind === 'victory' ? '13 Resultado · Victoria' : kind === 'defeat' ? '14 Resultado · Derrota' : '15 Resultado · Empate'}>
      <div style={{ height: '100%', display: 'flex', flexDirection: 'column', overflow: 'hidden' }}>

        {/* Hero */}
        <div style={{
          padding: '32px 20px 24px', textAlign: 'center', position: 'relative',
          background: isWin
            ? 'radial-gradient(ellipse at top, rgba(229,181,103,0.18), transparent 70%)'
            : kind === 'defeat'
            ? 'radial-gradient(ellipse at top, rgba(199,110,110,0.10), transparent 70%)'
            : 'radial-gradient(ellipse at top, rgba(229,181,103,0.10), transparent 70%)',
          borderBottom: '1px solid var(--border-2)'
        }}>
          <div style={{
            display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
            width: 56, height: 56, background: '#0E0E12',
            border: `2px solid ${config.color}`, fontFamily: 'var(--f-display)', fontSize: 36,
            color: config.color, marginBottom: 14
          }}>{config.tag}</div>
          <div className="td-eyebrow" style={{ color: config.color }}>RESULTADO · TURNO 9</div>
          <h2 className="td-display" style={{ fontSize: 36, margin: '6px 0 4px', letterSpacing: '0.02em', color: 'var(--fg)' }}>{config.title}</h2>
          <div style={{ fontSize: 13, color: 'var(--fg-2)' }}>{config.sub}</div>
        </div>

        <div style={{ flex: 1, overflowY: 'auto', padding: '16px 16px 20px' }}>
          {/* Recompensas */}
          <div className="td-section-title" style={{ marginBottom: 8 }}>
            <h3 style={{ fontSize: 18 }}>RECOMPENSAS</h3>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 6, marginBottom: 16 }}>
            <div className="td-card td-clip-corners" style={{ padding: 12, borderColor: 'rgba(229,181,103,0.3)' }}>
              <div className="td-eyebrow">MMR Δ</div>
              <div className="td-num td-display" style={{ fontSize: 26, color: isWin ? 'var(--win)' : 'var(--loss)' }}>
                {isWin ? '+22' : kind === 'defeat' ? '−14' : '±0'}
              </div>
            </div>
            <div className="td-card" style={{ padding: 12 }}>
              <div className="td-eyebrow">XP</div>
              <div className="td-num td-display" style={{ fontSize: 26, color: 'var(--fg)' }}>+{isWin ? 240 : 90}</div>
            </div>
            <div className="td-card" style={{ padding: 12 }}>
              <div className="td-eyebrow"><span style={{ display: 'inline-block', width: 6, height: 6, borderRadius: '50%', background: 'var(--gold)', marginRight: 4 }}/>MONEDAS</div>
              <div className="td-num td-display" style={{ fontSize: 26, color: 'var(--gold)' }}>+{isWin ? 180 : 50}</div>
            </div>
            <div className="td-card" style={{ padding: 12 }}>
              <div className="td-eyebrow"><span style={{ display: 'inline-block', width: 6, height: 6, background: '#5A8EC0', transform: 'rotate(45deg)', marginRight: 4 }}/>GEMAS</div>
              <div className="td-num td-display" style={{ fontSize: 26, color: '#5A8EC0' }}>+{isWin ? 4 : 1}</div>
            </div>
          </div>

          {/* Rendimiento */}
          <div className="td-section-title" style={{ marginBottom: 4 }}>
            <h3 style={{ fontSize: 18 }}>RENDIMIENTO</h3>
            <span className="meta">9 TURNOS</span>
          </div>
          <div className="td-card" style={{ padding: '4px 14px' }}>
            <TDResultRow label="Daño infligido"  value="284" accent="var(--gold)"/>
            <TDResultRow label="Daño recibido"   value="196"/>
            <TDResultRow label="Turnos"          value="9"/>
            <TDResultRow label="Ataques"         value="14"/>
            <TDResultRow label="Defensas"        value="6"/>
            <TDResultRow label="Especiales"      value="3"/>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline', padding: '8px 0' }}>
              <span style={{ fontFamily: 'var(--f-mono)', fontSize: 11, color: 'var(--muted)', letterSpacing: '0.1em' }}>MITIGACIÓN</span>
              <span className="td-num td-display" style={{ fontSize: 20, color: 'var(--info)' }}>72</span>
            </div>
          </div>
        </div>

        {/* Actions */}
        <div style={{ padding: '12px 14px 14px', display: 'flex', gap: 8, borderTop: '1px solid var(--border-2)', background: '#0F0F13' }}>
          <button className="td-btn ghost" style={{ flex: 1 }}>CONTINUAR</button>
          <button className="td-btn primary" style={{ flex: 1.4 }}>JUGAR DE NUEVO →</button>
        </div>
      </div>
    </TDPhoneBare>
  );
}

Object.assign(window, {
  TDMatchmaking, TDMatchSearching,
  TDCombat, TDCombatLog,
  TDResultado,
});
