import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'zustand';
import { createJSONStorage, persist } from 'zustand/middleware';
import { AppSettings, ThemePreference } from '../types';

interface SettingsState extends AppSettings {
  toggleSound: () => void;
  toggleMusic: () => void;
  setTheme: (theme: ThemePreference) => void;
}

export const useSettingsStore = create<SettingsState>()(
  persist(
    (set, get) => ({
      soundEnabled: true,
      musicEnabled: false,
      theme: 'system' as ThemePreference,
      toggleSound: () => set({ soundEnabled: !get().soundEnabled }),
      toggleMusic: () => set({ musicEnabled: !get().musicEnabled }),
      setTheme: (theme: ThemePreference) => set({ theme }),
    }),
    {
      name: '@ninoxo_settings_v2',
      storage: createJSONStorage(() => AsyncStorage),
    }
  )
);
