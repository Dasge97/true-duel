import { useState } from 'react';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@radix-ui/react-tabs';
import { visualModels, ModelKey } from './components/VisualModels';
import { SplashScreen } from './screens/SplashScreen';
import { LobbyScreen } from './screens/LobbyScreen';
import { ChampionSelectScreen } from './screens/ChampionSelectScreen';
import { MatchmakingScreen } from './screens/MatchmakingScreen';
import { CombatScreen } from './screens/CombatScreen';
import { ResultScreen } from './screens/ResultScreen';
import { RankedScreen } from './screens/RankedScreen';
import { ProfileScreen } from './screens/ProfileScreen';
import { ShopScreen } from './screens/ShopScreen';

export default function App() {
  const [model, setModel] = useState<ModelKey>('a');
  const [screen, setScreen] = useState('splash');

  const theme = visualModels[model];

  const renderScreen = () => {
    switch (screen) {
      case 'splash':
        return <SplashScreen model={model} onStart={() => setScreen('lobby')} />;
      case 'lobby':
      case 'home':
        return <LobbyScreen model={model} onNavigate={setScreen} />;
      case 'champions':
        return <ChampionSelectScreen model={model} onNavigate={setScreen} />;
      case 'matchmaking':
        return <MatchmakingScreen model={model} onNavigate={setScreen} />;
      case 'combat':
        return <CombatScreen model={model} onNavigate={setScreen} />;
      case 'result':
        return <ResultScreen model={model} onNavigate={setScreen} />;
      case 'ranked':
        return <RankedScreen model={model} onNavigate={setScreen} />;
      case 'profile':
        return <ProfileScreen model={model} onNavigate={setScreen} />;
      case 'shop':
        return <ShopScreen model={model} onNavigate={setScreen} />;
      default:
        return <LobbyScreen model={model} onNavigate={setScreen} />;
    }
  };

  return (
    <div className="size-full" style={{ backgroundColor: theme.colors.background }}>
      <Tabs value={model} onValueChange={(value) => setModel(value as ModelKey)} className="size-full flex flex-col">
        <TabsList
          style={{
            backgroundColor: theme.colors.surface,
            borderBottom: `2px solid ${theme.colors.backgroundLight}`,
            display: 'flex',
            padding: '8px',
            gap: '4px',
          }}
        >
          {Object.entries(visualModels).map(([key, value]) => (
            <TabsTrigger
              key={key}
              value={key}
              style={{
                flex: 1,
                padding: '12px 8px',
                backgroundColor: model === key ? theme.colors.primary : 'transparent',
                color: model === key ? '#ffffff' : theme.colors.textSecondary,
                border: 'none',
                borderRadius: theme.radius,
                fontFamily: theme.fonts.body,
                fontSize: '12px',
                fontWeight: 600,
                cursor: 'pointer',
                transition: 'all 0.2s',
                textTransform: 'uppercase',
                letterSpacing: '0.5px',
              }}
              className="hover:opacity-80"
            >
              {value.name}
            </TabsTrigger>
          ))}
        </TabsList>

        {Object.keys(visualModels).map((key) => (
          <TabsContent key={key} value={key} style={{ flex: 1, overflow: 'hidden' }}>
            <div
              style={{
                width: '100%',
                height: '100%',
                maxWidth: '420px',
                margin: '0 auto',
                backgroundColor: theme.colors.background,
                boxShadow: '0 0 40px rgba(0,0,0,0.5)',
              }}
            >
              {renderScreen()}
            </div>
          </TabsContent>
        ))}
      </Tabs>
    </div>
  );
}