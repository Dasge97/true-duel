import { visualModels, ModelKey } from '../components/VisualModels';
import { HealthBar } from '../components/HealthBar';
import { Home, Swords, Users, ShoppingBag, User, Trophy, Award, Target, Clock } from 'lucide-react';

interface ProfileScreenProps {
  model: ModelKey;
  onNavigate: (screen: string) => void;
}

export function ProfileScreen({ model, onNavigate }: ProfileScreenProps) {
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
            backgroundColor: theme.colors.surface,
            borderRadius: theme.radius,
            padding: '24px',
            marginBottom: '24px',
            textAlign: 'center',
          }}
        >
          <div
            style={{
              width: '80px',
              height: '80px',
              backgroundColor: theme.colors.primary,
              borderRadius: '50%',
              margin: '0 auto 16px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              fontSize: '32px',
              fontWeight: 700,
              color: '#ffffff',
            }}
          >
            G
          </div>
          <h2
            style={{
              fontFamily: theme.fonts.heading,
              fontSize: '24px',
              fontWeight: 700,
              color: theme.colors.text,
              marginBottom: '8px',
            }}
          >
            Guerrero
          </h2>
          <div
            style={{
              color: theme.colors.textSecondary,
              fontSize: '14px',
              marginBottom: '16px',
            }}
          >
            Miembro desde Enero 2026
          </div>
          <div style={{ display: 'flex', justifyContent: 'center', gap: '16px' }}>
            <div>
              <div style={{ color: theme.colors.secondary, fontSize: '20px', fontWeight: 700 }}>
                Nivel 42
              </div>
              <div style={{ color: theme.colors.textSecondary, fontSize: '12px' }}>
                Nivel
              </div>
            </div>
            <div
              style={{
                width: '1px',
                backgroundColor: theme.colors.backgroundLight,
              }}
            />
            <div>
              <div style={{ color: theme.colors.accent, fontSize: '20px', fontWeight: 700 }}>
                150
              </div>
              <div style={{ color: theme.colors.textSecondary, fontSize: '12px' }}>
                Partidas
              </div>
            </div>
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
          <h3
            style={{
              fontFamily: theme.fonts.heading,
              fontSize: '18px',
              fontWeight: 700,
              color: theme.colors.text,
              marginBottom: '16px',
            }}
          >
            Progreso de Nivel
          </h3>
          <HealthBar current={3450} max={5000} label="Experiencia" model={model} variant="rank" />
          <div
            style={{
              marginTop: '12px',
              color: theme.colors.textSecondary,
              fontSize: '12px',
            }}
          >
            1,550 XP hasta el siguiente nivel
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
          <h3
            style={{
              fontFamily: theme.fonts.heading,
              fontSize: '18px',
              fontWeight: 700,
              color: theme.colors.text,
              marginBottom: '16px',
            }}
          >
            Estadísticas
          </h3>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '16px' }}>
            <StatCard
              icon={<Trophy size={24} />}
              label="Victorias"
              value="98"
              color={theme.colors.success}
              theme={theme}
            />
            <StatCard
              icon={<Target size={24} />}
              label="Ratio Victoria"
              value="65%"
              color={theme.colors.accent}
              theme={theme}
            />
            <StatCard
              icon={<Award size={24} />}
              label="Mejor Racha"
              value="12"
              color={theme.colors.secondary}
              theme={theme}
            />
            <StatCard
              icon={<Clock size={24} />}
              label="Tiempo Total"
              value="48h"
              color={theme.colors.primary}
              theme={theme}
            />
          </div>
        </div>

        <div
          style={{
            backgroundColor: theme.colors.surface,
            borderRadius: theme.radius,
            padding: '20px',
          }}
        >
          <h3
            style={{
              fontFamily: theme.fonts.heading,
              fontSize: '18px',
              fontWeight: 700,
              color: theme.colors.text,
              marginBottom: '16px',
            }}
          >
            Logros Recientes
          </h3>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
            <Achievement
              title="Primera Victoria"
              description="Gana tu primera partida"
              unlocked
              model={model}
            />
            <Achievement
              title="Racha de 5"
              description="Gana 5 partidas seguidas"
              unlocked
              model={model}
            />
            <Achievement
              title="Maestro de Campeones"
              description="Juega con 10 campeones diferentes"
              unlocked={false}
              progress="7/10"
              model={model}
            />
          </div>
        </div>
      </div>

      <BottomNav model={model} active="profile" onNavigate={onNavigate} />
    </div>
  );
}

function StatCard({
  icon,
  label,
  value,
  color,
  theme,
}: {
  icon: React.ReactNode;
  label: string;
  value: string;
  color: string;
  theme: typeof visualModels.a;
}) {
  return (
    <div
      style={{
        backgroundColor: theme.colors.backgroundLight,
        borderRadius: theme.radius,
        padding: '16px',
        textAlign: 'center',
      }}
    >
      <div style={{ color, marginBottom: '8px', display: 'flex', justifyContent: 'center' }}>
        {icon}
      </div>
      <div
        style={{
          fontFamily: theme.fonts.heading,
          fontSize: '24px',
          fontWeight: 700,
          color: theme.colors.text,
          marginBottom: '4px',
        }}
      >
        {value}
      </div>
      <div
        style={{
          fontSize: '12px',
          color: theme.colors.textSecondary,
        }}
      >
        {label}
      </div>
    </div>
  );
}

function Achievement({
  title,
  description,
  unlocked,
  progress,
  model,
}: {
  title: string;
  description: string;
  unlocked: boolean;
  progress?: string;
  model: ModelKey;
}) {
  const theme = visualModels[model];

  return (
    <div
      style={{
        display: 'flex',
        alignItems: 'center',
        gap: '12px',
        padding: '12px',
        backgroundColor: theme.colors.backgroundLight,
        borderRadius: theme.radius,
        opacity: unlocked ? 1 : 0.6,
      }}
    >
      <div
        style={{
          width: '48px',
          height: '48px',
          backgroundColor: unlocked ? theme.colors.secondary : theme.colors.surface,
          borderRadius: '50%',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
        }}
      >
        <Award size={24} color={unlocked ? '#000' : theme.colors.textSecondary} />
      </div>
      <div style={{ flex: 1 }}>
        <div
          style={{
            fontFamily: theme.fonts.body,
            fontSize: '14px',
            fontWeight: 600,
            color: theme.colors.text,
            marginBottom: '4px',
          }}
        >
          {title}
        </div>
        <div
          style={{
            fontSize: '12px',
            color: theme.colors.textSecondary,
          }}
        >
          {description}
        </div>
        {progress && (
          <div
            style={{
              fontSize: '11px',
              color: theme.colors.accent,
              marginTop: '4px',
            }}
          >
            {progress}
          </div>
        )}
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
    { id: 'home', icon: Home, label: 'Inicio', screen: 'lobby' },
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
          onClick={() => onNavigate(item.screen || item.id)}
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
