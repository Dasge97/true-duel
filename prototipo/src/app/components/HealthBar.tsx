import { visualModels, ModelKey } from './VisualModels';

interface HealthBarProps {
  current: number;
  max: number;
  label?: string;
  model: ModelKey;
  variant?: 'health' | 'energy' | 'rank' | 'mission';
}

export function HealthBar({ current, max, label, model, variant = 'health' }: HealthBarProps) {
  const theme = visualModels[model];
  const percentage = (current / max) * 100;

  const getBarColor = () => {
    switch (variant) {
      case 'health':
        return percentage > 50 ? theme.colors.success : percentage > 25 ? theme.colors.warning : theme.colors.danger;
      case 'energy':
        return theme.colors.accent;
      case 'rank':
        return theme.colors.secondary;
      case 'mission':
        return theme.colors.primary;
      default:
        return theme.colors.primary;
    }
  };

  return (
    <div style={{ width: '100%' }}>
      {label && (
        <div
          style={{
            color: theme.colors.text,
            fontFamily: theme.fonts.body,
            fontSize: '12px',
            marginBottom: '4px',
            display: 'flex',
            justifyContent: 'space-between',
          }}
        >
          <span>{label}</span>
          <span>{current}/{max}</span>
        </div>
      )}
      <div
        style={{
          width: '100%',
          height: variant === 'health' ? '12px' : '8px',
          backgroundColor: theme.colors.surface,
          borderRadius: theme.radius,
          overflow: 'hidden',
          border: `1px solid ${theme.colors.backgroundLight}`,
        }}
      >
        <div
          style={{
            width: `${percentage}%`,
            height: '100%',
            backgroundColor: getBarColor(),
            transition: 'width 0.3s ease',
            boxShadow: model === 'b' ? `0 0 10px ${getBarColor()}` : 'none',
          }}
        />
      </div>
    </div>
  );
}
