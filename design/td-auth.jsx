// True Duel — Auth screens (Login + Register)

function TDLogin() {
  return (
    <TDPhoneBare screenLabel="01 Login">
      <div style={{ height: '100%', display: 'flex', flexDirection: 'column', padding: '40px 28px 32px', position: 'relative' }}>
        {/* Brand mark */}
        <div style={{ flex: 1, display: 'flex', flexDirection: 'column', justifyContent: 'center' }}>
          <div style={{
            width: 56, height: 1, background: 'var(--gold)', marginBottom: 18
          }}/>
          <div style={{ fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--muted)', letterSpacing: '0.3em', marginBottom: 8 }}>
            CICLO COMPETITIVO · S2
          </div>
          <h1 className="td-display" style={{ fontSize: 72, margin: 0, lineHeight: 0.85, letterSpacing: '0.01em' }}>
            TRUE<br/>
            <span style={{ color: 'var(--gold)' }}>DUEL</span>
          </h1>
          <div style={{ fontFamily: 'var(--f-mono)', fontSize: 11, color: 'var(--fg-2)', marginTop: 14, letterSpacing: '0.08em' }}>
            DUELOS POR TURNOS · 3 VS 3
          </div>
        </div>

        {/* Mode tabs */}
        <div style={{ display: 'flex', gap: 0, borderBottom: '1px solid var(--border-2)', marginBottom: 22 }}>
          <div style={{
            flex: 1, padding: '10px 0', fontFamily: 'var(--f-display)', fontSize: 18, letterSpacing: '0.16em',
            textAlign: 'center', color: 'var(--gold)',
            borderBottom: '2px solid var(--gold)', marginBottom: '-1px'
          }}>ENTRAR</div>
          <div style={{
            flex: 1, padding: '10px 0', fontFamily: 'var(--f-display)', fontSize: 18, letterSpacing: '0.16em',
            textAlign: 'center', color: 'var(--muted)'
          }}>CREAR CUENTA</div>
        </div>

        {/* Inputs */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: 16, marginBottom: 22 }}>
          <div className="td-input">
            <label>Usuario</label>
            <div className="field focused">valen.dx</div>
          </div>
          <div className="td-input">
            <label>Contraseña</label>
            <div className="field placeholder">••••••••••</div>
          </div>
        </div>

        <button className="td-btn primary">Entrar al duelo</button>

        <div style={{ textAlign: 'center', marginTop: 18, fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--muted)', letterSpacing: '0.16em' }}>
          ¿OLVIDASTE LA CONTRASEÑA?
        </div>

        {/* Footer */}
        <div style={{ marginTop: 'auto', display: 'flex', justifyContent: 'space-between', fontFamily: 'var(--f-mono)', fontSize: 9, color: 'var(--muted-2)', letterSpacing: '0.18em' }}>
          <span>v 0.42.1</span>
          <span>SERVIDOR · EUW</span>
        </div>
      </div>
    </TDPhoneBare>
  );
}

function TDLoginLoading() {
  return (
    <TDPhoneBare screenLabel="01b Login · loading">
      <div style={{ height: '100%', display: 'flex', flexDirection: 'column', padding: '40px 28px 32px' }}>
        <div style={{ flex: 1, display: 'flex', flexDirection: 'column', justifyContent: 'center' }}>
          <div style={{ width: 56, height: 1, background: 'var(--gold)', marginBottom: 18 }}/>
          <h1 className="td-display" style={{ fontSize: 72, margin: 0, lineHeight: 0.85 }}>
            TRUE<br/><span style={{ color: 'var(--gold)' }}>DUEL</span>
          </h1>
        </div>
        <div className="td-card" style={{ display: 'flex', alignItems: 'center', gap: 14, padding: 18 }}>
          <div className="td-loading-dots"><span/><span/><span/></div>
          <div>
            <div style={{ fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--muted)', letterSpacing: '0.16em' }}>ESTADO</div>
            <div style={{ fontSize: 14, color: 'var(--fg)', marginTop: 2 }}>Entrando...</div>
          </div>
        </div>
        <div className="td-bar thick" style={{ marginTop: 14 }}>
          <div className="fill" style={{ width: '62%', animation: 'none' }}/>
        </div>
      </div>
    </TDPhoneBare>
  );
}

function TDRegister() {
  return (
    <TDPhoneBare screenLabel="02 Register">
      <div style={{ height: '100%', display: 'flex', flexDirection: 'column', padding: '32px 28px 28px' }}>
        <div style={{ marginBottom: 22 }}>
          <div style={{ width: 56, height: 1, background: 'var(--gold)', marginBottom: 14 }}/>
          <h1 className="td-display" style={{ fontSize: 44, margin: 0, lineHeight: 0.85 }}>
            CREA TU<br/>DUELISTA
          </h1>
          <div style={{ fontFamily: 'var(--f-mono)', fontSize: 11, color: 'var(--muted)', marginTop: 10, letterSpacing: '0.08em' }}>
            ELIGE UN ALIAS QUE TUS RIVALES RECUERDEN
          </div>
        </div>

        {/* Mode tabs */}
        <div style={{ display: 'flex', gap: 0, borderBottom: '1px solid var(--border-2)', marginBottom: 18 }}>
          <div style={{ flex: 1, padding: '10px 0', fontFamily: 'var(--f-display)', fontSize: 16, letterSpacing: '0.16em', textAlign: 'center', color: 'var(--muted)' }}>ENTRAR</div>
          <div style={{ flex: 1, padding: '10px 0', fontFamily: 'var(--f-display)', fontSize: 16, letterSpacing: '0.16em', textAlign: 'center', color: 'var(--gold)', borderBottom: '2px solid var(--gold)', marginBottom: '-1px' }}>CREAR CUENTA</div>
        </div>

        <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
          <div className="td-input">
            <label>Nombre visible</label>
            <div className="field">Valen</div>
          </div>
          <div className="td-input">
            <label>Usuario</label>
            <div className="field focused">valen.dx</div>
          </div>
          <div className="td-input">
            <label>Email</label>
            <div className="field placeholder">tu@email.com</div>
          </div>
          <div className="td-input">
            <label>Contraseña</label>
            <div className="field placeholder">mínimo 8 caracteres</div>
          </div>
        </div>

        {/* Error state preview */}
        <div style={{
          marginTop: 14, padding: '10px 12px', background: 'rgba(199,110,110,0.06)',
          border: '1px solid rgba(199,110,110,0.3)', display: 'flex', gap: 10, alignItems: 'flex-start'
        }}>
          <div style={{ fontFamily: 'var(--f-mono)', fontSize: 10, color: 'var(--loss)', letterSpacing: '0.12em', whiteSpace: 'nowrap', marginTop: 2 }}>API · 409</div>
          <div style={{ fontSize: 12, color: 'var(--fg-2)', lineHeight: 1.4 }}>El usuario "valen.dx" ya está en uso. Prueba otra variante.</div>
        </div>

        <button className="td-btn primary" style={{ marginTop: 16 }}>Crear cuenta</button>
      </div>
    </TDPhoneBare>
  );
}

Object.assign(window, { TDLogin, TDLoginLoading, TDRegister });
