import { visualModels, ModelKey } from '../components/VisualModels';
import { GameButton } from '../components/GameButton';
import { Swords } from 'lucide-react';

interface SplashScreenProps {
  model: ModelKey;
  onStart: () => void;
}

export function SplashScreen({ model, onStart }: SplashScreenProps) {
  const theme = visualModels[model];

  return (
    <div
      style={{
        width: '100%',
        height: '100%',
        backgroundColor: theme.colors.background,
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
        padding: '24px',
        position: 'relative',
        overflow: 'hidden',
      }}
    >
      <div
        style={{
          position: 'absolute',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          background: `radial-gradient(circle at 50% 50%, ${theme.colors.primary}15, transparent 70%)`,
        }}
      />

      <div style={{ position: 'relative', textAlign: 'center' }}>
        <div
          style={{
            color: theme.colors.primary,
            marginBottom: '24px',
            display: 'flex',
            justifyContent: 'center',
          }}
        >
          <Swords size={80} />
        </div>

        <h1
          style={{
            fontFamily: theme.fonts.heading,
            fontSize: model === 'd' ? '48px' : '56px',
            fontWeight: 900,
            color: theme.colors.text,
            marginBottom: '16px',
            textShadow: model === 'b' ? `0 0 30px ${theme.colors.primary}` : 'none',
            letterSpacing: model === 'c' ? '2px' : '0px',
          }}
        >
          COMBATE
        </h1>

        <p
          style={{
            fontFamily: theme.fonts.body,
            fontSize: '18px',
            color: theme.colors.textSecondary,
            marginBottom: '48px',
            letterSpacing: '1px',
          }}
        >
          Competitivo 1v1 por Turnos
        </p>

        <div style={{ display: 'flex', flexDirection: 'column', gap: '16px', maxWidth: '300px' }}>
          <GameButton model={model} size="large" fullWidth onClick={onStart}>
            Iniciar Sesión
          </GameButton>
          <GameButton model={model} size="large" fullWidth variant="secondary" onClick={onStart}>
            Crear Cuenta
          </GameButton>
          <GameButton model={model} size="medium" fullWidth variant="ghost">
            Invitado
          </GameButton>
        </div>

        <div
          style={{
            marginTop: '48px',
            color: theme.colors.textSecondary,
            fontSize: '12px',
            fontFamily: theme.fonts.body,
          }}
        >
          v1.0.0 • {theme.name}
        </div>
      </div>
    </div>
  );
}
