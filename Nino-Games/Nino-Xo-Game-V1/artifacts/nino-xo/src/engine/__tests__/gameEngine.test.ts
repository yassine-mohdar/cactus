import {
  EMPTY_BOARD,
  applyMove,
  checkDraw,
  checkWinner,
  getGameResult,
  getLegalMoves,
  isValidMove,
  willWinWithMove,
} from '../gameEngine';
import type { Board, PlayerSymbol } from '../../types';

describe('gameEngine', () => {
  describe('checkWinner', () => {
    it('detects horizontal win for X', () => {
      const board: Board = ['X', 'X', 'X', null, null, null, null, null, null];
      const { winner, line } = checkWinner(board);
      expect(winner).toBe('X');
      expect(line).toEqual([0, 1, 2]);
    });

    it('detects horizontal win for O', () => {
      const board: Board = [null, null, null, 'O', 'O', 'O', null, null, null];
      const { winner, line } = checkWinner(board);
      expect(winner).toBe('O');
      expect(line).toEqual([3, 4, 5]);
    });

    it('detects vertical win', () => {
      const board: Board = ['X', null, null, 'X', null, null, 'X', null, null];
      const { winner, line } = checkWinner(board);
      expect(winner).toBe('X');
      expect(line).toEqual([0, 3, 6]);
    });

    it('detects diagonal win top-left to bottom-right', () => {
      const board: Board = ['X', null, null, null, 'X', null, null, null, 'X'];
      const { winner, line } = checkWinner(board);
      expect(winner).toBe('X');
      expect(line).toEqual([0, 4, 8]);
    });

    it('detects diagonal win top-right to bottom-left', () => {
      const board: Board = [null, null, 'O', null, 'O', null, 'O', null, null];
      const { winner, line } = checkWinner(board);
      expect(winner).toBe('O');
      expect(line).toEqual([2, 4, 6]);
    });

    it('returns null winner for empty board', () => {
      const { winner, line } = checkWinner(EMPTY_BOARD);
      expect(winner).toBeNull();
      expect(line).toBeNull();
    });

    it('returns null winner for incomplete game', () => {
      const board: Board = ['X', 'O', 'X', null, null, null, null, null, null];
      const { winner } = checkWinner(board);
      expect(winner).toBeNull();
    });
  });

  describe('checkDraw', () => {
    it('returns false for empty board', () => {
      expect(checkDraw(EMPTY_BOARD)).toBe(false);
    });

    it('returns true for full board with no winner', () => {
      const board: Board = ['X', 'O', 'X', 'X', 'O', 'X', 'O', 'X', 'O'];
      expect(checkDraw(board)).toBe(true);
    });

    it('returns false for partially filled board', () => {
      const board: Board = ['X', 'O', null, 'X', 'O', 'X', 'O', 'X', 'O'];
      expect(checkDraw(board)).toBe(false);
    });
  });

  describe('getGameResult', () => {
    it('returns X_WIN', () => {
      const board: Board = ['X', 'X', 'X', null, null, null, null, null, null];
      const { result } = getGameResult(board);
      expect(result).toBe('X_WIN');
    });

    it('returns O_WIN', () => {
      const board: Board = [null, null, null, 'O', 'O', 'O', null, null, null];
      const { result } = getGameResult(board);
      expect(result).toBe('O_WIN');
    });

    it('returns DRAW', () => {
      const board: Board = ['X', 'O', 'X', 'X', 'O', 'X', 'O', 'X', 'O'];
      const { result } = getGameResult(board);
      expect(result).toBe('DRAW');
    });

    it('returns null for ongoing game', () => {
      const { result } = getGameResult(EMPTY_BOARD);
      expect(result).toBeNull();
    });
  });

  describe('isValidMove', () => {
    it('returns true for empty cell', () => {
      expect(isValidMove(EMPTY_BOARD, 4)).toBe(true);
    });

    it('returns false for occupied cell', () => {
      const board: Board = ['X', null, null, null, null, null, null, null, null];
      expect(isValidMove(board, 0)).toBe(false);
    });

    it('returns false for out-of-range index', () => {
      expect(isValidMove(EMPTY_BOARD, -1)).toBe(false);
      expect(isValidMove(EMPTY_BOARD, 9)).toBe(false);
    });
  });

  describe('applyMove', () => {
    it('places symbol at index', () => {
      const board = applyMove(EMPTY_BOARD, 4, 'X');
      expect(board[4]).toBe('X');
      expect(board[0]).toBeNull();
    });

    it('does not mutate original board', () => {
      const original = [...EMPTY_BOARD] as Board;
      applyMove(EMPTY_BOARD, 0, 'X');
      expect(EMPTY_BOARD[0]).toBeNull();
    });

    it('throws on occupied cell', () => {
      const board: Board = ['X', null, null, null, null, null, null, null, null];
      expect(() => applyMove(board, 0, 'O')).toThrow();
    });
  });

  describe('getLegalMoves', () => {
    it('returns all 9 indices for empty board', () => {
      expect(getLegalMoves(EMPTY_BOARD)).toHaveLength(9);
    });

    it('returns empty for full board', () => {
      const board: Board = ['X', 'O', 'X', 'X', 'O', 'X', 'O', 'X', 'O'];
      expect(getLegalMoves(board)).toHaveLength(0);
    });

    it('returns only null cells', () => {
      const board: Board = ['X', null, 'O', null, null, null, null, null, null];
      const moves = getLegalMoves(board);
      expect(moves).not.toContain(0);
      expect(moves).not.toContain(2);
      expect(moves).toContain(1);
      expect(moves).toHaveLength(7);
    });
  });

  describe('willWinWithMove', () => {
    it('returns true when X can win', () => {
      const board: Board = ['X', 'X', null, null, null, null, null, null, null];
      expect(willWinWithMove(board, 2, 'X')).toBe(true);
    });

    it('returns false when the move does not win', () => {
      const board: Board = ['X', null, null, null, null, null, null, null, null];
      expect(willWinWithMove(board, 4, 'X')).toBe(false);
    });

    it('returns false for occupied cell', () => {
      const board: Board = ['X', 'X', 'O', null, null, null, null, null, null];
      expect(willWinWithMove(board, 2, 'X')).toBe(false);
    });
  });
});
