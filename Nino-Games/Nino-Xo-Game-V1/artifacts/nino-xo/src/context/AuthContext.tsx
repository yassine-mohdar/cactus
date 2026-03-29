import React, { createContext, useContext, useEffect, ReactNode } from 'react';
import { useAuthStore } from '../stores/authStore';
import { CharacterId, UserProfile } from '../types';
import { SyncResult } from '../services/playerSyncService';

interface AuthContextValue {
  profile: UserProfile | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  isAdmin: boolean;
  hasSubscription: boolean;
  pendingBadgeIds: string[];
  loginWithToken: (jwt: string, playerProfile: UserProfile) => Promise<void>;
  logout: () => Promise<void>;
  setSubscription: (hasSubscription: boolean) => void;
  updateCharacter: (id: CharacterId) => void;
  updateStats: (won: boolean, isDraw?: boolean) => void;
  updateName: (name: string) => Promise<SyncResult>;
  updateEmail: (email: string) => Promise<SyncResult>;
  updatePassword: (password: string) => void;
  updatePhoneNumber: (phoneNumber: string) => void;
  updateCountry: (country: string) => void;
  earnBadges: (ids: string[]) => void;
  clearPendingBadges: () => void;
  setTokenBalance: (balance: number) => void;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const store = useAuthStore();

  useEffect(() => {
    store.restoreSession();
  }, []);

  const value: AuthContextValue = {
    profile: store.profile,
    isLoading: store.isLoading,
    isAuthenticated: !!store.profile,
    isAdmin: store.isAdmin,
    hasSubscription: store.profile?.hasSubscription ?? false,
    pendingBadgeIds: store.pendingBadgeIds,
    loginWithToken: store.loginWithToken,
    logout: store.logout,
    setSubscription: store.setSubscription,
    updateCharacter: store.updateCharacter,
    updateStats: store.updateStats,
    updateName: store.updateName,
    updateEmail: store.updateEmail,
    updatePassword: store.updatePassword,
    updatePhoneNumber: store.updatePhoneNumber,
    updateCountry: store.updateCountry,
    earnBadges: store.earnBadges,
    clearPendingBadges: store.clearPendingBadges,
    setTokenBalance: store.setTokenBalance,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used inside AuthProvider');
  return ctx;
}
