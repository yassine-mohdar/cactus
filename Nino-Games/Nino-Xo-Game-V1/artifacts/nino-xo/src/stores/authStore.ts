import { create } from 'zustand';
import { CharacterId, UserProfile, UserStats } from '../types';
import { badgesEarned } from '../data/badges';
import { syncPlayerToServer, SyncResult } from '../services/playerSyncService';
import {
  getStoredJwt,
  storeJwt,
  clearJwt,
  decodeJwtPayload,
  isJwtExpired,
  apiFetch,
} from '../services/apiClient';

const DEFAULT_STATS: UserStats = {
  totalMatches: 0,
  totalWins: 0,
  totalLosses: 0,
  totalDraws: 0,
  streak: 0,
  bestStreak: 0,
  missionProgress: 0,
  missionGoal: 5,
};

interface AuthState {
  profile: UserProfile | null;
  isAdmin: boolean;
  isLoading: boolean;
  setLoading: (v: boolean) => void;
  loginWithToken: (jwt: string, playerProfile: UserProfile) => Promise<void>;
  logout: () => Promise<void>;
  restoreSession: () => Promise<void>;
  setSubscription: (hasSubscription: boolean) => void;
  updateCharacter: (id: CharacterId) => void;
  updateName: (name: string) => Promise<SyncResult>;
  updateEmail: (email: string) => Promise<SyncResult>;
  updatePassword: (password: string) => void;
  updatePhoneNumber: (phoneNumber: string) => void;
  updateCountry: (country: string) => void;
  updateStats: (won: boolean, isDraw?: boolean) => void;
  earnBadges: (ids: string[]) => void;
  pendingBadgeIds: string[];
  clearPendingBadges: () => void;
  setTokenBalance: (balance: number) => void;
}

export const useAuthStore = create<AuthState>()((set, get) => ({
  profile: null,
  isAdmin: false,
  isLoading: true,
  pendingBadgeIds: [],

  setLoading: (v) => set({ isLoading: v }),

  restoreSession: async () => {
    try {
      const token = await getStoredJwt();
      if (!token || isJwtExpired(token)) {
        await clearJwt();
        set({ profile: null, isAdmin: false, isLoading: false });
        return;
      }

      const payload = decodeJwtPayload(token);
      const playerId = payload?.['playerId'] as string | undefined;
      const isAdminFromJwt = !!(payload?.['isAdmin'] as boolean | undefined);

      if (!playerId) {
        await clearJwt();
        set({ profile: null, isAdmin: false, isLoading: false });
        return;
      }

      const { profile } = get();
      if (profile && profile.id === playerId) {
        set({ isAdmin: isAdminFromJwt, isLoading: false });
        syncPlayerToServer(profile);
        return;
      }

      const res = await apiFetch('/auth/me');

      if (!res.ok) {
        await clearJwt();
        set({ profile: null, isAdmin: false, isLoading: false });
        return;
      }

      const data = await res.json() as {
        isAdmin: boolean;
        player: {
          id: string;
          name: string;
          characterId: string;
          tokenBalance: number;
          totalMatches: number;
          totalWins: number;
          totalLosses: number;
          totalDraws: number;
          streak: number;
          bestStreak: number;
          missionProgress: number;
          missionGoal: number;
          earnedBadgeIds: string[];
          hasSubscription: boolean;
        };
      };

      const p = data.player;
      const restoredProfile: UserProfile = {
        id: p.id,
        name: p.name,
        selectedCharacterId: (p.characterId as UserProfile['selectedCharacterId']) ?? 'nino',
        hasSubscription: p.hasSubscription ?? false,
        stats: {
          totalMatches: p.totalMatches ?? 0,
          totalWins: p.totalWins ?? 0,
          totalLosses: p.totalLosses ?? 0,
          totalDraws: p.totalDraws ?? 0,
          streak: p.streak ?? 0,
          bestStreak: p.bestStreak ?? 0,
          missionProgress: p.missionProgress ?? 0,
          missionGoal: p.missionGoal ?? 5,
        },
        earnedBadgeIds: p.earnedBadgeIds ?? [],
        tokenBalance: p.tokenBalance ?? 0,
      };

      set({ profile: restoredProfile, isAdmin: data.isAdmin, isLoading: false });
    } catch {
      set({ profile: null, isAdmin: false, isLoading: false });
    }
  },

  loginWithToken: async (jwt: string, playerProfile: UserProfile) => {
    await storeJwt(jwt);
    const payload = decodeJwtPayload(jwt);
    const isAdmin = !!(payload?.['isAdmin'] as boolean | undefined);
    set({ profile: playerProfile, isAdmin, isLoading: false });
    syncPlayerToServer(playerProfile);
  },

  logout: async () => {
    await clearJwt();
    set({ profile: null, isAdmin: false, isLoading: false });
  },

  setSubscription: (hasSubscription: boolean) => {
    const { profile } = get();
    if (!profile) return;
    set({ profile: { ...profile, hasSubscription } });
  },

  updateCharacter: (id: CharacterId) => {
    const { profile } = get();
    if (!profile) return;
    const updated = { ...profile, selectedCharacterId: id };
    set({ profile: updated });
    syncPlayerToServer(updated);
  },

  updateName: async (name: string): Promise<SyncResult> => {
    const { profile } = get();
    if (!profile) return { ok: false, error: 'unknown' };
    const updated = { ...profile, name };
    const result = await syncPlayerToServer(updated);
    if (result.ok) {
      set({ profile: updated });
    }
    return result;
  },

  updateEmail: async (email: string): Promise<SyncResult> => {
    const { profile } = get();
    if (!profile) return { ok: false, error: 'unknown' };
    const updated = { ...profile, email };
    const result = await syncPlayerToServer(updated);
    if (result.ok) {
      set({ profile: updated });
    }
    return result;
  },

  updatePassword: (password: string) => {
    const { profile } = get();
    if (!profile) return;
    set({ profile: { ...profile, password } });
  },

  updatePhoneNumber: (phoneNumber: string) => {
    const { profile } = get();
    if (!profile) return;
    const updated = { ...profile, phoneNumber };
    set({ profile: updated });
    syncPlayerToServer(updated);
  },

  updateCountry: (country: string) => {
    const { profile } = get();
    if (!profile) return;
    const updated = { ...profile, country };
    set({ profile: updated });
    syncPlayerToServer(updated);
  },

  updateStats: (won: boolean, isDraw = false) => {
    const { profile } = get();
    if (!profile) return;
    const s = profile.stats;
    const newStreak = won ? s.streak + 1 : 0;
    const prevCharWins = s.characterWins ?? {};
    const charId = profile.selectedCharacterId;
    const updatedCharWins = won
      ? { ...prevCharWins, [charId]: (prevCharWins[charId] ?? 0) + 1 }
      : prevCharWins;
    const rawNextProgress = s.missionProgress + 1;
    const missionJustCompleted = rawNextProgress >= s.missionGoal;
    const nextMissionProgress = missionJustCompleted ? 0 : rawNextProgress;
    const updatedStats: UserStats = {
      totalMatches: s.totalMatches + 1,
      totalWins: won ? s.totalWins + 1 : s.totalWins,
      totalLosses: (!won && !isDraw) ? (s.totalLosses ?? 0) + 1 : (s.totalLosses ?? 0),
      totalDraws: isDraw ? (s.totalDraws ?? 0) + 1 : (s.totalDraws ?? 0),
      streak: newStreak,
      bestStreak: Math.max(s.bestStreak, newStreak),
      missionProgress: nextMissionProgress,
      missionGoal: s.missionGoal,
      missionsCompletedTotal: (s.missionsCompletedTotal ?? 0) + (missionJustCompleted ? 1 : 0),
      characterWins: updatedCharWins,
    };
    const updatedProfile: UserProfile = { ...profile, stats: updatedStats };
    const currentEarned = profile.earnedBadgeIds ?? [];
    const currentEarnedSet = new Set(currentEarned);
    const newBadgeIds = badgesEarned(s, updatedStats, profile, updatedProfile)
      .filter((id) => !currentEarnedSet.has(id));
    const nextEarned = newBadgeIds.length > 0
      ? [...currentEarned, ...newBadgeIds]
      : currentEarned;
    const finalProfile = { ...updatedProfile, earnedBadgeIds: nextEarned };
    set({
      profile: finalProfile,
      pendingBadgeIds: newBadgeIds,
    });
    syncPlayerToServer(finalProfile);
  },

  earnBadges: (ids: string[]) => {
    const { profile } = get();
    if (!profile || ids.length === 0) return;
    const current = new Set(profile.earnedBadgeIds ?? []);
    const fresh = ids.filter((id) => !current.has(id));
    if (fresh.length === 0) return;
    const updated = [...current, ...fresh];
    set({
      profile: { ...profile, earnedBadgeIds: updated },
      pendingBadgeIds: fresh,
    });
  },

  clearPendingBadges: () => set({ pendingBadgeIds: [] }),

  setTokenBalance: (balance: number) => {
    const { profile } = get();
    if (!profile) return;
    set({ profile: { ...profile, tokenBalance: balance } });
  },
}));
