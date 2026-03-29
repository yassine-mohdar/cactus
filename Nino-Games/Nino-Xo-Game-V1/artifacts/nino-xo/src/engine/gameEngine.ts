import { Board, Cell, GameResult, PlayerSymbol, WinLine } from '../types';

export const EMPTY_BOARD: Board = [null, null, null, null, null, null, null, null, null];

const WIN_LINES: Array<[number, number, number]> = [
  [0, 1, 2],
  [3, 4, 5],
  [6, 7, 8],
  [0, 3, 6],
  [1, 4, 7],
  [2, 5, 8],
  [0, 4, 8],
  [2, 4, 6],
];

export function checkWinner(board: Board): { winner: PlayerSymbol | null; line: WinLine } {
  for (const line of WIN_LINES) {
    const [a, b, c] = line;
    if (board[a] && board[a] === board[b] && board[a] === board[c]) {
      return { winner: board[a] as PlayerSymbol, line };
    }
  }
  return { winner: null, line: null };
}

export function checkDraw(board: Board): boolean {
  return board.every(cell => cell !== null);
}

export function getGameResult(board: Board): { result: GameResult; winLine: WinLine } {
  const { winner, line } = checkWinner(board);
  if (winner) {
    return { result: winner === 'X' ? 'X_WIN' : 'O_WIN', winLine: line };
  }
  if (checkDraw(board)) {
    return { result: 'DRAW', winLine: null };
  }
  return { result: null, winLine: null };
}

export function isValidMove(board: Board, index: number): boolean {
  return index >= 0 && index < 9 && board[index] === null;
}

export function applyMove(board: Board, index: number, symbol: PlayerSymbol): Board {
  if (!isValidMove(board, index)) {
    throw new Error(`Invalid move: cell ${index} is already occupied`);
  }
  const newBoard = [...board] as Board;
  newBoard[index] = symbol;
  return newBoard;
}

export function getLegalMoves(board: Board): number[] {
  return board.map((cell, i) => (cell === null ? i : -1)).filter(i => i !== -1);
}

export function getOpponentSymbol(symbol: PlayerSymbol): PlayerSymbol {
  return symbol === 'X' ? 'O' : 'X';
}

export function willWinWithMove(board: Board, index: number, symbol: PlayerSymbol): boolean {
  if (!isValidMove(board, index)) return false;
  const testBoard = applyMove(board, index, symbol);
  const { winner } = checkWinner(testBoard);
  return winner === symbol;
}
