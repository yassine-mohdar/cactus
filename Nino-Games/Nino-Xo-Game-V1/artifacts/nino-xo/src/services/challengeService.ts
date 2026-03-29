import { apiFetch, getApiBaseUrl } from './apiClient';

function getApiBase(): string {
  return getApiBaseUrl();
}

export interface Challenge {
  id: string;
  title: string;
  description: string | null;
  entryFee: number;
  prizePool: number;
  rank1Reward: number | null;
  rank2Reward: number | null;
  rank3Reward: number | null;
  status: 'upcoming' | 'active' | 'completed' | 'cancelled';
  startAt: string;
  endAt: string;
  maxParticipants: number | null;
  rewardsDistributed: boolean;
  createdBy: string;
  createdAt: string;
  participantCount: number;
}

export interface LeaderboardEntry {
  rank: number;
  playerId: string;
  name: string;
  wins: number;
  losses: number;
  matches: number;
}

export interface RewardInfo {
  amount: number;
  rank: number;
}

export interface ChallengeDetail extends Challenge {
  leaderboard: LeaderboardEntry[];
  isJoined: boolean;
  myStats: LeaderboardEntry | null;
  rewardReceived: RewardInfo | null;
}

export interface AdminChallenge extends Challenge {
  topLeaderboard: LeaderboardEntry[];
}

/** A challenge the player participated in (from /challenges/player/:playerId) */
export interface PlayerChallenge extends Challenge {
  myWins: number;
  myLosses: number;
  rewardReceived: RewardInfo | null;
}

export async function fetchChallenges(): Promise<Challenge[]> {
  try {
    const res = await fetch(`${getApiBase()}/challenges`);
    if (!res.ok) return [];
    return res.json();
  } catch {
    return [];
  }
}

export async function fetchAllChallenges(): Promise<Challenge[]> {
  try {
    const res = await fetch(`${getApiBase()}/challenges/all`);
    if (!res.ok) return [];
    return res.json();
  } catch {
    return [];
  }
}

export async function fetchPlayerChallenges(playerId: string): Promise<PlayerChallenge[]> {
  try {
    const res = await fetch(`${getApiBase()}/challenges/player/${encodeURIComponent(playerId)}`);
    if (!res.ok) return [];
    return res.json();
  } catch {
    return [];
  }
}

export async function fetchChallengeDetail(
  challengeId: string,
  playerId?: string,
): Promise<ChallengeDetail | null> {
  try {
    const url = playerId
      ? `${getApiBase()}/challenges/${encodeURIComponent(challengeId)}?playerId=${encodeURIComponent(playerId)}`
      : `${getApiBase()}/challenges/${encodeURIComponent(challengeId)}`;
    const res = await fetch(url);
    if (!res.ok) return null;
    return res.json();
  } catch {
    return null;
  }
}

export interface JoinResult {
  ok: boolean;
  tokenBalance?: number;
  error?: string;
}

export async function joinChallenge(
  challengeId: string,
  playerId: string,
): Promise<JoinResult> {
  try {
    const res = await apiFetch(`/challenges/${encodeURIComponent(challengeId)}/join`, {
      method: 'POST',
      body: JSON.stringify({ playerId }),
    });
    const data = await res.json();
    if (!res.ok) {
      return { ok: false, error: data.error ?? 'Failed to join' };
    }
    return { ok: true, tokenBalance: data.tokenBalance };
  } catch {
    return { ok: false, error: 'Network error' };
  }
}

/**
 * Request a server-issued one-time match token before a challenge game starts.
 */
export async function startChallengeMatch(
  challengeId: string,
  participantId: string,
): Promise<string | null> {
  try {
    const res = await apiFetch(
      `/challenges/${encodeURIComponent(challengeId)}/start-match`,
      {
        method: 'POST',
        body: JSON.stringify({ participantId }),
      },
    );
    if (!res.ok) return null;
    const data = await res.json() as { matchToken: string };
    return data.matchToken;
  } catch {
    return null;
  }
}

/**
 * Record a match result for a challenge game.
 */
export async function recordChallengeMatch(
  challengeId: string,
  participantId: string,
  won: boolean,
  isDraw: boolean,
  matchToken: string,
): Promise<boolean> {
  try {
    const res = await apiFetch(
      `/challenges/${encodeURIComponent(challengeId)}/record-match`,
      {
        method: 'POST',
        body: JSON.stringify({ participantId, won, isDraw, matchToken }),
      },
    );
    return res.ok;
  } catch {
    return false;
  }
}

// ── Admin functions (require JWT with admin role) ─────────────────────────────

export async function fetchAdminChallenges(): Promise<AdminChallenge[]> {
  try {
    const res = await apiFetch('/admin/challenges');
    if (!res.ok) return [];
    return res.json();
  } catch {
    return [];
  }
}

export async function createChallenge(
  params: {
    title: string;
    description?: string;
    entryFee: number;
    prizePool: number;
    rank1Reward?: number;
    rank2Reward?: number;
    rank3Reward?: number;
    startsAt: string;
    endsAt: string;
    maxParticipants?: number;
  },
): Promise<Challenge | null> {
  try {
    const res = await apiFetch('/admin/challenges', {
      method: 'POST',
      body: JSON.stringify(params),
    });
    if (!res.ok) return null;
    return res.json();
  } catch {
    return null;
  }
}

export async function updateChallenge(
  id: string,
  params: Partial<{
    title: string;
    description: string;
    entryFee: number;
    prizePool: number;
    rank1Reward: number;
    rank2Reward: number;
    rank3Reward: number;
    startsAt: string;
    endsAt: string;
    maxParticipants: number;
    status: string;
  }>,
): Promise<Challenge | null> {
  try {
    const res = await apiFetch(`/admin/challenges/${encodeURIComponent(id)}`, {
      method: 'PATCH',
      body: JSON.stringify(params),
    });
    if (!res.ok) return null;
    return res.json();
  } catch {
    return null;
  }
}

export async function completeChallenge(id: string): Promise<{ ok: boolean; rewards?: { playerId: string; name: string; rank: number; amount: number }[] }> {
  try {
    const res = await apiFetch(`/admin/challenges/${encodeURIComponent(id)}/complete`, {
      method: 'POST',
    });
    if (!res.ok) return { ok: false };
    return res.json();
  } catch {
    return { ok: false };
  }
}

export async function cancelChallenge(id: string): Promise<boolean> {
  try {
    const res = await apiFetch(`/admin/challenges/${encodeURIComponent(id)}/cancel`, {
      method: 'POST',
    });
    return res.ok;
  } catch {
    return false;
  }
}
