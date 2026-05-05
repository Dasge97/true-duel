import { visualModels, ModelKey } from './VisualModels';

interface TurnTimerProps {
  timeLeft: number;
  maxTime: number;
  model: ModelKey;
}

export function TurnTimer({ timeLeft, maxTime, model }: TurnTimerProps) {
  const theme = visualModels[model];
  const percentage = (timeLeft / maxTime) * 100;
  const radius = 40;
  const circumference = 2 * Math.PI * radius;
  const strokeDashoffset = circumference - (percentage / 100) * circumference;

  const getColor = () => {
    if (timeLeft <= 5) return theme.colors.danger;
    if (timeLeft <= 10) return theme.colors.warning;
    return theme.colors.accent;
  };

  return (
    <div style={{ position: 'relative', width: '100px', height: '100px', display: 'inline-block' }}>
      <svg width="100" height="100" style={{ transform: 'rotate(-90deg)' }}>
        <circle
          cx="50"
          cy="50"
          r={radius}
          fill="none"
          stroke={theme.colors.surface}
          strokeWidth="8"
        />
        <circle
          cx="50"
          cy="50"
          r={radius}
          fill="none"
          stroke={getColor()}
          strokeWidth="8"
          strokeDasharray={circumference}
          strokeDashoffset={strokeDashoffset}
          strokeLinecap="round"
          style={{ transition: 'stroke-dashoffset 1s linear' }}
        />
      </svg>
      <div
        style={{
          position: 'absolute',
          top: '50%',
          left: '50%',
          transform: 'translate(-50%, -50%)',
          color: theme.colors.text,
          fontFamily: theme.fonts.heading,
          fontSize: '28px',
          fontWeight: 700,
        }}
      >
        {timeLeft}
      </div>
    </div>
  );
}
