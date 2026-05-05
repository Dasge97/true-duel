import { visualModels, ModelKey } from './VisualModels';

interface GameButtonProps {
  variant?: 'primary' | 'secondary' | 'ghost' | 'danger';
  size?: 'small' | 'medium' | 'large';
  disabled?: boolean;
  loading?: boolean;
  onClick?: () => void;
  children: React.ReactNode;
  model: ModelKey;
  fullWidth?: boolean;
}

export function GameButton({
  variant = 'primary',
  size = 'medium',
  disabled = false,
  loading = false,
  onClick,
  children,
  model,
  fullWidth = false,
}: GameButtonProps) {
  const theme = visualModels[model];

  const getBackgroundColor = () => {
    if (disabled) return '#4a5568';
    switch (variant) {
      case 'primary':
        return theme.colors.primary;
      case 'secondary':
        return theme.colors.secondary;
      case 'danger':
        return theme.colors.danger;
      case 'ghost':
        return 'transparent';
      default:
        return theme.colors.primary;
    }
  };

  const getTextColor = () => {
    if (variant === 'ghost') return theme.colors.primary;
    return variant === 'secondary' ? '#000000' : '#ffffff';
  };

  const getPadding = () => {
    switch (size) {
      case 'small':
        return '8px 16px';
      case 'large':
        return '16px 32px';
      default:
        return '12px 24px';
    }
  };

  return (
    <button
      onClick={onClick}
      disabled={disabled || loading}
      style={{
        backgroundColor: getBackgroundColor(),
        color: getTextColor(),
        padding: getPadding(),
        borderRadius: theme.radius,
        border: variant === 'ghost' ? `2px solid ${theme.colors.primary}` : 'none',
        fontFamily: theme.fonts.body,
        fontSize: size === 'large' ? '18px' : size === 'small' ? '14px' : '16px',
        fontWeight: 600,
        cursor: disabled || loading ? 'not-allowed' : 'pointer',
        opacity: disabled ? 0.5 : 1,
        transition: 'all 0.2s',
        width: fullWidth ? '100%' : 'auto',
        textTransform: 'uppercase',
        letterSpacing: '0.5px',
      }}
      className="hover:opacity-90 active:scale-95"
    >
      {loading ? 'Cargando...' : children}
    </button>
  );
}
