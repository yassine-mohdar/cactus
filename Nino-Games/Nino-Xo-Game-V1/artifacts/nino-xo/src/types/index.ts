import { ImageSourcePropType } from 'react-native';

export type CharacterId =
  | 'nino'
  | 'blue_detective'
  | 'pink'
  | 'blue_succulent'
  | 'scientist'
  | 'yellow';

export type PlayerSymbol = 'X' | 'O';

export type Cell = PlayerSymbol | null;
export type Board = [Cell, Cell, Cell, Cell, Cell, Cell, Cell, Cell, Cell];

export type GameResult = 'X_WIN' | 'O_WIN' | 'DRAW' | null;

export type WinLine = [number, number, number] | null;

export type GameMode = 'bot' | 'friend' | 'quick' | 'online' | 'challenge';
export type BotDifficulty = 'easy' | 'medium' | 'hard';

export interface Character {
  id: CharacterId;
  name: string;
  personality: string;
  flavor: string;
  idleMood: string;
  accentColor: string;
  accentPale: string;
  image: ImageSourcePropType;
  reactions: CharacterReactions;
}

export interface CharacterReactions {
  win: string;
  lose: string;
  draw: string;
  place: string;
  block: string;
  search: string;
  matchFound: string;
  rematch: string;
}

export interface Player {
  id: string;
  name: string;
  characterId: CharacterId;
  symbol: PlayerSymbol;
  isBot?: boolean;
}

export interface GameSession {
  mode: GameMode;
  botDifficulty?: BotDifficulty;
  board: Board;
  currentSymbol: PlayerSymbol;
  players: [Player, Player];
  result: GameResult;
  winLine: WinLine;
  moveCount: number;
  isGameOver: boolean;
  roomCode?: string;
  opponentSocketId?: string;
  opponentProfileId?: string;
  turnTimerSec?: number | null;
  challengeId?: string;
  matchToken?: string;
}

export interface MatchResult {
  winner: PlayerSymbol | 'DRAW';
  mySymbol: PlayerSymbol;
  streak: number;
  missionsProgress: number;
}

export interface UserStats {
  totalMatches: number;
  totalWins: number;
  totalLosses: number;
  totalDraws: number;
  streak: number;
  bestStreak: number;
  missionProgress: number;
  missionGoal: number;
  missionsCompletedTotal?: number;
  characterWins?: Record<string, number>;
}

export interface UserProfile {
  id: string;
  name: string;
  email?: string;
  password?: string;
  phoneNumber?: string;
  country?: string;
  selectedCharacterId: CharacterId;
  stats: UserStats;
  hasSubscription: boolean;
  earnedBadgeIds: string[];
  tokenBalance: number;
}

export type ThemePreference = 'system' | 'light' | 'dark';

export interface AppSettings {
  soundEnabled: boolean;
  musicEnabled: boolean;
  theme: ThemePreference;
}
