import { visualModels, ModelKey } from '../components/VisualModels';
import { GameButton } from '../components/GameButton';
import { Trophy, TrendingUp, Award, Zap } from 'lucide-react';

interface ResultScreenProps {
  model: ModelKey;
  onNavigate: (screen: string) => void;
}

export function ResultScreen({ model, onNavigate }: ResultScreenProps) {
  const theme = visualModels[model];
  const victory = true;

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
          background: victory
            ? `radial-gradient(circle at 50% 50%, ${theme.colors.success}20, transparent 70%)`
            : `radial-gradient(circle at 50% 50%, ${theme.colors.danger}20, transparent 70%)`,
        }}
      />

      <div style={{ position: 'relative', textAlign: 'center', width: '100%', maxWidth: '400px' }}>
        <div
          style={{
            color: victory ? theme.colors.success : theme.colors.danger,
            marginBottom: '24px',
            display: 'flex',
            justifyContent: 'center',
          }}
        >
          <Trophy size={100} />
        </div>

        <h1
          style={{
            fontFamily: theme.fonts.heading,
            fontSize: '48px',
            fontWeight: 900,
            color: victory ? theme.colors.success : theme.colors.danger,
            marginBottom: '16px',
            textShadow: model === 'b' ? `0 0 30px ${victory ? theme.colors.success : theme.colors.danger}` : 'none',
          }}
        >
          {victory ? '¡VICTORIA!' : 'DERROTA'}
        </h1>

        <p
          style={{
            fontFamily: theme.fonts.body,
            fontSize: '16px',
            color: theme.colors.textSecondary,
            marginBottom: '32px',
          }}
        >
          {victory ? 'Excelente combate, continúa así' : 'No te rindas, inténtalo de nuevo'}
        </p>

        <div
          style={{
            backgroundColor: theme.colors.surface,
            borderRadius: theme.radius,
            padding: '24px',
            marginBottom: '32px',
          }}
        >
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '20px', marginBottom: '24px' }}>
            <StatCard
              icon={<TrendingUp size={24} />}
              label="Rango"
              value="+25 LP"
              positive={victory}
              model={model}
            />
            <StatCard
              icon={<Award size={24} />}
              label="XP Ganada"
              value="+150"
              positive
              model={model}
            />
            <StatCard
              icon={<Zap size={24} />}
              label="Daño Total"
              value="2,450"
              positive
              model={model}
            />
            <StatCard
              icon={<Trophy size={24} />}
              label="Duración"
              value="4:32"
              positive
              model={model}
            />
          </div>

          <div
            style={{
              borderTop: `1px solid ${theme.colors.backgroundLight}`,
              paddingTop: '16px',
            }}
          >
            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '8px' }}>
              <span style={{ color: theme.colors.textSecondary, fontSize: '14px' }}>Progreso de Rango</span>
              <span style={{ color: theme.colors.text, fontSize: '14px', fontWeight: 600 }}>75/100 LP</span>
            </div>
            <div
              style={{
                width: '100%',
                height: '8px',
                backgroundColor: theme.colors.backgroundLight,
                borderRadius: theme.radius,
                overflow: 'hidden',
              }}
            >
              <div
                style={{
                  width: '75%',
                  height: '100%',
                  backgroundColor: theme.colors.secondary,
                  transition: 'width 0.5s ease',
                }}
              />
            </div>
          </div>
        </div>

        <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
          <GameButton model={model} size="large" fullWidth onClick={() => onNavigate('lobby')}>
            Continuar
          </GameButton>
          <GameButton model={model} size="medium" fullWidth variant="secondary" onClick={() => onNavigate('matchmaking')}>
            Jugar de Nuevo
          </GameButton>
        </div>
      </div>
    </div>
  );
}

function StatCard({
  icon,
  label,
  value,
  positive,
  model,
}: {
  icon: React.ReactNode;
  label: string;
  value: string;
  positive: boolean;
  model: ModelKey;
}) {
  const theme = visualModels[model];

  return (
    <div style={{ textAlign: 'center' }}>
      <div
        style={{
          color: positive ? theme.colors.success : theme.colors.danger,
          display: 'flex',
          justifyContent: 'center',
          marginBottom: '8px',
        }}
      >
        {icon}
      </div>
      <div
        style={{
          fontFamily: theme.fonts.heading,
          fontSize: '20px',
          fontWeight: 700,
          color: theme.colors.text,
          marginBottom: '4px',
        }}
      >
        {value}
      </div>
      <div
        style={{
          fontFamily: theme.fonts.body,
          fontSize: '12px',
          color: theme.colors.textSecondary,
        }}
      >
        {label}
      </div>
    </div>
  );
}
