// True Duel — Pantalla de Títulos (desglose + escalera)

const TD_TITLES = [
  { id: 'novato',   name: 'El Novato',           sp: 0,    color: '#7A7872' },
  { id: 'aspirante',name: 'Aspirante de Bronce', sp: 500,  color: '#B07A4A' },
  { id: 'guardia',  name: 'Guardia de Bronce',   sp: 1200, color: '#B07A4A' },
  { id: 'centinela',name: 'El Centinela de Plata', sp: 2000, color: '#C9C2B0', current: true },
  { id: 'duelista', name: 'Duelista de Plata',     sp: 3500, color: '#C9C2B0' },
  { id: 'campeon',  name: 'Campeón de Oro',        sp: 5500, color: '#E5B567' },
  { id: 'arconte',  name: 'Arconte Imparable',     sp: 8500, color: '#D67A7A' },
  { id: 'mitico',   name: 'Mítico Eterno',         sp: 14000, color: '#A77ACE' },
];

function TDTitulos() {
  const currentSP = 2480;
  const cur = TD_TITLES.find(t => t.current);
  const curIdx = TD_TITLES.indexOf(cur);
  const prev = TD_TITLES[curIdx - 1];
  const next = TD_TITLES[curIdx + 1];
  const progress = (currentSP - cur.sp) / (next.sp - cur.sp);

  return (
    <TDPhoneBare screenLabel="08b Títulos">
      {/* Top bar with back */}
      <div style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '12px 16px', borderBottom: '1px solid var(--border)' }}>
        <button style={{ width: 32, height: 32, background: 'transparent', border: '1px solid var(--border-2)', color: 'var(--fg)', display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer' }}>
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round"><path d="M9 2L4 7l5 5"/></svg>
        </button>
        <div style={{ fontFamily: 'var(--f-display)', fontSize: 22, letterSpacing: '0.06em' }}>TÍTULOS</div>
        <div style={{ flex: 1 }}/>
        <span style={{ fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--gold)', letterSpacing: '0.14em' }}>S2</span>
      </div>

      <div className="scroll" style={{ overflowY: 'auto' }}>
        <div style={{ padding: '16px 14px 24px', display: 'flex', flexDirection: 'column', gap: 16 }}>

          {/* Hero — current title card */}
          <div className="td-clip-corners" style={{
            position: 'relative', overflow: 'hidden',
            background: 'linear-gradient(160deg, #1F1A12 0%, #15151A 70%)',
            border: '1px solid rgba(229,181,103,0.4)',
            padding: '22px 18px 20px',
          }}>
            <div style={{ position: 'absolute', inset: 0, backgroundImage: 'repeating-linear-gradient(135deg, transparent 0 24px, rgba(229,181,103,0.04) 24px 25px)', pointerEvents: 'none' }}/>
            <div className="td-eyebrow" style={{ color: 'var(--gold)', position: 'relative' }}>TÍTULO ACTUAL · EQUIPADO</div>
            <h2 className="td-display" style={{ fontSize: 38, margin: '6px 0 4px', lineHeight: 0.95, position: 'relative' }}>EL CENTINELA<br/>DE PLATA</h2>
            <p style={{ fontSize: 12, color: 'var(--fg-2)', lineHeight: 1.5, margin: '8px 0 14px', position: 'relative' }}>
              Concedido a los duelistas que sostienen la línea cuando todo se desmorona. Ganaste este título tras <span className="td-num" style={{ color: 'var(--fg)' }}>14 victorias</span> consecutivas defendiendo con Bulwark en SLOT 2.
            </p>

            {/* Perks grid */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 6, position: 'relative' }}>
              {[
                { ic: '◆', lbl: 'Marco avatar', val: 'Plata' },
                { ic: '▲', lbl: 'Banner perfil', val: 'Centinela' },
                { ic: '✦', lbl: 'Flair post-partida', val: 'Activo' },
              ].map((p, i) => (
                <div key={i} style={{ padding: '10px 8px', background: 'rgba(0,0,0,0.3)', border: '1px solid var(--border-2)', textAlign: 'center' }}>
                  <div style={{ fontFamily: 'var(--f-display)', fontSize: 22, color: 'var(--gold)' }}>{p.ic}</div>
                  <div className="td-eyebrow" style={{ marginTop: 2 }}>{p.lbl}</div>
                  <div style={{ fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--fg)', marginTop: 2 }}>{p.val}</div>
                </div>
              ))}
            </div>
          </div>

          {/* Progress between adjacent titles */}
          <div className="td-card" style={{ padding: '14px 16px' }}>
            <div className="td-eyebrow">PROGRESO DE TÍTULO</div>
            <div className="td-num td-display" style={{ fontSize: 28, color: 'var(--fg)', marginTop: 4 }}>
              {currentSP.toLocaleString()} <span style={{ color: 'var(--muted)', fontSize: 16 }}>SP</span>
            </div>

            {/* Ladder bar */}
            <div style={{ marginTop: 14, position: 'relative' }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 6 }}>
                <div>
                  <div style={{ fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--muted)', letterSpacing: '0.14em' }}>ANTERIOR</div>
                  <div style={{ fontSize: 12, color: 'var(--fg-2)' }}>{prev.name}</div>
                </div>
                <div style={{ textAlign: 'right' }}>
                  <div style={{ fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--gold)', letterSpacing: '0.14em' }}>SIGUIENTE</div>
                  <div style={{ fontSize: 12, color: 'var(--fg)' }}>{next.name}</div>
                </div>
              </div>

              {/* Track */}
              <div style={{ position: 'relative', height: 32, marginTop: 4 }}>
                <div style={{ position: 'absolute', top: 14, left: 0, right: 0, height: 3, background: '#0E0E12', border: '1px solid var(--border)' }}/>
                <div style={{ position: 'absolute', top: 14, left: 0, height: 3, width: `${progress*100}%`, background: 'linear-gradient(90deg, var(--gold-deep), var(--gold))' }}/>

                {/* Prev marker */}
                <div style={{ position: 'absolute', top: 9, left: 0, width: 13, height: 13, background: '#15151A', border: '2px solid var(--gold-deep)', transform: 'rotate(45deg)' }}/>
                {/* Current marker */}
                <div style={{
                  position: 'absolute', top: 4, left: `calc(${progress*100}% - 11px)`,
                  width: 22, height: 22,
                  background: 'var(--gold)', boxShadow: '0 0 0 4px rgba(229,181,103,0.18)',
                  display: 'flex', alignItems: 'center', justifyContent: 'center'
                }}>
                  <span style={{ fontFamily: 'var(--f-display)', fontSize: 13, color: '#16110A' }}>V</span>
                </div>
                {/* Next marker */}
                <div style={{ position: 'absolute', top: 9, right: 0, width: 13, height: 13, background: '#15151A', border: '2px solid var(--border-strong)', transform: 'rotate(45deg)' }}/>
              </div>

              <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 8, fontFamily: 'var(--f-mono)', fontSize: 10 }}>
                <span className="td-num" style={{ color: 'var(--muted)' }}>{prev.sp.toLocaleString()} SP</span>
                <span className="td-num" style={{ color: 'var(--gold)' }}>{(next.sp - currentSP).toLocaleString()} SP RESTANTES</span>
                <span className="td-num" style={{ color: 'var(--fg-2)' }}>{next.sp.toLocaleString()} SP</span>
              </div>
            </div>

            <hr className="td-rule" style={{ margin: '14px 0 12px' }}/>

            {/* Tips */}
            <div className="td-eyebrow" style={{ marginBottom: 6 }}>FORMAS DE GANAR SP</div>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
              {[
                { txt: 'Victoria ranked',           sp: '+22' },
                { txt: 'Victoria sin perder slot',  sp: '+8'  },
                { txt: 'Especial decisivo (último turno)', sp: '+5'  },
                { txt: 'Misión diaria completa',    sp: '+10' },
              ].map((r, i) => (
                <div key={i} style={{ display: 'flex', justifyContent: 'space-between', fontFamily: 'var(--f-mono)', fontSize: 11 }}>
                  <span style={{ color: 'var(--fg-2)' }}>{r.txt}</span>
                  <span className="td-num" style={{ color: 'var(--gold)' }}>{r.sp} SP</span>
                </div>
              ))}
            </div>
          </div>

          {/* Ladder of all titles */}
          <div>
            <div className="td-section-title">
              <h3>ESCALERA</h3>
              <span className="meta">{curIdx + 1} / {TD_TITLES.length}</span>
            </div>
            <div style={{ position: 'relative', paddingLeft: 22 }}>
              {/* vertical line */}
              <div style={{ position: 'absolute', left: 9, top: 8, bottom: 8, width: 1, background: 'var(--border-2)' }}/>
              <div style={{ position: 'absolute', left: 9, top: 8, height: `${(curIdx / (TD_TITLES.length - 1)) * 100}%`, width: 1, background: 'var(--gold)' }}/>

              <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
                {TD_TITLES.map((t, i) => {
                  const reached = i <= curIdx;
                  const isCur = i === curIdx;
                  return (
                    <div key={t.id} style={{
                      position: 'relative',
                      padding: '10px 12px',
                      background: isCur ? 'linear-gradient(90deg, rgba(229,181,103,0.10), transparent 80%)' : 'transparent',
                      border: isCur ? '1px solid var(--gold-deep)' : '1px solid transparent',
                      display: 'flex', alignItems: 'center', gap: 12,
                    }}>
                      {/* Node on rail */}
                      <span style={{
                        position: 'absolute', left: -22, top: '50%', transform: 'translateY(-50%) rotate(45deg)',
                        width: isCur ? 14 : 9, height: isCur ? 14 : 9,
                        background: isCur ? 'var(--gold)' : reached ? 'var(--gold-deep)' : '#15151A',
                        border: isCur ? '2px solid var(--gold)' : `1px solid ${reached ? 'var(--gold-deep)' : 'var(--border-2)'}`,
                      }}/>
                      <div style={{ flex: 1 }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                          <span style={{
                            fontFamily: 'var(--f-display)', fontSize: 16,
                            color: reached ? (isCur ? 'var(--gold)' : 'var(--fg)') : 'var(--muted)',
                            letterSpacing: '0.04em'
                          }}>{t.name.toUpperCase()}</span>
                          {isCur && <span style={{ fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--gold)', letterSpacing: '0.14em', padding: '1px 5px', border: '1px solid var(--gold-deep)' }}>ACTUAL</span>}
                        </div>
                        <div className="td-num" style={{ fontSize: 10, color: 'var(--muted)', marginTop: 2, letterSpacing: '0.06em' }}>
                          {t.sp.toLocaleString()} SP
                          {!reached && <span style={{ color: 'var(--muted-2)', marginLeft: 8 }}>· FALTAN {(t.sp - currentSP).toLocaleString()}</span>}
                        </div>
                      </div>
                      {/* Color dot */}
                      <span style={{ width: 10, height: 10, background: t.color, opacity: reached ? 1 : 0.35 }}/>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>

          {/* Sources of SP this season */}
          <div className="td-card" style={{ padding: 14 }}>
            <div className="td-section-title" style={{ marginBottom: 8 }}>
              <h3 style={{ fontSize: 16 }}>FUENTES · TEMPORADA 2</h3>
              <span className="meta">DESDE 12 ABR</span>
            </div>
            {/* Stacked bar */}
            <div style={{ display: 'flex', height: 10, marginBottom: 10, border: '1px solid var(--border)' }}>
              <div style={{ width: '46%', background: 'var(--gold)' }}/>
              <div style={{ width: '24%', background: 'var(--gold-2)' }}/>
              <div style={{ width: '18%', background: 'var(--gold-deep)' }}/>
              <div style={{ width: '12%', background: '#5A8EC0' }}/>
            </div>
            {[
              { c: 'var(--gold)',      lbl: 'Victorias ranked',  sp: 1140 },
              { c: 'var(--gold-2)',    lbl: 'Misiones diarias',  sp: 600  },
              { c: 'var(--gold-deep)', lbl: 'Maestría campeón',  sp: 440  },
              { c: '#5A8EC0',          lbl: 'Eventos · Pase S2',  sp: 300  },
            ].map((r, i) => (
              <div key={i} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '5px 0', borderBottom: i < 3 ? '1px solid var(--border)' : 'none' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                  <span style={{ width: 8, height: 8, background: r.c }}/>
                  <span style={{ fontSize: 12, color: 'var(--fg-2)' }}>{r.lbl}</span>
                </div>
                <span className="td-num" style={{ fontFamily: 'var(--f-mono)', fontSize: 11, color: 'var(--fg)' }}>{r.sp.toLocaleString()} SP</span>
              </div>
            ))}
          </div>

          {/* Hidden title teaser */}
          <div style={{
            padding: '14px 16px',
            border: '1px dashed var(--border-strong)',
            background: 'repeating-linear-gradient(135deg, transparent 0 12px, rgba(255,255,255,0.015) 12px 13px)',
            display: 'flex', gap: 14, alignItems: 'center'
          }}>
            <div style={{ width: 36, height: 36, background: '#0E0E12', border: '1px solid var(--border-2)', display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--muted)', fontFamily: 'var(--f-display)', fontSize: 22 }}>?</div>
            <div style={{ flex: 1 }}>
              <div className="td-eyebrow">TÍTULO OCULTO · 0 / 1</div>
              <div style={{ fontFamily: 'var(--f-display)', fontSize: 16, color: 'var(--fg-2)', marginTop: 2, letterSpacing: '0.04em' }}>?????? ??????</div>
              <div style={{ fontSize: 11, color: 'var(--muted)', marginTop: 2 }}>Pista: gana sin perder ningún campeón en 5 ranked seguidas.</div>
            </div>
          </div>

          {/* Action */}
          <button className="td-btn ghost">VER OTROS TÍTULOS DESBLOQUEADOS · 3</button>

        </div>
      </div>
    </TDPhoneBare>
  );
}

Object.assign(window, { TDTitulos, TD_TITLES });
