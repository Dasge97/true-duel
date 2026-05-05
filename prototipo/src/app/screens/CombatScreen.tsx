import { useState } from 'react';
import { visualModels, ModelKey } from '../components/VisualModels';
import { HealthBar } from '../components/HealthBar';
import { StatusChip } from '../components/StatusChip';
import { TurnTimer } from '../components/TurnTimer';
import { GameButton } from '../components/GameButton';
import { Sword, Shield, Zap } from 'lucide-react';

interface CombatScreenProps {
  model: ModelKey;
  onNavigate: (screen: string) => void;
}

export function CombatScreen({ model, onNavigate }: CombatScreenProps) {
  const [playerHealth] = useState(75);
  const [enemyHealth] = useState(60);
  const [charges] = useState(3);
  const [timeLeft] = useState(15);
  const theme = visualModels[model];

  return (
    <div
      style={{
        width: '100%',
        height: '100%',
        backgroundColor: theme.colors.background,
        display: 'flex',
        flexDirection: 'column',
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
          background: `radial-gradient(circle at 50% 30%, ${theme.colors.danger}10, transparent 60%)`,
          pointerEvents: 'none',
        }}
      />

      <div style={{ position: 'relative', padding: '20px', flex: 1, display: 'flex', flexDirection: 'column' }}>
        <div
          style={{
            backgroundColor: theme.colors.surface + 'cc',
            borderRadius: theme.radius,
            padding: '16px',
            marginBottom: '20px',
            backdropFilter: 'blur(10px)',
          }}
        >
          <div style={{ marginBottom: '8px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <span style={{ color: theme.colors.text, fontSize: '14px', fontWeight: 600 }}>Enemigo</span>
            <div style={{ display: 'flex', gap: '4px' }}>
              <StatusChip label="Debuff" variant="debuff" model={model} />
            </div>
          </div>
          <HealthBar current={enemyHealth} max={100} model={model} variant="health" />
        </div>

        <div style={{ flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
          <div style={{ textAlign: 'center' }}>
            <div style={{ marginBottom: '16px' }}>
              <TurnTimer timeLeft={timeLeft} maxTime={30} model={model} />
            </div>
            <div
              style={{
                color: theme.colors.text,
                fontFamily: theme.fonts.heading,
                fontSize: '18px',
                fontWeight: 700,
                marginBottom: '8px',
              }}
            >
              Tu Turno
            </div>
            <div
              style={{
                color: theme.colors.textSecondary,
                fontFamily: theme.fonts.body,
                fontSize: '14px',
              }}
            >
              Selecciona una acción
            </div>
          </div>
        </div>

        <div
          style={{
            backgroundColor: theme.colors.surface + 'cc',
            borderRadius: theme.radius,
            padding: '16px',
            marginBottom: '20px',
            backdropFilter: 'blur(10px)',
          }}
        >
          <div style={{ marginBottom: '8px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <span style={{ color: theme.colors.text, fontSize: '14px', fontWeight: 600 }}>Tú</span>
            <div style={{ display: 'flex', gap: '4px' }}>
              <StatusChip label="Cargas +1" variant="charge" model={model} />
              <StatusChip label="Bloqueo" variant="buff" model={model} />
            </div>
          </div>
          <HealthBar current={playerHealth} max={100} model={model} variant="health" />
          <div style={{ marginTop: '12px' }}>
            <HealthBar current={charges} max={5} label="Cargas" model={model} variant="energy" />
          </div>
        </div>

        <div
          style={{
            backgroundColor: theme.colors.surface + 'cc',
            borderRadius: theme.radius,
            padding: '16px',
            backdropFilter: 'blur(10px)',
          }}
        >
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '12px' }}>
            <ActionButton
              icon={<Sword size={24} />}
              label="Atacar"
              cost={1}
              model={model}
              onClick={() => {}}
            />
            <ActionButton
              icon={<Shield size={24} />}
              label="Defender"
              cost={1}
              model={model}
              onClick={() => {}}
            />
            <ActionButton
              icon={<Zap size={24} />}
              label="Especial"
              cost={3}
              model={model}
              disabled={charges < 3}
              onClick={() => {}}
            />
          </div>
        </div>

        <div style={{ marginTop: '16px' }}>
          <GameButton model={model} fullWidth variant="ghost" onClick={() => onNavigate('result')}>
            Rendirse
          </GameButton>
        </div>
      </div>
    </div>
  );
}

function ActionButton({
  icon,
  label,
  cost,
  model,
  disabled = false,
  onClick,
}: {
  icon: React.ReactNode;
  label: string;
  cost: number;
  model: ModelKey;
  disabled?: boolean;
  onClick: () => void;
}) {
  const theme = visualModels[model];

  return (
    <button
      onClick={onClick}
      disabled={disabled}
      style={{
        backgroundColor: disabled ? theme.colors.backgroundLight : theme.colors.primary,
        border: `2px solid ${disabled ? theme.colors.surface : theme.colors.primary}`,
        borderRadius: theme.radius,
        padding: '16px 8px',
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        gap: '8px',
        cursor: disabled ? 'not-allowed' : 'pointer',
        opacity: disabled ? 0.5 : 1,
        transition: 'all 0.2s',
        color: theme.colors.text,
      }}
      className={disabled ? '' : 'hover:scale-105 active:scale-95'}
    >
      <div style={{ color: disabled ? theme.colors.textSecondary : theme.colors.text }}>
        {icon}
      </div>
      <div
        style={{
          fontFamily: theme.fonts.body,
          fontSize: '12px',
          fontWeight: 600,
          textTransform: 'uppercase',
        }}
      >
        {label}
      </div>
      <div
        style={{
          fontSize: '10px',
          color: theme.colors.textSecondary,
        }}
      >
        {cost} carga{cost !== 1 ? 's' : ''}
      </div>
    </button>
  );
}
