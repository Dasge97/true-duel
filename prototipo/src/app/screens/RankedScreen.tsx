import { visualModels, ModelKey } from '../components/VisualModels';
import { HealthBar } from '../components/HealthBar';
import { Home, Swords, Users, ShoppingBag, User, Trophy, Medal, ChevronRight } from 'lucide-react';

interface RankedScreenProps {
  model: ModelKey;
  onNavigate: (screen: string) => void;
}

const leaderboard = [
  { rank: 1, name: 'DragonKing', lp: 2450, wins: 156, losses: 44 },
  { rank: 2, name: 'ShadowMaster', lp: 2380, wins: 142, losses: 58 },
  { rank: 3, name: 'StormBringer', lp: 2290, wins: 138, losses: 62 },
  { rank: 4, name: 'PhoenixFire', lp: 2210, wins: 129, losses: 71 },
  { rank: 5, name: 'IceQueen', lp: 2150, wins: 125, losses: 75 },
  { rank: 15, name: 'Tú', lp: 1875, wins: 98, losses: 52, isPlayer: true },
];

export function RankedScreen({ model, onNavigate }: RankedScreenProps) {
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
        <h2
          style={{
            fontFamily: theme.fonts.heading,
            fontSize: '24px',
            fontWeight: 700,
            color: theme.colors.text,
            marginBottom: '24px',
            display: 'flex',
            alignItems: 'center',
            gap: '12px',
          }}
        >
          <Trophy size={28} color={theme.colors.secondary} />
          Clasificación Global
        </h2>

        <div
          style={{
            backgroundColor: theme.colors.surface,
            borderRadius: theme.radius,
            padding: '20px',
            marginBottom: '24px',
          }}
        >
          <div style={{ marginBottom: '16px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '12px' }}>
              <div>
                <div
                  style={{
                    color: theme.colors.text,
                    fontFamily: theme.fonts.heading,
                    fontSize: '20px',
                    fontWeight: 700,
                  }}
                >
                  Oro II
                </div>
                <div
                  style={{
                    color: theme.colors.textSecondary,
                    fontSize: '12px',
                  }}
                >
                  Rango #15 Global
                </div>
              </div>
              <div
                style={{
                  backgroundColor: theme.colors.secondary + '30',
                  color: theme.colors.secondary,
                  padding: '8px 16px',
                  borderRadius: theme.radius,
                  fontWeight: 700,
                }}
              >
                1,875 LP
              </div>
            </div>
            <HealthBar current={75} max={100} label="Progreso a Oro I" model={model} variant="rank" />
          </div>

          <div
            style={{
              display: 'grid',
              gridTemplateColumns: 'repeat(2, 1fr)',
              gap: '12px',
              paddingTop: '16px',
              borderTop: `1px solid ${theme.colors.backgroundLight}`,
            }}
          >
            <div>
              <div style={{ color: theme.colors.textSecondary, fontSize: '12px', marginBottom: '4px' }}>
                Victorias
              </div>
              <div style={{ color: theme.colors.success, fontSize: '20px', fontWeight: 700 }}>
                98
              </div>
            </div>
            <div>
              <div style={{ color: theme.colors.textSecondary, fontSize: '12px', marginBottom: '4px' }}>
                Derrotas
              </div>
              <div style={{ color: theme.colors.danger, fontSize: '20px', fontWeight: 700 }}>
                52
              </div>
            </div>
          </div>
        </div>

        <div style={{ marginBottom: '24px' }}>
          <h3
            style={{
              fontFamily: theme.fonts.heading,
              fontSize: '18px',
              fontWeight: 700,
              color: theme.colors.text,
              marginBottom: '16px',
            }}
          >
            Top Global
          </h3>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
            {leaderboard.map((player) => (
              <div
                key={player.rank}
                style={{
                  backgroundColor: player.isPlayer ? theme.colors.primary + '20' : theme.colors.surface,
                  border: player.isPlayer ? `2px solid ${theme.colors.primary}` : 'none',
                  borderRadius: theme.radius,
                  padding: '16px',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '16px',
                }}
              >
                <div
                  style={{
                    width: '40px',
                    height: '40px',
                    backgroundColor: player.rank <= 3 ? theme.colors.secondary : theme.colors.backgroundLight,
                    borderRadius: '50%',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    fontFamily: theme.fonts.heading,
                    fontSize: '18px',
                    fontWeight: 700,
                    color: player.rank <= 3 ? '#000' : theme.colors.text,
                  }}
                >
                  {player.rank <= 3 ? <Medal size={20} /> : player.rank}
                </div>
                <div style={{ flex: 1 }}>
                  <div
                    style={{
                      color: theme.colors.text,
                      fontFamily: theme.fonts.body,
                      fontSize: '16px',
                      fontWeight: 600,
                      marginBottom: '4px',
                    }}
                  >
                    {player.name}
                  </div>
                  <div
                    style={{
                      color: theme.colors.textSecondary,
                      fontSize: '12px',
                    }}
                  >
                    {player.wins}V - {player.losses}D
                  </div>
                </div>
                <div
                  style={{
                    color: theme.colors.secondary,
                    fontFamily: theme.fonts.heading,
                    fontSize: '16px',
                    fontWeight: 700,
                  }}
                >
                  {player.lp} LP
                </div>
              </div>
            ))}
          </div>
        </div>

        <button
          onClick={() => onNavigate('champions')}
          style={{
            width: '100%',
            backgroundColor: theme.colors.surface,
            border: `1px solid ${theme.colors.backgroundLight}`,
            borderRadius: theme.radius,
            padding: '16px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            cursor: 'pointer',
            marginBottom: '20px',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <Users size={24} color={theme.colors.accent} />
            <div>
              <div
                style={{
                  color: theme.colors.text,
                  fontFamily: theme.fonts.body,
                  fontSize: '16px',
                  fontWeight: 600,
                  textAlign: 'left',
                }}
              >
                Rankings por Campeón
              </div>
              <div
                style={{
                  color: theme.colors.textSecondary,
                  fontSize: '12px',
                  textAlign: 'left',
                }}
              >
                Ver estadísticas individuales
              </div>
            </div>
          </div>
          <ChevronRight size={20} color={theme.colors.textSecondary} />
        </button>
      </div>

      <BottomNav model={model} active="ranked" onNavigate={onNavigate} />
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
