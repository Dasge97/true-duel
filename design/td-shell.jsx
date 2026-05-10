// True Duel — phone shell, header, bottom nav, shared bits

const TD_ROLES = {
  iniciador:    { color: 'var(--role-iniciador)',    short: 'INIC' },
  amplificador: { color: 'var(--role-amplificador)', short: 'AMPL' },
  ejecutor:     { color: 'var(--role-ejecutor)',     short: 'EJEC' },
  soporte:      { color: 'var(--role-soporte)',      short: 'SOPT' },
  mago:         { color: 'var(--role-mago)',         short: 'MAGO' },
  cazador:      { color: 'var(--role-cazador)',      short: 'CAZA' },
  centinela:    { color: 'var(--role-centinela)',    short: 'CENT' },
};

const TD_PLAYER = {
  initial: 'V',
  name: 'Valen.dx',
  rank: 'PLATA II',
  mmr: 1248,
  coins: 4820,
  gems: 32,
  level: 14,
  xp: 6420,
  xpNext: 9000,
};

// Custom dark Android status bar
function TDStatus() {
  return (
    <div className="td-status">
      <span>9:41</span>
      <div className="right">
        {/* signal */}
        <svg width="14" height="10" viewBox="0 0 14 10" fill="currentColor">
          <rect x="0" y="7" width="2" height="3"/>
          <rect x="3.5" y="5" width="2" height="5"/>
          <rect x="7" y="3" width="2" height="7"/>
          <rect x="10.5" y="0" width="2" height="10"/>
        </svg>
        {/* wifi */}
        <svg width="13" height="10" viewBox="0 0 13 10" fill="currentColor">
          <path d="M6.5 9.5L0 3.4a9 9 0 0113 0L6.5 9.5z"/>
        </svg>
        {/* battery */}
        <svg width="22" height="10" viewBox="0 0 22 10" fill="none" stroke="currentColor" strokeWidth="1">
          <rect x="0.5" y="0.5" width="19" height="9" rx="1"/>
          <rect x="2" y="2" width="13" height="6" fill="currentColor"/>
          <rect x="20" y="3" width="1.5" height="4" fill="currentColor"/>
        </svg>
      </div>
    </div>
  );
}

function TDAppHeader({ player = TD_PLAYER }) {
  return (
    <div className="td-app-header">
      <div className="avatar">{player.initial}</div>
      <div className="ident">
        <div className="name">{player.name}</div>
        <div className="meta">
          <span className="rank">{player.rank}</span>
          <span style={{ opacity: 0.4 }}>·</span>
          <span className="td-num">MMR {player.mmr}</span>
        </div>
      </div>
      <div className="currency">
        <div className="td-currency-pill coin">
          <span className="ic"></span>
          <span className="td-num">{player.coins.toLocaleString()}</span>
        </div>
        <div className="td-currency-pill gem">
          <span className="ic"></span>
          <span className="td-num">{player.gems}</span>
        </div>
      </div>
    </div>
  );
}

function TDNavIcon({ kind }) {
  const stroke = "currentColor";
  const sw = 1.6;
  switch (kind) {
    case 'rank': return (
      <svg width="22" height="22" viewBox="0 0 22 22" fill="none" stroke={stroke} strokeWidth={sw} strokeLinecap="round" strokeLinejoin="round">
        <path d="M4 18h4V11H4zM9 18h4V6H9zM14 18h4v-9h-4z"/>
      </svg>);
    case 'mochila': return (
      <svg width="22" height="22" viewBox="0 0 22 22" fill="none" stroke={stroke} strokeWidth={sw} strokeLinecap="round" strokeLinejoin="round">
        <path d="M5 8a3 3 0 0 1 3-3h6a3 3 0 0 1 3 3v11H5z"/>
        <path d="M8 5V4a3 3 0 0 1 6 0v1"/>
        <path d="M5 12h12"/>
        <rect x="9" y="13" width="4" height="3"/>
      </svg>);
    case 'champ': return (
      <svg width="22" height="22" viewBox="0 0 22 22" fill="none" stroke={stroke} strokeWidth={sw} strokeLinecap="round" strokeLinejoin="round">
        <path d="M5 3h12v6a6 6 0 0 1-12 0z"/><path d="M11 15v4M7 19h8"/>
      </svg>);
    case 'home': return (
      <svg width="22" height="22" viewBox="0 0 22 22" fill="none" stroke={stroke} strokeWidth={sw} strokeLinecap="round" strokeLinejoin="round">
        <path d="M3 11l8-7 8 7"/><path d="M5 10v9h12v-9"/>
      </svg>);
    case 'shop': return (
      <svg width="22" height="22" viewBox="0 0 22 22" fill="none" stroke={stroke} strokeWidth={sw} strokeLinecap="round" strokeLinejoin="round">
        <path d="M3 7h16l-1 12H4z"/><path d="M7 7V5a4 4 0 0 1 8 0v2"/>
      </svg>);
    case 'profile': return (
      <svg width="22" height="22" viewBox="0 0 22 22" fill="none" stroke={stroke} strokeWidth={sw} strokeLinecap="round" strokeLinejoin="round">
        <circle cx="11" cy="8" r="4"/><path d="M3 19c1.5-3.5 4.5-5 8-5s6.5 1.5 8 5"/>
      </svg>);
  }
}

function TDBottomNav({ active = 'home' }) {
  const items = [
    { id: 'mochila', label: 'Mochila' },
    { id: 'champ', label: 'Campeones' },
    { id: 'home', label: 'Home' },
    { id: 'shop', label: 'Tienda' },
    { id: 'profile', label: 'Perfil' },
  ];
  return (
    <div className="td-nav">
      {items.map(it => (
        <div key={it.id} className={'item' + (active === it.id ? ' active' : '')}>
          <span className="ic"><TDNavIcon kind={it.id} /></span>
          <span>{it.label}</span>
        </div>
      ))}
    </div>
  );
}

// Pill nav bar at bottom (Android gesture)
function TDGestureBar() {
  return (
    <div style={{ height: 16, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0, background: '#0F0F13' }}>
      <div style={{ width: 100, height: 3, borderRadius: 2, background: 'rgba(236,231,218,0.4)' }}/>
    </div>
  );
}

// Full phone shell with chrome
function TDPhone({ children, header = true, nav = 'home', screenLabel }) {
  return (
    <div className="td-phone" data-screen-label={screenLabel}>
      <TDStatus />
      {header && <TDAppHeader />}
      <div className="scroll">{children}</div>
      {nav && <TDBottomNav active={nav} />}
      <TDGestureBar />
    </div>
  );
}

// Phone shell without header / nav (for login + combat + matchmaking)
function TDPhoneBare({ children, screenLabel }) {
  return (
    <div className="td-phone" data-screen-label={screenLabel}>
      <TDStatus />
      <div className="scroll">{children}</div>
      <TDGestureBar />
    </div>
  );
}

// Champion glyph card (Mortal Kombat select-style monogram)
function TDChampionGlyph({ initial = 'V', glyph, size = 80, accent = 'var(--gold)', subtle = false }) {
  return (
    <div className="td-glyph" style={{ width: size, height: size, '--accent': accent }}>
      <div className="stripe"/>
      <div className="ring" style={{ borderColor: subtle ? 'var(--border-2)' : 'rgba(229,181,103,0.25)' }}/>
      {!subtle && (<>
        <span className="corner tl" style={{ borderColor: accent }}/>
        <span className="corner tr" style={{ borderColor: accent }}/>
        <span className="corner bl" style={{ borderColor: accent }}/>
        <span className="corner br" style={{ borderColor: accent }}/>
      </>)}
      <span style={{
        fontSize: size * 0.55,
        color: subtle ? 'var(--muted)' : 'var(--fg)',
        textShadow: subtle ? 'none' : '0 0 30px ' + accent + '33',
        position: 'relative', zIndex: 1,
      }}>{initial}</span>
      {glyph && (
        <span style={{
          position: 'absolute', bottom: 4, right: 6,
          fontFamily: 'var(--f-mono)', fontSize: 8,
          color: 'var(--muted)', letterSpacing: '0.1em',
        }}>{glyph}</span>
      )}
    </div>
  );
}

function TDRolePill({ role }) {
  const r = TD_ROLES[role];
  if (!r) return null;
  return (
    <span className="td-role">
      <span className="swatch" style={{ background: r.color }}/>
      <span>{role}</span>
    </span>
  );
}

// Catalog of champions used in screens
const TD_CHAMPIONS = [
  { id: 'vanguard', name: 'Vanguard', initial: 'V', rol: 'iniciador',    coste: 2, mast: 3, owned: true,  price: null,  tags: ['starter','pressure'],   slot: 1 },
  { id: 'bulwark',  name: 'Bulwark',  initial: 'B', rol: 'centinela',    coste: 3, mast: 2, owned: true,  price: null,  tags: ['shield','defense'],     slot: 2 },
  { id: 'riftblade',name: 'Riftblade',initial: 'R', rol: 'ejecutor',     coste: 3, mast: 4, owned: true,  price: null,  tags: ['finisher','execute'],   slot: 3 },
  { id: 'hexa',     name: 'Hexa',     initial: 'H', rol: 'mago',         coste: 2, mast: 1, owned: true,  price: null,  tags: ['burst','debuff'] },
  { id: 'oracle',   name: 'Oracle',   initial: 'O', rol: 'amplificador', coste: 2, mast: 2, owned: true,  price: null,  tags: ['amplifier','utility'] },
  { id: 'revenant', name: 'Revenant', initial: 'R', rol: 'ejecutor',     coste: 3, mast: 0, owned: false, price: 1200,  tags: ['execute','bleed'] },
  { id: 'warden',   name: 'Warden',   initial: 'W', rol: 'centinela',    coste: 2, mast: 0, owned: false, price: 800,   tags: ['shield','tempo'] },
  { id: 'spark',    name: 'Spark',    initial: 'S', rol: 'amplificador', coste: 2, mast: 0, owned: false, price: 800,   tags: ['amplifier','tempo'] },
  { id: 'mender',   name: 'Mender',   initial: 'M', rol: 'soporte',      coste: 2, mast: 0, owned: false, price: 1000,  tags: ['utility','starter'] },
  { id: 'grim',     name: 'Grim',     initial: 'G', rol: 'cazador',      coste: 3, mast: 0, owned: false, price: 1500,  tags: ['mark','pressure'] },
  { id: 'tracer',   name: 'Tracer',   initial: 'T', rol: 'cazador',      coste: 2, mast: 0, owned: false, price: 1000,  tags: ['mark','tempo'] },
  { id: 'null',     name: 'Null',     initial: 'N', rol: 'mago',         coste: 3, mast: 0, owned: false, price: 1500,  tags: ['control','anti-rng'] },
];

Object.assign(window, {
  TD_ROLES, TD_PLAYER, TD_CHAMPIONS,
  TDStatus, TDAppHeader, TDBottomNav, TDGestureBar, TDPhone, TDPhoneBare,
  TDChampionGlyph, TDRolePill, TDNavIcon,
});
