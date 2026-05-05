import { visualModels, ModelKey } from './VisualModels';

interface StatusChipProps {
  label: string;
  variant: 'buff' | 'debuff' | 'neutral' | 'charge';
  model: ModelKey;
}

export function StatusChip({ label, variant, model }: StatusChipProps) {
  const theme = visualModels[model];

  const getColors = () => {
    switch (variant) {
      case 'buff':
        return { bg: theme.colors.success + '30', text: theme.colors.success };
      case 'debuff':
        return { bg: theme.colors.danger + '30', text: theme.colors.danger };
      case 'charge':
        return { bg: theme.colors.accent + '30', text: theme.colors.accent };
      default:
        return { bg: theme.colors.surface, text: theme.colors.text };
    }
  };

  const colors = getColors();

  return (
    <div
      style={{
        backgroundColor: colors.bg,
        color: colors.text,
        padding: '6px 12px',
        borderRadius: theme.radius,
        fontSize: '12px',
        fontWeight: 600,
        fontFamily: theme.fonts.body,
        display: 'inline-block',
        border: model === 'c' ? `1px solid ${colors.text}` : 'none',
        textTransform: 'uppercase',
        letterSpacing: '0.5px',
      }}
    >
      {label}
    </div>
  );
}
