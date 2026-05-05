import { visualModels, ModelKey } from '../components/VisualModels';
import { GameButton } from '../components/GameButton';
import { Search } from 'lucide-react';

interface MatchmakingScreenProps {
  model: ModelKey;
  onNavigate: (screen: string) => void;
}

export function MatchmakingScreen({ model, onNavigate }: MatchmakingScreenProps) {
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
          background: `radial-gradient(circle at 50% 50%, ${theme.colors.accent}15, transparent 70%)`,
        }}
      />

      <div style={{ position: 'relative', textAlign: 'center' }}>
        <div
          style={{
            color: theme.colors.accent,
            marginBottom: '32px',
            display: 'flex',
            justifyContent: 'center',
            animation: 'pulse 2s ease-in-out infinite',
          }}
        >
          <Search size={80} />
        </div>

        <h2
          style={{
            fontFamily: theme.fonts.heading,
            fontSize: '32px',
            fontWeight: 700,
            color: theme.colors.text,
            marginBottom: '16px',
          }}
        >
          Buscando Oponente...
        </h2>

        <p
          style={{
            fontFamily: theme.fonts.body,
            fontSize: '16px',
            color: theme.colors.textSecondary,
            marginBottom: '48px',
          }}
        >
          Emparejando con jugador de nivel similar
        </p>

        <div
          style={{
            backgroundColor: theme.colors.surface,
            borderRadius: theme.radius,
            padding: '24px',
            marginBottom: '32px',
            maxWidth: '300px',
          }}
        >
          <div style={{ marginBottom: '16px' }}>
            <div
              style={{
                color: theme.colors.textSecondary,
                fontSize: '12px',
                marginBottom: '8px',
              }}
            >
              Tiempo de búsqueda
            </div>
            <div
              style={{
                color: theme.colors.text,
                fontFamily: theme.fonts.heading,
                fontSize: '24px',
                fontWeight: 700,
              }}
            >
              0:23
            </div>
          </div>

          <div style={{ marginBottom: '16px' }}>
            <div
              style={{
                color: theme.colors.textSecondary,
                fontSize: '12px',
                marginBottom: '8px',
              }}
            >
              Rango objetivo
            </div>
            <div
              style={{
                color: theme.colors.secondary,
                fontSize: '16px',
                fontWeight: 600,
              }}
            >
              Oro I - Oro III
            </div>
          </div>

          <div
            style={{
              width: '100%',
              height: '4px',
              backgroundColor: theme.colors.backgroundLight,
              borderRadius: theme.radius,
              overflow: 'hidden',
              animation: 'loading 1.5s ease-in-out infinite',
            }}
          >
            <div
              style={{
                width: '40%',
                height: '100%',
                backgroundColor: theme.colors.accent,
              }}
            />
          </div>
        </div>

        <GameButton model={model} size="large" variant="danger" onClick={() => onNavigate('lobby')}>
          Cancelar Búsqueda
        </GameButton>
      </div>

      <style>{`
        @keyframes pulse {
          0%, 100% {
            opacity: 1;
            transform: scale(1);
          }
          50% {
            opacity: 0.6;
            transform: scale(1.1);
          }
        }
        @keyframes loading {
          0% {
            transform: translateX(-100%);
          }
          100% {
            transform: translateX(250%);
          }
        }
      `}</style>
    </div>
  );
}
