// True Duel — Campeones (Equipo) + Detalle + Tienda

function TDChampSlot({ slot, champion, active }) {
  const empty = !champion;
  return (
    <div style={{
      flex: 1, padding: 10,
      background: empty ? 'transparent' : 'linear-gradient(180deg, #1B1B22, #14141A)',
      border: `1px solid ${active ? 'var(--gold)' : empty ? 'var(--border-2)' : 'var(--border)'}`,
      borderStyle: empty ? 'dashed' : 'solid',
      position: 'relative'
    }}>
      <div style={{ fontFamily: 'var(--f-mono)', fontSize: 9, color: active ? 'var(--gold)' : 'var(--muted)', letterSpacing: '0.18em' }}>SLOT {slot}</div>
      {empty ? (
        <div style={{ height: 70, display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--muted-2)', fontFamily: 'var(--f-display)', fontSize: 32, letterSpacing: '0.1em' }}>+</div>
      ) : (
        <>
          <div style={{ display: 'flex', justifyContent: 'center', margin: '4px 0 6px' }}>
            <TDChampionGlyph initial={champion.initial} size={56} accent={TD_ROLES[champion.rol].color}/>
          </div>
          <div style={{ fontSize: 12, fontWeight: 600, color: 'var(--fg)', textAlign: 'center', letterSpacing: '0.02em' }}>{champion.name}</div>
          <div style={{ textAlign: 'center', marginTop: 2 }}>
            <TDRolePill role={champion.rol}/>
          </div>
        </>
      )}
    </div>
  );
}

function TDChampCard({ champ, locked, badge }) {
  return (
    <div style={{
      background: '#16161C',
      border: '1px solid var(--border)',
      padding: 10,
      position: 'relative',
      opacity: locked ? 0.85 : 1,
    }}>
      {badge && (
        <div style={{
          position: 'absolute', top: -1, left: -1,
          background: 'var(--gold)', color: '#16110A',
          fontFamily: 'var(--f-display)', fontSize: 11,
          padding: '2px 6px', letterSpacing: '0.1em',
          zIndex: 1
        }}>S{badge}</div>
      )}
      {locked && (
        <div style={{
          position: 'absolute', top: 6, right: 6,
          width: 18, height: 18,
          display: 'flex', alignItems: 'center', justifyContent: 'center',
          background: '#0A0A0B', border: '1px solid var(--border-2)',
        }}>
          <svg width="10" height="10" viewBox="0 0 10 10" fill="none" stroke="var(--muted)" strokeWidth="1.2">
            <rect x="2" y="5" width="6" height="4"/>
            <path d="M3.5 5V3.5a1.5 1.5 0 013 0V5"/>
          </svg>
        </div>
      )}
      <div style={{ display: 'flex', justifyContent: 'center', marginBottom: 8 }}>
        <TDChampionGlyph
          initial={champ.initial}
          size={64}
          accent={TD_ROLES[champ.rol].color}
          subtle={locked}
        />
      </div>
      <div style={{ fontSize: 12, fontWeight: 600, color: locked ? 'var(--muted)' : 'var(--fg)', textAlign: 'center', letterSpacing: '0.02em' }}>{champ.name}</div>
      <div style={{ textAlign: 'center', marginTop: 4 }}>
        <TDRolePill role={champ.rol}/>
      </div>
      <div style={{
        marginTop: 8, paddingTop: 8,
        borderTop: '1px solid var(--border)',
        display: 'flex', justifyContent: 'space-between', alignItems: 'center',
        fontFamily: 'var(--f-mono)', fontSize: 10,
        color: 'var(--muted)'
      }}>
        <span style={{ display: 'flex', gap: 2 }}>
          {[1,2,3].map(c => (
            <span key={c} style={{
              width: 5, height: 8,
              background: c <= champ.coste ? 'var(--gold)' : 'var(--muted-2)',
            }}/>
          ))}
        </span>
        {locked ? (
          <span style={{ color: 'var(--gold)' }} className="td-num">{champ.price}</span>
        ) : (
          <span className="td-num" style={{ color: 'var(--fg-2)' }}>M·{champ.mast}</span>
        )}
      </div>
    </div>
  );
}

function TDCampeones() {
  const slotted = [
    TD_CHAMPIONS.find(c => c.id === 'vanguard'),
    TD_CHAMPIONS.find(c => c.id === 'bulwark'),
    TD_CHAMPIONS.find(c => c.id === 'riftblade'),
  ];
  return (
    <TDPhone screenLabel="05 Campeones" nav="champ">
      <div style={{ padding: '14px 14px 90px', display: 'flex', flexDirection: 'column', gap: 14 }}>

        {/* Slots */}
        <div>
          <div className="td-section-title">
            <h3>EQUIPO</h3>
            <span className="meta">SLOT 2 ACTIVO</span>
          </div>
          <div style={{ display: 'flex', gap: 6 }}>
            <TDChampSlot slot="1" champion={slotted[0]} active={false}/>
            <TDChampSlot slot="2" champion={slotted[1]} active={true}/>
            <TDChampSlot slot="3" champion={slotted[2]} active={false}/>
          </div>
        </div>

        {/* Filter chips */}
        <div style={{ display: 'flex', gap: 6, overflowX: 'auto', margin: '0 -14px', padding: '0 14px' }}>
          {['TODOS','INICIADOR','AMPLIFICADOR','EJECUTOR','SOPORTE','MAGO','CAZADOR','CENTINELA'].map((c, i) => (
            <span key={c} className="td-tag" style={{
              flexShrink: 0,
              background: i === 0 ? 'var(--gold)' : '#14141A',
              color: i === 0 ? '#16110A' : 'var(--fg-2)',
              borderColor: i === 0 ? 'var(--gold)' : 'var(--border-2)',
              fontWeight: i === 0 ? 700 : 400
            }}>{c}</span>
          ))}
        </div>

        {/* Catalog */}
        <div>
          <div className="td-section-title">
            <h3>CATÁLOGO</h3>
            <span className="meta td-num">12 CAMPEONES</span>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 6 }}>
            {TD_CHAMPIONS.map(c => (
              <TDChampCard key={c.id} champ={c} locked={!c.owned} badge={c.slot}/>
            ))}
          </div>
        </div>
      </div>

      {/* Sticky save bar */}
      <div style={{
        position: 'absolute', bottom: 56, left: 0, right: 0,
        padding: '10px 14px',
        background: 'linear-gradient(180deg, transparent, #0A0A0B 30%)',
        zIndex: 2
      }}>
        <button className="td-btn primary">GUARDAR EQUIPO</button>
      </div>
    </TDPhone>
  );
}

function TDChampDetail() {
  const c = {
    name: 'Bulwark',
    initial: 'B',
    rol: 'centinela',
    coste: 3,
    desc: 'Sentinel de la primera línea. Convierte daño recibido en cargas de protección y permite al equipo iniciar duelos largos sin colapsar.',
    skill: 'Aegis Final',
    skillType: 'Defensiva · 3 cargas',
    skillDesc: 'Aplica un escudo de 40 HP a un aliado. Si bloquea daño letal, el aliado contraataca con 20 dmg garantizado.',
    pasiva: { name: 'Conversión', desc: 'Cada 30 dmg recibidos otorga 1 carga adicional, máx 1 por turno.' },
    tags: ['shield','defense','tempo'],
    mast: { lvl: 2, xp: 240, xpNext: 500 }
  };
  return (
    <TDPhone screenLabel="06 Detalle Campeón" nav="champ">
      {/* Dim background of catalog */}
      <div style={{ position: 'absolute', inset: '0 0 16px', background: 'rgba(10,10,11,0.7)', zIndex: 1, pointerEvents: 'none' }}/>

      {/* Bottom sheet */}
      <div style={{
        position: 'absolute', left: 0, right: 0, bottom: 16,
        background: '#16161C',
        borderTop: '1px solid var(--gold-deep)',
        zIndex: 2,
        maxHeight: '78%',
        display: 'flex', flexDirection: 'column'
      }}>
        {/* Drag handle */}
        <div style={{ display: 'flex', justifyContent: 'center', padding: '8px 0 4px' }}>
          <div style={{ width: 36, height: 3, background: 'var(--border-strong)' }}/>
        </div>

        <div style={{ overflowY: 'auto', padding: '8px 16px 90px' }}>
          {/* Header */}
          <div style={{ display: 'flex', gap: 14, alignItems: 'flex-start', paddingBottom: 14, borderBottom: '1px solid var(--border)' }}>
            <TDChampionGlyph initial={c.initial} size={72} accent={TD_ROLES[c.rol].color}/>
            <div style={{ flex: 1 }}>
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <h2 className="td-display" style={{ fontSize: 32, margin: 0 }}>{c.name.toUpperCase()}</h2>
                <span style={{ fontFamily: 'var(--f-display)', fontSize: 14, padding: '2px 8px', background: 'var(--gold)', color: '#16110A', letterSpacing: '0.1em' }}>S2</span>
              </div>
              <div style={{ marginTop: 4 }}><TDRolePill role={c.rol}/></div>
              <div style={{ marginTop: 8, display: 'flex', alignItems: 'center', gap: 8, fontFamily: 'var(--f-mono)', fontSize: 11 }}>
                <span style={{ color: 'var(--muted)', letterSpacing: '0.14em' }}>COSTE</span>
                <span style={{ display: 'flex', gap: 2 }}>
                  {[1,2,3].map(x => <span key={x} style={{ width: 7, height: 12, background: x <= c.coste ? 'var(--gold)' : 'var(--muted-2)' }}/>)}
                </span>
              </div>
            </div>
          </div>

          {/* Desc */}
          <p style={{ fontSize: 13, color: 'var(--fg-2)', lineHeight: 1.5, marginTop: 14 }}>{c.desc}</p>

          {/* Special */}
          <div style={{ marginTop: 14, padding: 12, background: '#1A1A22', border: '1px solid var(--gold-deep)' }}>
            <div className="td-eyebrow" style={{ color: 'var(--gold)' }}>HABILIDAD ESPECIAL</div>
            <div className="td-display" style={{ fontSize: 24, marginTop: 4 }}>{c.skill.toUpperCase()}</div>
            <div style={{ fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--muted)', letterSpacing: '0.12em', marginTop: 2 }}>{c.skillType.toUpperCase()}</div>
            <p style={{ fontSize: 12, color: 'var(--fg-2)', lineHeight: 1.5, marginTop: 8 }}>{c.skillDesc}</p>
          </div>

          {/* Pasiva */}
          <div style={{ marginTop: 10, padding: 12, background: '#14141A', border: '1px solid var(--border)' }}>
            <div className="td-eyebrow">PASIVA · {c.pasiva.name.toUpperCase()}</div>
            <p style={{ fontSize: 12, color: 'var(--fg-2)', lineHeight: 1.5, marginTop: 6 }}>{c.pasiva.desc}</p>
          </div>

          {/* Tags */}
          <div style={{ display: 'flex', gap: 6, marginTop: 12, flexWrap: 'wrap' }}>
            {c.tags.map(t => <span key={t} className="td-tag">{t}</span>)}
          </div>

          {/* Maestría */}
          <div style={{ marginTop: 14 }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline', marginBottom: 6 }}>
              <span className="td-eyebrow">MAESTRÍA</span>
              <span className="td-num" style={{ fontSize: 11, color: 'var(--muted)' }}>{c.mast.xp}/{c.mast.xpNext} XP</span>
            </div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
              <div className="td-display" style={{ fontSize: 38, color: 'var(--gold)', lineHeight: 0.8 }}>{c.mast.lvl}</div>
              <div style={{ flex: 1 }}>
                <div className="td-bar"><div className="fill" style={{ width: `${(c.mast.xp/c.mast.xpNext)*100}%` }}/></div>
                <div style={{ fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--muted)', marginTop: 4, letterSpacing: '0.1em' }}>NV. MAESTRÍA</div>
              </div>
            </div>
          </div>
        </div>

        {/* Sticky actions */}
        <div style={{
          padding: '10px 14px 14px',
          background: '#16161C',
          borderTop: '1px solid var(--border)',
          display: 'flex', gap: 8
        }}>
          <button className="td-btn ghost" style={{ flex: 1 }}>QUITAR DEL SLOT 2</button>
          <button className="td-btn primary" style={{ flex: 1.4 }}>AÑADIR AL EQUIPO</button>
        </div>
      </div>
    </TDPhone>
  );
}

function TDTienda() {
  const items = [
    { name: 'PAQUETE INICIAL', type: 'Bundle · 3 campeones', price: 1500, currency: 'coin', state: 'buy', initial: 'P', accent: 'var(--gold)' },
    { name: 'Aspecto · Bulwark Obsidiana', type: 'Cosmético', price: 600, currency: 'gem', state: 'owned', initial: 'B', accent: '#5A8EC0' },
    { name: 'Aspecto · Vanguard Carmín',   type: 'Cosmético', price: 800, currency: 'gem', state: 'equipped', initial: 'V', accent: '#D6814A' },
    { name: 'Emote · Provocación',         type: 'Emote · ×3', price: 200, currency: 'gem', state: 'buy', initial: '!', accent: 'var(--gold)', qty: 3 },
    { name: 'Banner · Plata Eterna',       type: 'Banner', price: 400, currency: 'coin', state: 'buy', initial: '◊', accent: '#7B83C7' },
    { name: 'Pase · Temporada 2',          type: 'Pase competitivo', price: 1000, currency: 'gem', state: 'buy', initial: 'S2', accent: 'var(--gold)' },
  ];
  return (
    <TDPhone screenLabel="07 Tienda" nav="shop">
      <div style={{ padding: '14px 14px 20px', display: 'flex', flexDirection: 'column', gap: 14 }}>

        {/* Wallet */}
        <div className="td-card" style={{ padding: 14, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <div>
            <div className="td-eyebrow">CARTERA</div>
            <div style={{ display: 'flex', gap: 16, marginTop: 6 }}>
              <div>
                <span style={{ display: 'inline-block', width: 8, height: 8, borderRadius: '50%', background: 'var(--gold)', marginRight: 6 }}/>
                <span className="td-num td-display" style={{ fontSize: 22, color: 'var(--fg)' }}>{TD_PLAYER.coins.toLocaleString()}</span>
              </div>
              <div>
                <span style={{ display: 'inline-block', width: 8, height: 8, background: '#5A8EC0', transform: 'rotate(45deg)', marginRight: 6 }}/>
                <span className="td-num td-display" style={{ fontSize: 22, color: 'var(--fg)' }}>{TD_PLAYER.gems}</span>
              </div>
            </div>
          </div>
          <button className="td-btn sm" style={{ width: 'auto', background: 'transparent', color: 'var(--gold)', border: '1px solid var(--gold-deep)' }}>+ COMPRAR GEMAS</button>
        </div>

        {/* Featured */}
        <div className="td-card td-clip-corners" style={{
          padding: 18, position: 'relative', overflow: 'hidden',
          background: 'linear-gradient(135deg, #1F1A12 0%, #14141A 70%)',
          borderColor: 'rgba(229,181,103,0.3)'
        }}>
          <div className="td-eyebrow" style={{ color: 'var(--gold)' }}>OFERTA DESTACADA · 02:14:38</div>
          <h2 className="td-display" style={{ fontSize: 36, margin: '4px 0 4px' }}>PASE TEMPORADA 2</h2>
          <p style={{ fontSize: 12, color: 'var(--fg-2)', margin: '0 0 14px', lineHeight: 1.4 }}>40 niveles de recompensa, aspecto exclusivo de Riftblade y pista de duelo personalizada.</p>
          <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
            <div className="td-display" style={{ fontSize: 22, color: 'var(--fg)' }}>
              <span style={{ display: 'inline-block', width: 10, height: 10, background: '#5A8EC0', transform: 'rotate(45deg)', marginRight: 6 }}/>
              1 000
            </div>
            <span style={{ fontFamily: 'var(--f-mono)', fontSize: 11, color: 'var(--muted)', textDecoration: 'line-through' }}>1 400</span>
            <span style={{ fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--gold)', letterSpacing: '0.14em' }}>−30%</span>
          </div>
        </div>

        {/* List */}
        <div>
          <div className="td-section-title"><h3>CATÁLOGO</h3></div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
            {items.map((it, i) => (
              <div key={i} style={{
                background: '#16161C', border: '1px solid var(--border)',
                padding: 10, display: 'flex', gap: 12, alignItems: 'center'
              }}>
                <div style={{
                  width: 56, height: 56, flexShrink: 0,
                  background: '#0E0E12',
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                  fontFamily: 'var(--f-display)', fontSize: 26,
                  color: it.accent,
                  border: '1px solid var(--border-2)',
                  position: 'relative'
                }}>
                  {it.initial}
                  {it.qty && <span style={{ position: 'absolute', bottom: 2, right: 4, fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--muted)' }}>×{it.qty}</span>}
                </div>
                <div style={{ flex: 1, minWidth: 0 }}>
                  <div style={{ fontSize: 13, fontWeight: 600, color: 'var(--fg)' }}>{it.name}</div>
                  <div style={{ fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--muted)', letterSpacing: '0.1em', marginTop: 2 }}>{it.type.toUpperCase()}</div>
                  <div style={{ marginTop: 4, fontFamily: 'var(--f-display)', fontSize: 16, color: 'var(--fg)' }}>
                    <span style={{
                      display: 'inline-block',
                      width: 8, height: 8,
                      background: it.currency === 'gem' ? '#5A8EC0' : 'var(--gold)',
                      transform: it.currency === 'gem' ? 'rotate(45deg)' : 'none',
                      borderRadius: it.currency === 'gem' ? 0 : '50%',
                      marginRight: 6
                    }}/>
                    <span className="td-num">{it.price.toLocaleString()}</span>
                  </div>
                </div>
                {it.state === 'buy' && (
                  <button className="td-btn sm" style={{ width: 'auto', background: 'var(--gold)', color: '#16110A', fontFamily: 'var(--f-display)' }}>COMPRAR</button>
                )}
                {it.state === 'owned' && (
                  <button className="td-btn sm" style={{ width: 'auto', background: 'transparent', color: 'var(--fg)', border: '1px solid var(--border-2)', fontFamily: 'var(--f-display)' }}>EQUIPAR</button>
                )}
                {it.state === 'equipped' && (
                  <span style={{ fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--gold)', letterSpacing: '0.16em' }}>EQUIPADO</span>
                )}
              </div>
            ))}
          </div>
        </div>
      </div>
    </TDPhone>
  );
}

Object.assign(window, { TDCampeones, TDChampDetail, TDTienda });
