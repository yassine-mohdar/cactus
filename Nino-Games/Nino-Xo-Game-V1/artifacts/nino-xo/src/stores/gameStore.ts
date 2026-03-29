import { create } from 'zustand';
import { Board, BotDifficulty, GameMode, GameResult, GameSession, Player, PlayerSymbol, WinLine, CharacterId } from '../types';
import { EMPTY_BOARD, applyMove, getGameResult, isValidMove } from '../engine/gameEngine';
import { getBotMove } from '../engine/botEngine';
import { CHARACTERS, getCharacter } from '../data/characters';

export function pickBotCharacter(playerCharId: string): { char: ReturnType<typeof getCharacter>; isFallback: boolean } {
  const others = CHARACTERS.filter((c) => c.id !== playerCharId);
  if (others.length > 0) {
    return { char: others[Math.floor(Math.random() * others.length)], isFallback: false };
  }
  // Only one character in the roster — return it with a flag so callers can
  // apply an alternate visual label to distinguish bot from player.
  const fallback = CHARACTERS[0] ?? getCharacter('nino');
  return { char: fallback, isFallback: true };
}

interface GameState {
  session: GameSession | null;
  lastResult: GameResult;
  serverMatchId: string | null;
  _initRef: { mode: GameMode; players: [Player, Player]; difficulty?: BotDifficulty; challengeId?: string } | null;
  _botTimer: ReturnType<typeof setTimeout> | null;

  startBotGame: (playerCharId: CharacterId, playerName: string, difficulty: BotDifficulty, botCharId?: CharacterId) => void;
  startFriendGame: (p1CharId: CharacterId, p1Name: string, p2CharId: CharacterId, p2Name: string) => void;
  startQuickGame: (playerCharId: CharacterId, playerName: string, opponentName?: string, opponentCharId?: CharacterId) => void;
  startChallengeGame: (playerCharId: CharacterId, playerName: string, challengeId: string, difficulty?: BotDifficulty, matchToken?: string) => void;
  startOnlineGame: (
    myCharId: CharacterId,
    myName: string,
    mySymbol: PlayerSymbol,
    opponentCharId: CharacterId,
    opponentName: string,
    opponentSocketId: string,
    roomCode: string,
    turnTimerSec?: number | null,
    opponentProfileId?: string,
  ) => void;
  setServerMatchId: (id: string | null) => void;
  makeMove: (index: number) => void;
  forfeitGame: (loserSymbol: PlayerSymbol) => void;
  rematch: () => void;
  resetGame: () => void;
}

function makePlayer(id: string, name: string, charId: CharacterId, symbol: PlayerSymbol, isBot = false): Player {
  return { id, name, characterId: charId, symbol, isBot };
}

function newSession(mode: GameMode, players: [Player, Player], botDifficulty?: BotDifficulty): GameSession {
  return {
    mode,
    botDifficulty,
    board: [...EMPTY_BOARD] as Board,
    currentSymbol: 'X',
    players,
    result: null,
    winLine: null,
    moveCount: 0,
    isGameOver: false,
  };
}

export const useGameStore = create<GameState>((set, get) => ({
  session: null,
  lastResult: null,
  serverMatchId: null,
  _initRef: null,
  _botTimer: null,

  startBotGame: (playerCharId, playerName, difficulty, botCharId?) => {
    const { char: botChar, isFallback } = botCharId
      ? { char: getCharacter(botCharId), isFallback: botCharId === playerCharId }
      : pickBotCharacter(playerCharId);
    const botName = isFallback ? 'Bot Opponent' : botChar.name;
    const p1 = makePlayer('player', playerName, playerCharId, 'X');
    const p2 = makePlayer('bot', botName, botChar.id, 'O', true);
    const s = newSession('bot', [p1, p2], difficulty);
    set({ session: s, lastResult: null, _initRef: { mode: 'bot', players: [p1, p2], difficulty } });
  },

  startFriendGame: (p1CharId, p1Name, p2CharId, p2Name) => {
    const p1 = makePlayer('p1', p1Name, p1CharId, 'X');
    const p2 = makePlayer('p2', p2Name, p2CharId, 'O');
    const s = newSession('friend', [p1, p2]);
    set({ session: s, lastResult: null, _initRef: { mode: 'friend', players: [p1, p2] } });
  },

  startQuickGame: (playerCharId, playerName, opponentName?, opponentCharId?) => {
    const { char: botChar, isFallback } = opponentCharId
      ? { char: getCharacter(opponentCharId), isFallback: false }
      : pickBotCharacter(playerCharId);
    const resolvedCharId = botChar.id;
    const resolvedName = opponentName ?? (isFallback ? 'Bot Opponent' : botChar.name);
    const p1 = makePlayer('player', playerName, playerCharId, 'X');
    const p2 = makePlayer('bot_quick', resolvedName, resolvedCharId, 'O', true);
    const s = newSession('quick', [p1, p2], 'medium');
    set({ session: s, lastResult: null, _initRef: { mode: 'quick', players: [p1, p2], difficulty: 'medium' } });
  },

  startChallengeGame: (playerCharId, playerName, challengeId, difficulty = 'medium', matchToken?) => {
    const { char: botChar } = pickBotCharacter(playerCharId);
    const p1 = makePlayer('player', playerName, playerCharId, 'X');
    const p2 = makePlayer('bot_challenge', botChar.name, botChar.id, 'O', true);
    const s: GameSession = { ...newSession('challenge', [p1, p2], difficulty), challengeId, matchToken };
    set({ session: s, lastResult: null, _initRef: { mode: 'challenge', players: [p1, p2], difficulty, challengeId } });
  },

  setServerMatchId: (id) => set({ serverMatchId: id }),

  startOnlineGame: (myCharId, myName, mySymbol, opponentCharId, opponentName, opponentSocketId, roomCode, turnTimerSec, opponentProfileId) => {
    const opponentSymbol: PlayerSymbol = mySymbol === 'X' ? 'O' : 'X';
    const me = makePlayer('player', myName, myCharId, mySymbol);
    const opponent = makePlayer('remote', opponentName, opponentCharId, opponentSymbol);
    const players: [Player, Player] = mySymbol === 'X' ? [me, opponent] : [opponent, me];
    const s: GameSession = {
      ...newSession('online', players),
      roomCode,
      opponentSocketId,
      opponentProfileId,
      turnTimerSec: turnTimerSec ?? null,
    };
    set({ session: s, lastResult: null, _initRef: { mode: 'online', players } });
  },

  makeMove: (index: number) => {
    const { session } = get();
    if (!session || session.isGameOver) return;
    if (!isValidMove(session.board, index)) return;

    const currentPlayer = session.players.find(p => p.symbol === session.currentSymbol);
    if (currentPlayer?.isBot) return;

    const newBoard = applyMove(session.board, index, session.currentSymbol);
    const { result, winLine } = getGameResult(newBoard);
    const nextSymbol: PlayerSymbol = session.currentSymbol === 'X' ? 'O' : 'X';

    const updated: GameSession = {
      ...session,
      board: newBoard,
      currentSymbol: nextSymbol,
      result,
      winLine,
      moveCount: session.moveCount + 1,
      isGameOver: result !== null,
    };

    set({ session: updated, lastResult: result ?? get().lastResult });

    if (result === null) {
      const nextPlayer = session.players.find(p => p.symbol === nextSymbol);
      if (nextPlayer?.isBot) {
        const { _botTimer } = get();
        if (_botTimer) clearTimeout(_botTimer);
        const timer = setTimeout(() => {
          const { session: current } = get();
          if (!current || current.isGameOver) return;
          const botPlayer = current.players.find(p => p.isBot && p.symbol === current.currentSymbol);
          if (!botPlayer) return;
          const botMove = getBotMove(current.board, botPlayer.symbol, current.botDifficulty ?? 'medium');
          if (!isValidMove(current.board, botMove)) return;
          const botBoard = applyMove(current.board, botMove, botPlayer.symbol);
          const { result: botResult, winLine: botLine } = getGameResult(botBoard);
          set({
            session: {
              ...current,
              board: botBoard,
              currentSymbol: current.currentSymbol === 'X' ? 'O' : 'X',
              result: botResult,
              winLine: botLine,
              moveCount: current.moveCount + 1,
              isGameOver: botResult !== null,
            },
            lastResult: botResult ?? get().lastResult,
          });
        }, 700 + Math.random() * 400);
        set({ _botTimer: timer });
      }
    }
  },

  forfeitGame: (loserSymbol) => {
    const { session } = get();
    if (!session || session.isGameOver) return;
    const winnerSymbol: PlayerSymbol = loserSymbol === 'X' ? 'O' : 'X';
    const result: GameResult = `${winnerSymbol}_WIN`;
    set({ session: { ...session, result, isGameOver: true, winLine: null }, lastResult: result });
  },

  rematch: () => {
    const { _initRef, _botTimer } = get();
    if (_botTimer) clearTimeout(_botTimer);
    if (!_initRef) return;
    const { mode, players, difficulty, challengeId } = _initRef;
    const base = newSession(mode, players, difficulty);
    const s: GameSession = challengeId ? { ...base, challengeId } : base;
    set({ session: s, lastResult: null, serverMatchId: null });
  },

  resetGame: () => {
    const { _botTimer } = get();
    if (_botTimer) clearTimeout(_botTimer);
    set({ session: null, lastResult: null, _initRef: null, _botTimer: null, serverMatchId: null });
  },
}));
