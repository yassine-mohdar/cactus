import { router } from 'expo-router';
import React, { useEffect } from 'react';
import { View } from 'react-native';
import { useTheme } from '@/src/context/ThemeContext';

export default function HomeScreen() {
  const { colors } = useTheme();
  useEffect(() => {
    router.replace('/(tabs)');
  }, []);
  return <View style={{ flex: 1, backgroundColor: colors.background }} />;
}
