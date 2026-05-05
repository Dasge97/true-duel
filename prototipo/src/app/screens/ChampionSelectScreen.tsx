import { useState } from 'react';
import { visualModels, ModelKey } from '../components/VisualModels';
import { ChampionCard } from '../components/ChampionCard';
import { GameButton } from '../components/GameButton';
import { ArrowLeft } from 'lucide-react';

interface ChampionSelectScreenProps {
  model: ModelKey;
  onNavigate: (screen: string) => void;
}

const champions = [
  { id: 1, name: 'Titan', rank: 1, winRate: 64, attack: 95, defense: 70, speed: 60 },
  { id: 2, name: 'Sombra', rank: 3, winRate: 58, attack: 85, defense: 55, speed: 90 },
  { id: 3, name: 'Guardian', rank: 5, winRate: 62, attack: 70, defense: 95, speed: 50 },
  { id: 4, name: 'Viento', rank: 7, winRate: 55, attack: 75, defense: 60, speed: 95 },
  { id: 5, name: 'Roca', rank: 12, winRate: 51, attack: 80, defense: 85, speed: 45 },
  { id: 6, name: 'Relámpago', rank: 8, winRate: 57, attack: 90, defense: 50, speed: 85 },
];

export function ChampionSelectScreen({ model, onNavigate }: ChampionSelectScreenProps) {
  const [selected, setSelected] = useState<number | null>(null);
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
      <div
        style={{
          backgroundColor: theme.colors.surface,
          padding: '16px 20px',
          display: 'flex',
          alignItems: 'center',
          gap: '16px',
          borderBottom: `1px solid ${theme.colors.backgroundLight}`,
        }}
      >
        <button
          onClick={() => onNavigate('lobby')}
          style={{
            background: 'none',
            border: 'none',
            color: theme.colors.text,
            cursor: 'pointer',
            padding: '8px',
          }}
        >
          <ArrowLeft size={24} />
        </button>
        <div>
          <h2
            style={{
              fontFamily: theme.fonts.heading,
              fontSize: '20px',
              fontWeight: 700,
              color: theme.colors.text,
              marginBottom: '4px',
            }}
          >
            Selecciona tu Campeón
          </h2>
          <p
            style={{
              fontFamily: theme.fonts.body,
              fontSize: '12px',
              color: theme.colors.textSecondary,
            }}
          >
            Elige sabiamente
          </p>
        </div>
      </div>

      <div
        style={{
          flex: 1,
          overflowY: 'auto',
          padding: '20px',
        }}
      >
        <div style={{ display: 'grid', gridTemplateColumns: '1fr', gap: '16px' }}>
          {champions.map((champion) => (
            <ChampionCard
              key={champion.id}
              {...champion}
              model={model}
              selected={selected === champion.id}
              onClick={() => setSelected(champion.id)}
            />
          ))}
        </div>
      </div>

      <div
        style={{
          backgroundColor: theme.colors.surface,
          padding: '20px',
          borderTop: `1px solid ${theme.colors.backgroundLight}`,
        }}
      >
        <GameButton
          model={model}
          size="large"
          fullWidth
          disabled={!selected}
          onClick={() => onNavigate('combat')}
        >
          Confirmar Selección
        </GameButton>
      </div>
    </div>
  );
}
