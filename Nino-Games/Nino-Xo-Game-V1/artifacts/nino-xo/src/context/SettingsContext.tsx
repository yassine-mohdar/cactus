import React, { createContext, useContext, ReactNode } from 'react';
import { useSettingsStore } from '../stores/settingsStore';

interface SettingsContextValue {
  settings: { soundEnabled: boolean; musicEnabled: boolean };
  toggleSound: () => void;
  toggleMusic: () => void;
}

const SettingsContext = createContext<SettingsContextValue | null>(null);

export function SettingsProvider({ children }: { children: ReactNode }) {
  const store = useSettingsStore();

  const value: SettingsContextValue = {
    settings: { soundEnabled: store.soundEnabled, musicEnabled: store.musicEnabled },
    toggleSound: store.toggleSound,
    toggleMusic: store.toggleMusic,
  };

  return <SettingsContext.Provider value={value}>{children}</SettingsContext.Provider>;
}

export function useSettings() {
  const ctx = useContext(SettingsContext);
  if (!ctx) throw new Error('useSettings must be used inside SettingsProvider');
  return ctx;
}
