// True Duel — Mochila (inventario)

const TD_MOCHILA_OWNED = [
  { id: 'sk-vng-01', name: 'Vanguard · Cazador',     type: 'Skin',     champ: 'V', rarity: 'EPICA',  acquired: '12 ABR' },
  { id: 'sk-blw-01', name: 'Bulwark · Centinela Plata', type: 'Skin',  champ: 'B', rarity: 'RARA',   acquired: '03 MAY', equipped: true },
  { id: 'mar-gold',  name: 'Marco perfil · Plata',   type: 'Marco',    champ: '·', rarity: 'RARA',   acquired: '17 MAY', equipped: true },
  { id: 'emt-vic',   name: 'Emote · Victoria sellada', type: 'Emote',  champ: '·', rarity: 'COMÚN',  acquired: '21 MAY' },
  { id: 'ban-s2',    name: 'Banner · Centinela',     type: 'Banner',   champ: '·', rarity: 'RARA',   acquired: 'PASE S2', equipped: true },
  { id: 'tit-cnt',   name: 'Título · El Centinela',  type: 'Título',   champ: '·', rarity: 'RARA',   acquired: '17 MAY', equipped: true },
];

const TD_MOCHILA_CONSUMABLES = [
  { id: 'pck-comun', name: 'Sobre común',     count: 4, sub: '+1 ítem aleatorio',     icon: '◇' },
  { id: 'pck-rara',  name: 'Sobre raro',      count: 1, sub: 'Garantiza ítem raro',   icon: '◆' },
  { id: 'pulse',     name: 'Re-roll misión',  count: 3, sub: 'Cambia 1 misión diaria', icon: '↺' },
  { id: 'sp-boost',  name: 'Boost SP · 24h',  count: 1, sub: '+25% SP por victoria',   icon: '⤴' },
];

const TD_MOCHILA_RECENT = [
  { id: 'sk-mira-bone', name: 'Mira · Hueso',        type: 'Skin',   price: 950, currency: 'ORO', rarity: 'RARA' },
  { id: 'pck-leg',      name: 'Sobre legendario',    type: 'Sobre',  price: 4,   currency: 'GEMA', rarity: 'LEGENDARIA' },
  { id: 'fra-flame',    name: 'Marco · Flama',       type: 'Marco',  price: 600, currency: 'ORO', rarity: 'RARA' },
];

const TD_RARITY_COLOR = {
  'COMÚN':       'var(--muted)',
  'RARA':        '#5A8EC0',
  'EPICA':       '#B07ACE',
  'LEGENDARIA':  'var(--gold)',
};

function TDMochila() {
  return (
    <TDPhone screenLabel="04 Mochila" nav="mochila">
      <div style={{ padding: '14px 14px 20px', display: 'flex', flexDirection: 'column', gap: 14 }}>

        {/* Header card — totals + currencies */}
        <div className="td-card td-clip-corners elev" style={{ padding: 16, position: 'relative', overflow: 'hidden' }}>
          <div style={{ position: 'absolute', inset: 0, background: 'radial-gradient(ellipse at top right, rgba(229,181,103,0.08), transparent 60%)', pointerEvents: 'none' }}/>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end', position: 'relative' }}>
            <div>
              <div className="td-eyebrow" style={{ color: 'var(--gold)' }}>MOCHILA</div>
              <div className="td-display" style={{ fontSize: 32, lineHeight: 1, marginTop: 4 }}>
                {TD_MOCHILA_OWNED.length} <span style={{ color: 'var(--muted)', fontSize: 18 }}>ÍTEMS</span>
              </div>
              <div style={{ fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--muted)', letterSpacing: '0.1em', marginTop: 2 }}>
                3 EQUIPADOS · 1 SIN VER
              </div>
            </div>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 4, fontFamily: 'var(--f-mono)', fontSize: 11 }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: 6, justifyContent: 'flex-end' }}>
                <span style={{ width: 8, height: 8, background: 'var(--gold)' }}/>
                <span className="td-num" style={{ color: 'var(--fg)' }}>2 480</span>
                <span style={{ color: 'var(--muted)', letterSpacing: '0.1em' }}>ORO</span>
              </div>
              <div style={{ display: 'flex', alignItems: 'center', gap: 6, justifyContent: 'flex-end' }}>
                <span style={{ width: 8, height: 8, background: '#7A6FCE', transform: 'rotate(45deg)' }}/>
                <span className="td-num" style={{ color: 'var(--fg)' }}>14</span>
                <span style={{ color: 'var(--muted)', letterSpacing: '0.1em' }}>GEMA</span>
              </div>
            </div>
          </div>
        </div>

        {/* Filter chips */}
        <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
          {[
            { l: 'TODOS',    n: 6, active: true },
            { l: 'SKINS',    n: 2 },
            { l: 'MARCOS',   n: 1 },
            { l: 'BANNERS',  n: 1 },
            { l: 'EMOTES',   n: 1 },
            { l: 'TÍTULOS',  n: 1 },
          ].map((f, i) => (
            <span key={i} style={{
              fontFamily: 'var(--f-mono)', fontSize: 9, letterSpacing: '0.14em',
              padding: '5px 9px',
              background: f.active ? 'var(--gold)' : 'transparent',
              color: f.active ? '#16110A' : 'var(--muted)',
              border: `1px solid ${f.active ? 'var(--gold)' : 'var(--border-2)'}`,
            }}>{f.l} <span style={{ opacity: 0.7 }}>· {f.n}</span></span>
          ))}
        </div>

        {/* Items grid */}
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: 6 }}>
          {TD_MOCHILA_OWNED.map((it) => (
            <div key={it.id} style={{
              padding: 0, background: '#13131A',
              border: `1px solid ${it.equipped ? 'var(--gold-deep)' : 'var(--border)'}`,
              position: 'relative',
            }}>
              {/* Art slot */}
              <div style={{
                aspectRatio: '1.4', position: 'relative', overflow: 'hidden',
                background: `linear-gradient(160deg, ${TD_RARITY_COLOR[it.rarity]}26 0%, #0E0E12 70%)`,
                borderBottom: `1px solid ${TD_RARITY_COLOR[it.rarity]}`,
              }}>
                <div style={{
                  position: 'absolute', inset: 0,
                  backgroundImage: 'repeating-linear-gradient(135deg, transparent 0 14px, rgba(255,255,255,0.025) 14px 15px)',
                }}/>
                <div style={{ position: 'absolute', top: 6, left: 6, fontFamily: 'var(--f-mono)', fontSize: 8, letterSpacing: '0.14em', color: TD_RARITY_COLOR[it.rarity] }}>
                  {it.rarity}
                </div>
                {it.equipped && (
                  <div style={{ position: 'absolute', top: 6, right: 6, fontFamily: 'var(--f-mono)', fontSize: 8, letterSpacing: '0.14em', color: '#16110A', background: 'var(--gold)', padding: '2px 5px' }}>
                    EQUIPADO
                  </div>
                )}
                <div style={{ position: 'absolute', inset: 0, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                  <span style={{ fontFamily: 'var(--f-display)', fontSize: 56, color: TD_RARITY_COLOR[it.rarity], opacity: 0.7, letterSpacing: '0.04em' }}>
                    {it.champ}
                  </span>
                </div>
              </div>
              <div style={{ padding: '8px 10px 10px' }}>
                <div className="td-eyebrow" style={{ color: 'var(--muted)' }}>{it.type.toUpperCase()}</div>
                <div style={{ fontSize: 12, color: 'var(--fg)', marginTop: 2, lineHeight: 1.25 }}>{it.name}</div>
              </div>
            </div>
          ))}
        </div>

        {/* Consumibles */}
        <div>
          <div className="td-section-title">
            <h3>CONSUMIBLES</h3>
            <span className="meta">{TD_MOCHILA_CONSUMABLES.length}</span>
          </div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
            {TD_MOCHILA_CONSUMABLES.map((c, i) => (
              <div key={c.id} style={{
                display: 'flex', alignItems: 'center', gap: 12,
                padding: '10px 12px',
                background: '#16161C', border: '1px solid var(--border)',
              }}>
                <div style={{
                  width: 36, height: 36,
                  background: 'rgba(229,181,103,0.08)', border: '1px solid var(--gold-deep)',
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                  fontFamily: 'var(--f-display)', fontSize: 22, color: 'var(--gold)'
                }}>{c.icon}</div>
                <div style={{ flex: 1, minWidth: 0 }}>
                  <div style={{ fontFamily: 'var(--f-display)', fontSize: 15, color: 'var(--fg)', letterSpacing: '0.04em' }}>{c.name.toUpperCase()}</div>
                  <div style={{ fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--muted)', letterSpacing: '0.1em', marginTop: 2 }}>{c.sub}</div>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                  <span className="td-num td-display" style={{ fontSize: 18, color: 'var(--gold)' }}>×{c.count}</span>
                  <button style={{
                    fontFamily: 'var(--f-mono)', fontSize: 9, letterSpacing: '0.14em',
                    padding: '4px 8px', background: 'transparent',
                    color: 'var(--gold)', border: '1px solid var(--gold-deep)', cursor: 'pointer'
                  }}>USAR</button>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Recientes en tienda */}
        <div>
          <div className="td-section-title">
            <h3>NUEVO EN TIENDA</h3>
            <span className="meta" style={{ color: 'var(--gold)' }}>VER TIENDA →</span>
          </div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
            {TD_MOCHILA_RECENT.map((it) => (
              <div key={it.id} style={{
                display: 'flex', alignItems: 'center', gap: 12,
                padding: '10px 12px',
                background: '#13131A', border: '1px solid var(--border)',
              }}>
                <div style={{
                  width: 44, height: 44,
                  background: `linear-gradient(160deg, ${TD_RARITY_COLOR[it.rarity]}33 0%, #0E0E12 80%)`,
                  border: `1px solid ${TD_RARITY_COLOR[it.rarity]}`,
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                  fontFamily: 'var(--f-display)', fontSize: 24, color: TD_RARITY_COLOR[it.rarity]
                }}>{it.type[0]}</div>
                <div style={{ flex: 1, minWidth: 0 }}>
                  <div style={{ fontFamily: 'var(--f-mono)', fontSize: 9, letterSpacing: '0.14em', color: TD_RARITY_COLOR[it.rarity] }}>{it.rarity} · {it.type.toUpperCase()}</div>
                  <div style={{ fontFamily: 'var(--f-display)', fontSize: 16, color: 'var(--fg)', letterSpacing: '0.04em', marginTop: 2 }}>{it.name.toUpperCase()}</div>
                </div>
                <div style={{ textAlign: 'right' }}>
                  <div className="td-num td-display" style={{ fontSize: 18, color: 'var(--gold)' }}>{it.price.toLocaleString()}</div>
                  <div style={{ fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--muted)', letterSpacing: '0.14em' }}>{it.currency}</div>
                </div>
              </div>
            ))}
          </div>
        </div>

      </div>
    </TDPhone>
  );
}

Object.assign(window, { TDMochila });
