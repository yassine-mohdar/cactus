import React, { createContext, useContext, ReactNode } from 'react';
import { useColorScheme } from 'react-native';
import { DarkTheme, LightTheme, ThemeColors } from '../../constants/theme';
import { useSettingsStore } from '../stores/settingsStore';

interface ThemeContextValue {
  colors: ThemeColors;
  isDark: boolean;
}

const ThemeContext = createContext<ThemeContextValue>({
  colors: DarkTheme,
  isDark: true,
});

export function ThemeProvider({ children }: { children: ReactNode }) {
  const systemScheme = useColorScheme();
  const preference = useSettingsStore((s) => s.theme);

  const resolvedScheme =
    preference === 'system'
      ? (systemScheme ?? 'dark')
      : preference;

  const isDark = resolvedScheme === 'dark';
  const colors = isDark ? DarkTheme : LightTheme;

  return (
    <ThemeContext.Provider value={{ colors, isDark }}>
      {children}
    </ThemeContext.Provider>
  );
}

export function useTheme(): ThemeContextValue {
  return useContext(ThemeContext);
}
