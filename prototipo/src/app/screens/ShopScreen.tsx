import { visualModels, ModelKey } from '../components/VisualModels';
import { GameButton } from '../components/GameButton';
import { Home, Swords, Users, ShoppingBag, User, Trophy, Sparkles, Shirt, Palette } from 'lucide-react';

interface ShopScreenProps {
  model: ModelKey;
  onNavigate: (screen: string) => void;
}

const shopItems = [
  { id: 1, name: 'Skin Dorada', type: 'skin', price: 500, category: 'premium' },
  { id: 2, name: 'Efecto de Victoria', type: 'effect', price: 300, category: 'effect' },
  { id: 3, name: 'Avatar Legendario', type: 'avatar', price: 200, category: 'cosmetic' },
  { id: 4, name: 'Skin Platino', type: 'skin', price: 800, category: 'premium' },
  { id: 5, name: 'Emote Victoria', type: 'emote', price: 150, category: 'cosmetic' },
  { id: 6, name: 'Borde de Perfil', type: 'border', price: 250, category: 'cosmetic' },
];

export function ShopScreen({ model, onNavigate }: ShopScreenProps) {
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
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '24px' }}>
          <h2
            style={{
              fontFamily: theme.fonts.heading,
              fontSize: '24px',
              fontWeight: 700,
              color: theme.colors.text,
            }}
          >
            Tienda Cosmética
          </h2>
          <div
            style={{
              backgroundColor: theme.colors.secondary + '30',
              color: theme.colors.secondary,
              padding: '8px 16px',
              borderRadius: theme.radius,
              fontWeight: 700,
              display: 'flex',
              alignItems: 'center',
              gap: '8px',
            }}
          >
            <Sparkles size={16} />
            2,450
          </div>
        </div>

        <div
          style={{
            backgroundColor: theme.colors.primary + '20',
            border: `2px solid ${theme.colors.primary}`,
            borderRadius: theme.radius,
            padding: '16px',
            marginBottom: '24px',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <Sparkles size={32} color={theme.colors.primary} />
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
                Pack Exclusivo del Mes
              </div>
              <div
                style={{
                  fontSize: '12px',
                  color: theme.colors.textSecondary,
                }}
              >
                3 skins + 2 efectos por tiempo limitado
              </div>
            </div>
            <div
              style={{
                backgroundColor: theme.colors.primary,
                color: '#ffffff',
                padding: '8px 16px',
                borderRadius: theme.radius,
                fontWeight: 700,
              }}
            >
              1,500
            </div>
          </div>
        </div>

        <div style={{ marginBottom: '16px' }}>
          <h3
            style={{
              fontFamily: theme.fonts.heading,
              fontSize: '18px',
              fontWeight: 700,
              color: theme.colors.text,
              marginBottom: '16px',
            }}
          >
            Artículos Destacados
          </h3>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '12px' }}>
            {shopItems.map((item) => (
              <ShopItem key={item.id} item={item} model={model} />
            ))}
          </div>
        </div>
      </div>

      <BottomNav model={model} active="shop" onNavigate={onNavigate} />
    </div>
  );
}

function ShopItem({
  item,
  model,
}: {
  item: { id: number; name: string; type: string; price: number; category: string };
  model: ModelKey;
}) {
  const theme = visualModels[model];

  const getIcon = () => {
    switch (item.type) {
      case 'skin':
        return <Shirt size={32} />;
      case 'effect':
        return <Sparkles size={32} />;
      case 'avatar':
        return <User size={32} />;
      default:
        return <Palette size={32} />;
    }
  };

  return (
    <div
      style={{
        backgroundColor: theme.colors.surface,
        borderRadius: theme.radius,
        padding: '16px',
        display: 'flex',
        flexDirection: 'column',
        gap: '12px',
      }}
    >
      <div
        style={{
          width: '100%',
          height: '100px',
          backgroundColor: theme.colors.backgroundLight,
          borderRadius: theme.radius,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          color: item.category === 'premium' ? theme.colors.secondary : theme.colors.accent,
        }}
      >
        {getIcon()}
      </div>
      <div>
        <div
          style={{
            fontFamily: theme.fonts.body,
            fontSize: '14px',
            fontWeight: 600,
            color: theme.colors.text,
            marginBottom: '4px',
          }}
        >
          {item.name}
        </div>
        <div
          style={{
            fontSize: '11px',
            color: theme.colors.textSecondary,
            textTransform: 'uppercase',
            marginBottom: '8px',
          }}
        >
          {item.type}
        </div>
      </div>
      <button
        style={{
          backgroundColor: theme.colors.secondary,
          color: '#000000',
          padding: '8px',
          borderRadius: theme.radius,
          border: 'none',
          fontWeight: 700,
          fontSize: '12px',
          cursor: 'pointer',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          gap: '6px',
        }}
        className="hover:opacity-90"
      >
        <Sparkles size={14} />
        {item.price}
      </button>
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
