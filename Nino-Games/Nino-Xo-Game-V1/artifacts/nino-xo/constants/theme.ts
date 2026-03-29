export interface ThemeColors {
  background: string;
  card: string;
  cardAlt: string;
  textPrimary: string;
  textSecondary: string;
  textMuted: string;
  textFaint: string;
  textVeryFaint: string;
  border: string;
  borderMid: string;
  inputBg: string;
  subtleBg: string;
  overlay: string;
  tabBar: string;
  tabBarBorder: string;
}

export const DarkTheme: ThemeColors = {
  background: '#16162A',
  card: '#1E1E35',
  cardAlt: '#222240',
  textPrimary: '#FFFFFF',
  textSecondary: 'rgba(255,255,255,0.55)',
  textMuted: 'rgba(255,255,255,0.40)',
  textFaint: 'rgba(255,255,255,0.25)',
  textVeryFaint: 'rgba(255,255,255,0.20)',
  border: 'rgba(255,255,255,0.08)',
  borderMid: 'rgba(255,255,255,0.15)',
  inputBg: 'rgba(255,255,255,0.06)',
  subtleBg: 'rgba(255,255,255,0.04)',
  overlay: 'rgba(0,0,0,0.72)',
  tabBar: '#1A1A30',
  tabBarBorder: 'rgba(255,255,255,0.08)',
};

export const LightTheme: ThemeColors = {
  background: '#F0F0F8',
  card: '#FFFFFF',
  cardAlt: '#F5F5FC',
  textPrimary: '#1A1A2E',
  textSecondary: 'rgba(26,26,46,0.55)',
  textMuted: 'rgba(26,26,46,0.40)',
  textFaint: 'rgba(26,26,46,0.25)',
  textVeryFaint: 'rgba(26,26,46,0.20)',
  border: 'rgba(26,26,46,0.07)',
  borderMid: 'rgba(26,26,46,0.14)',
  inputBg: 'rgba(26,26,46,0.05)',
  subtleBg: 'rgba(26,26,46,0.03)',
  overlay: 'rgba(0,0,0,0.45)',
  tabBar: '#FFFFFF',
  tabBarBorder: 'rgba(0,0,0,0.08)',
};
