import { Board, BotDifficulty, PlayerSymbol } from '../types';
import {
  getLegalMoves,
  willWinWithMove,
  getOpponentSymbol,
  checkWinner,
  applyMove,
} from './gameEngine';

export function getBotMove(board: Board, botSymbol: PlayerSymbol, difficulty: BotDifficulty): number {
  const legalMoves = getLegalMoves(board);
  if (legalMoves.length === 0) throw new Error('No legal moves available');

  if (difficulty === 'easy') return getRandomMove(legalMoves);
  if (difficulty === 'medium') return getMediumMove(board, botSymbol, legalMoves);
  return getHardMove(board, botSymbol, legalMoves);
}

function getRandomMove(legalMoves: number[]): number {
  return legalMoves[Math.floor(Math.random() * legalMoves.length)];
}

function getMediumMove(board: Board, botSymbol: PlayerSymbol, legalMoves: number[]): number {
  const opponent = getOpponentSymbol(botSymbol);

  for (const move of legalMoves) {
    if (willWinWithMove(board, move, botSymbol)) return move;
  }

  for (const move of legalMoves) {
    if (willWinWithMove(board, move, opponent)) return move;
  }

  if (legalMoves.includes(4)) return 4;

  const corners = [0, 2, 6, 8].filter(c => legalMoves.includes(c));
  if (corners.length > 0) return getRandomMove(corners);

  return getRandomMove(legalMoves);
}

// ── Hard mode: minimax (perfect play — never loses) ────────────────────────

function minimax(
  board: Board,
  isMaximizing: boolean,
  botSymbol: PlayerSymbol,
  opponentSymbol: PlayerSymbol,
  depth: number,
): number {
  const { winner } = checkWinner(board);
  if (winner === botSymbol) return 10 - depth;
  if (winner === opponentSymbol) return depth - 10;

  const moves = getLegalMoves(board);
  if (moves.length === 0) return 0;

  if (isMaximizing) {
    let best = -Infinity;
    for (const move of moves) {
      const next = applyMove(board, move, botSymbol);
      best = Math.max(best, minimax(next, false, botSymbol, opponentSymbol, depth + 1));
    }
    return best;
  } else {
    let best = Infinity;
    for (const move of moves) {
      const next = applyMove(board, move, opponentSymbol);
      best = Math.min(best, minimax(next, true, botSymbol, opponentSymbol, depth + 1));
    }
    return best;
  }
}

function getHardMove(board: Board, botSymbol: PlayerSymbol, legalMoves: number[]): number {
  const opponent = getOpponentSymbol(botSymbol);
  let bestScore = -Infinity;
  let bestMove = legalMoves[0];

  for (const move of legalMoves) {
    const next = applyMove(board, move, botSymbol);
    const score = minimax(next, false, botSymbol, opponent, 0);
    if (score > bestScore) {
      bestScore = score;
      bestMove = move;
    }
  }

  return bestMove;
}
