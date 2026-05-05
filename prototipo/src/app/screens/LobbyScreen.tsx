import { visualModels, ModelKey } from '../components/VisualModels';
import { GameButton } from '../components/GameButton';
import { Home, Swords, Users, ShoppingBag, User, Trophy, Gift } from 'lucide-react';

interface LobbyScreenProps {
  model: ModelKey;
  onNavigate: (screen: string) => void;
}

export function LobbyScreen({ model, onNavigate }: LobbyScreenProps) {
  const theme = visualModels[model];

  return (
    <div
      style={{
        width: '100%',
        height: '100%',
        backgroundColor: theme.colors.background,
        display: 'flex',
        flexDirection: 'column',
      }}
    >
      <div style={{ flex: 1, overflowY: 'auto', padding: '20px' }}>
        <div
          style={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            marginBottom: '24px',
          }}
        >
          <div>
            <h2
              style={{
                fontFamily: theme.fonts.heading,
                fontSize: '24px',
                fontWeight: 700,
                color: theme.colors.text,
                marginBottom: '4px',
              }}
            >
              ¡Bienvenido, Guerrero!
            </h2>
            <p
              style={{
                fontFamily: theme.fonts.body,
                fontSize: '14px',
                color: theme.colors.textSecondary,
              }}
            >
              Listo para la batalla
            </p>
          </div>
          <div
            style={{
              backgroundColor: theme.colors.surface,
              padding: '8px 16px',
              borderRadius: theme.radius,
              display: 'flex',
              alignItems: 'center',
              gap: '8px',
            }}
          >
            <Trophy size={20} color={theme.colors.secondary} />
            <span
              style={{
                fontFamily: theme.fonts.body,
                fontSize: '16px',
                fontWeight: 700,
                color: theme.colors.text,
              }}
            >
              Rango: Oro II
            </span>
          </div>
        </div>

        <div style={{ marginBottom: '32px' }}>
          <GameButton model={model} size="large" fullWidth onClick={() => onNavigate('matchmaking')}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '8px' }}>
              <Swords size={20} />
              Jugar Ahora
            </div>
          </GameButton>
        </div>

        <div
          style={{
            backgroundColor: theme.colors.surface,
            borderRadius: theme.radius,
            padding: '20px',
            marginBottom: '24px',
          }}
        >
          <h3
            style={{
              fontFamily: theme.fonts.heading,
              fontSize: '16px',
              fontWeight: 700,
              color: theme.colors.text,
              marginBottom: '16px',
            }}
          >
            Modos de Juego
          </h3>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
            <GameModeCard
              title="Casual"
              description="Practica sin presión"
              icon={<Users size={24} />}
              model={model}
              onClick={() => onNavigate('matchmaking')}
            />
            <GameModeCard
              title="Ranked"
              description="Sube en la clasificación"
              icon={<Trophy size={24} />}
              model={model}
              highlighted
              onClick={() => onNavigate('ranked')}
            />
          </div>
        </div>

        <div
          style={{
            backgroundColor: theme.colors.surface,
            borderRadius: theme.radius,
            padding: '20px',
            marginBottom: '24px',
          }}
        >
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
            <h3
              style={{
                fontFamily: theme.fonts.heading,
                fontSize: '16px',
                fontWeight: 700,
                color: theme.colors.text,
              }}
            >
              Misiones Diarias
            </h3>
            <span style={{ color: theme.colors.accent, fontSize: '14px', fontWeight: 600 }}>2/3</span>
          </div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
            <MissionItem
              title="Gana 3 partidas"
              progress={2}
              total={3}
              reward="100 XP"
              model={model}
            />
            <MissionItem
              title="Usa 5 habilidades de defensa"
              progress={5}
              total={5}
              reward="50 XP"
              model={model}
              completed
            />
            <MissionItem
              title="Juega con 3 campeones diferentes"
              progress={1}
              total={3}
              reward="75 XP"
              model={model}
            />
          </div>
        </div>
      </div>

      <BottomNav model={model} active="home" onNavigate={onNavigate} />
    </div>
  );
}

function GameModeCard({
  title,
  description,
  icon,
  model,
  highlighted = false,
  onClick,
}: {
  title: string;
  description: string;
  icon: React.ReactNode;
  model: ModelKey;
  highlighted?: boolean;
  onClick: () => void;
}) {
  const theme = visualModels[model];

  return (
    <div
      onClick={onClick}
      style={{
        backgroundColor: highlighted ? theme.colors.primary + '20' : theme.colors.backgroundLight,
        border: highlighted ? `2px solid ${theme.colors.primary}` : `1px solid ${theme.colors.backgroundLight}`,
        borderRadius: theme.radius,
        padding: '16px',
        display: 'flex',
        alignItems: 'center',
        gap: '16px',
        cursor: 'pointer',
        transition: 'all 0.2s',
      }}
      className="hover:scale-102"
    >
      <div style={{ color: highlighted ? theme.colors.primary : theme.colors.accent }}>
        {icon}
      </div>
      <div style={{ flex: 1 }}>
        <div
          style={{
            fontFamily: theme.fonts.heading,
            fontSize: '16px',
            fontWeight: 700,
            color: theme.colors.text,
            marginBottom: '4px',
          }}
        >
          {title}
        </div>
        <div
          style={{
            fontFamily: theme.fonts.body,
            fontSize: '12px',
            color: theme.colors.textSecondary,
          }}
        >
          {description}
        </div>
      </div>
    </div>
  );
}

function MissionItem({
  title,
  progress,
  total,
  reward,
  model,
  completed = false,
}: {
  title: string;
  progress: number;
  total: number;
  reward: string;
  model: ModelKey;
  completed?: boolean;
}) {
  const theme = visualModels[model];
  const percentage = (progress / total) * 100;

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
        <span
          style={{
            fontFamily: theme.fonts.body,
            fontSize: '14px',
            color: theme.colors.text,
            textDecoration: completed ? 'line-through' : 'none',
          }}
        >
          {title}
        </span>
        <span
          style={{
            fontFamily: theme.fonts.body,
            fontSize: '12px',
            color: theme.colors.secondary,
            fontWeight: 600,
          }}
        >
          {reward}
        </span>
      </div>
      <div
        style={{
          width: '100%',
          height: '6px',
          backgroundColor: theme.colors.backgroundLight,
          borderRadius: theme.radius,
          overflow: 'hidden',
        }}
      >
        <div
          style={{
            width: `${percentage}%`,
            height: '100%',
            backgroundColor: completed ? theme.colors.success : theme.colors.accent,
            transition: 'width 0.3s ease',
          }}
        />
      </div>
    </div>
  );
}

function BottomNav({
  model,
  active,
  onNavigate,
}: {
  model: ModelKey;
  active: string;
  onNavigate: (screen: string) => void;
}) {
  const theme = visualModels[model];

  const navItems = [
    { id: 'home', icon: Home, label: 'Inicio' },
    { id: 'champions', icon: Users, label: 'Campeones' },
    { id: 'ranked', icon: Trophy, label: 'Ranked' },
    { id: 'shop', icon: ShoppingBag, label: 'Tienda' },
    { id: 'profile', icon: User, label: 'Perfil' },
  ];

  return (
    <div
      style={{
        backgroundColor: theme.colors.surface,
        borderTop: `1px solid ${theme.colors.backgroundLight}`,
        display: 'flex',
        justifyContent: 'space-around',
        padding: '12px 0',
      }}
    >
      {navItems.map((item) => (
        <button
          key={item.id}
          onClick={() => onNavigate(item.id)}
          style={{
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            gap: '4px',
            background: 'none',
            border: 'none',
            cursor: 'pointer',
            padding: '8px',
            color: active === item.id ? theme.colors.primary : theme.colors.textSecondary,
            transition: 'color 0.2s',
          }}
        >
          <item.icon size={20} />
          <span style={{ fontSize: '10px', fontFamily: theme.fonts.body }}>{item.label}</span>
        </button>
      ))}
    </div>
  );
}
