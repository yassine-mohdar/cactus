import { UserProfile } from '../types';
import { apiFetch, getApiBaseUrl } from './apiClient';

export interface SyncResult {
  ok: boolean;
  error?: 'name_taken' | 'email_taken' | 'unknown';
}

export async function syncPlayerToServer(profile: UserProfile): Promise<SyncResult> {
  try {
    const res = await apiFetch('/players/sync', {
      method: 'POST',
      body: JSON.stringify({
        id: profile.id,
        name: profile.name,
        phoneNumber: profile.phoneNumber ?? null,
        country: profile.country ?? null,
        characterId: profile.selectedCharacterId,
        hasSubscription: profile.hasSubscription,
        stats: profile.stats,
        earnedBadgeIds: profile.earnedBadgeIds,
      }),
    });

    if (res.ok) return { ok: true };

    if (res.status === 409) {
      const body = await res.json().catch(() => ({}));
      const field = (body as { field?: string }).field;
      if (field === 'email') return { ok: false, error: 'email_taken' };
      return { ok: false, error: 'name_taken' };
    }

    return { ok: false, error: 'unknown' };
  } catch {
    return { ok: false, error: 'unknown' };
  }
}

export interface LeaderboardEntry {
  id: string;
  name: string;
  characterId: string;
  hasSubscription: boolean;
  totalWins: number;
  totalLosses: number;
  totalDraws: number;
  totalMatches: number;
  streak: number;
  bestStreak: number;
  winRate: number;
}

export async function fetchLeaderboard(limit = 20): Promise<LeaderboardEntry[]> {
  const base = getApiBaseUrl();
  const res = await fetch(`${base}/leaderboard?limit=${limit}`);
  if (!res.ok) throw new Error('Failed to fetch leaderboard');
  return res.json();
}
