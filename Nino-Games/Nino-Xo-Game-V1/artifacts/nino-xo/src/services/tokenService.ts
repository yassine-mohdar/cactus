import { API_BASE_URL } from '../config';

export interface AwardWinResult {
  ok: boolean;
  tokenBalance: number;
  awarded: number;
  alreadyAwarded?: boolean;
  result?: string;
}

export interface TokenTransaction {
  id: number;
  playerId: string;
  amount: number;
  type: string;
  description: string | null;
  relatedMatchId: string | null;
  createdAt: string;
}

export const TOKEN_TYPE_LABELS: Record<string, string> = {
  match_win_reward: 'Match Win',
  challenge_entry_fee: 'Challenge Entry',
  challenge_reward: 'Challenge Prize',
  admin_adjustment: 'Admin Adjustment',
  refund: 'Refund',
};

export type Cell = 'X' | 'O' | null;

export async function startMatch(
  playerId: string,
  mode: string,
  playerSymbol: 'X' | 'O',
  opponentName?: string,
  opponentCharacterId?: string,
): Promise<string | null> {
  try {
    const base = API_BASE_URL;
    const res = await fetch(`${base}/matches/start`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ playerId, mode, playerSymbol, opponentName, opponentCharacterId }),
    });
    if (!res.ok) return null;
    const data = await res.json();
    return data.matchId ?? null;
  } catch {
    return null;
  }
}

export async function recordMatchResult(
  matchId: string,
  playerId: string,
  board: Cell[],
): Promise<string | null> {
  try {
    const base = API_BASE_URL;
    const res = await fetch(`${base}/matches/record-result`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ matchId, playerId, board }),
    });
    if (!res.ok) return null;
    const data = await res.json();
    return data.result ?? null;
  } catch {
    return null;
  }
}

export async function awardWinTokens(
  playerId: string,
  matchId: string,
): Promise<AwardWinResult> {
  try {
    const base = API_BASE_URL;
    const res = await fetch(`${base}/tokens/award-win`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ playerId, matchId }),
    });
    if (!res.ok) return { ok: false, tokenBalance: 0, awarded: 0 };
    const data = await res.json();
    return {
      ok: data.ok ?? false,
      tokenBalance: data.tokenBalance ?? 0,
      awarded: data.awarded ?? 0,
      alreadyAwarded: data.alreadyAwarded ?? false,
      result: data.result,
    };
  } catch {
    return { ok: false, tokenBalance: 0, awarded: 0 };
  }
}

export async function fetchTokenBalance(playerId: string): Promise<number | null> {
  try {
    const base = API_BASE_URL;
    const res = await fetch(`${base}/tokens/balance/${encodeURIComponent(playerId)}`);
    if (!res.ok) return null;
    const data = await res.json();
    return data.tokenBalance ?? null;
  } catch {
    return null;
  }
}

export async function fetchTokenHistory(playerId: string, limit = 30): Promise<TokenTransaction[]> {
  try {
    const base = API_BASE_URL;
    const res = await fetch(`${base}/tokens/history/${encodeURIComponent(playerId)}?limit=${limit}`);
    if (!res.ok) return [];
    return res.json();
  } catch {
    return [];
  }
}
