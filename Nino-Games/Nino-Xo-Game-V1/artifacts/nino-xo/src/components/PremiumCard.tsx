import React, { ReactNode } from 'react';
import { StyleSheet, View, ViewStyle } from 'react-native';
import { useTheme } from '@/src/context/ThemeContext';

interface Props {
  children: ReactNode;
  style?: ViewStyle;
  padding?: number;
}

export function PremiumCard({ children, style, padding = 20 }: Props) {
  const { colors } = useTheme();
  return (
    <View style={[styles.card, { padding, backgroundColor: colors.card }, style]}>
      {children}
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    borderRadius: 24,
    shadowColor: '#000',
    shadowOpacity: 0.06,
    shadowOffset: { width: 0, height: 4 },
    shadowRadius: 16,
    elevation: 4,
  },
});
