import React, { createContext, useContext, ReactNode } from 'react';
import { useGameStore } from '../stores/gameStore';
import { BotDifficulty, CharacterId, GameResult } from '../types';

interface GameContextValue {
  session: ReturnType<typeof useGameStore>['session'];
  lastResult: GameResult;
  startBotGame: (playerCharId: CharacterId, playerName: string, difficulty: BotDifficulty, botCharId?: CharacterId) => void;
  startFriendGame: (p1CharId: CharacterId, p1Name: string, p2CharId: CharacterId, p2Name: string) => void;
  startQuickGame: (playerCharId: CharacterId, playerName: string, opponentName?: string, opponentCharId?: CharacterId) => void;
  startChallengeGame: (playerCharId: CharacterId, playerName: string, challengeId: string, difficulty?: BotDifficulty, matchToken?: string) => void;
  startOnlineGame: ReturnType<typeof useGameStore>['startOnlineGame'];
  makeMove: (index: number) => void;
  forfeitGame: ReturnType<typeof useGameStore>['forfeitGame'];
  rematch: () => void;
  resetGame: () => void;
}

const GameContext = createContext<GameContextValue | null>(null);

export function GameProvider({ children }: { children: ReactNode }) {
  const store = useGameStore();

  const value: GameContextValue = {
    session: store.session,
    lastResult: store.lastResult,
    startBotGame: store.startBotGame,
    startFriendGame: store.startFriendGame,
    startQuickGame: store.startQuickGame,
    startChallengeGame: store.startChallengeGame,
    startOnlineGame: store.startOnlineGame,
    makeMove: store.makeMove,
    forfeitGame: store.forfeitGame,
    rematch: store.rematch,
    resetGame: store.resetGame,
  };

  return <GameContext.Provider value={value}>{children}</GameContext.Provider>;
}

export function useGame() {
  const ctx = useContext(GameContext);
  if (!ctx) throw new Error('useGame must be used inside GameProvider');
  return ctx;
}
