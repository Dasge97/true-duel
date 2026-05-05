import { visualModels, ModelKey } from './VisualModels';
import { Sword, Shield, Zap } from 'lucide-react';

interface ChampionCardProps {
  name: string;
  rank: number;
  winRate: number;
  attack: number;
  defense: number;
  speed: number;
  model: ModelKey;
  selected?: boolean;
  onClick?: () => void;
}

export function ChampionCard({
  name,
  rank,
  winRate,
  attack,
  defense,
  speed,
  model,
  selected = false,
  onClick,
}: ChampionCardProps) {
  const theme = visualModels[model];

  return (
    <div
      onClick={onClick}
      style={{
        backgroundColor: selected ? theme.colors.primary + '20' : theme.colors.surface,
        border: `2px solid ${selected ? theme.colors.primary : theme.colors.backgroundLight}`,
        borderRadius: theme.radius,
        padding: '16px',
        cursor: onClick ? 'pointer' : 'default',
        transition: 'all 0.2s',
        boxShadow: selected && model === 'b' ? `0 0 20px ${theme.colors.primary}` : 'none',
      }}
      className="hover:scale-105"
    >
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '12px' }}>
        <div>
          <h3
            style={{
              color: theme.colors.text,
              fontFamily: theme.fonts.heading,
              fontSize: '18px',
              fontWeight: 700,
              marginBottom: '4px',
            }}
          >
            {name}
          </h3>
          <div
            style={{
              color: theme.colors.textSecondary,
              fontFamily: theme.fonts.body,
              fontSize: '12px',
            }}
          >
            Rango #{rank}
          </div>
        </div>
        <div
          style={{
            backgroundColor: theme.colors.success + '30',
            color: theme.colors.success,
            padding: '4px 8px',
            borderRadius: theme.radius,
            fontSize: '12px',
            fontWeight: 600,
          }}
        >
          {winRate}% WR
        </div>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '12px' }}>
        <StatItem icon={<Sword size={16} />} label="ATK" value={attack} theme={theme} />
        <StatItem icon={<Shield size={16} />} label="DEF" value={defense} theme={theme} />
        <StatItem icon={<Zap size={16} />} label="VEL" value={speed} theme={theme} />
      </div>
    </div>
  );
}

function StatItem({ icon, label, value, theme }: { icon: React.ReactNode; label: string; value: number; theme: typeof visualModels.a }) {
  return (
    <div style={{ textAlign: 'center' }}>
      <div style={{ color: theme.colors.accent, display: 'flex', justifyContent: 'center', marginBottom: '4px' }}>
        {icon}
      </div>
      <div style={{ color: theme.colors.textSecondary, fontSize: '10px', marginBottom: '2px' }}>
        {label}
      </div>
      <div style={{ color: theme.colors.text, fontSize: '14px', fontWeight: 700 }}>
        {value}
      </div>
    </div>
  );
}
