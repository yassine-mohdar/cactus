import { router } from 'expo-router';
import React, { useEffect } from 'react';
import {
  ActivityIndicator,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { Colors } from '@/constants/colors';
import { useAuthStore } from '@/src/stores/authStore';
import { subscriptionService } from '@/src/services/subscriptionService';
import { useTheme } from '@/src/context/ThemeContext';

export default function SessionRestoreScreen() {
  const { colors } = useTheme();
  const profile = useAuthStore(s => s.profile);

  useEffect(() => {
    const restore = async () => {
      if (!profile) {
        router.replace('/login');
        return;
      }

      try {
        const { hasAccess } = await subscriptionService.checkEntitlement(profile.id);
        if (!hasAccess) {
          router.replace('/locked');
        } else {
          router.replace('/(tabs)');
        }
      } catch {
        router.replace('/offline');
      }
    };

    const timer = setTimeout(restore, 800);
    return () => clearTimeout(timer);
  }, []);

  return (
    <View style={[styles.container, { backgroundColor: colors.background }]}>
      <ActivityIndicator size="large" color={Colors.green} />
      <Text style={[styles.text, { color: colors.textSecondary }]}>Restoring your session...</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 16,
  },
  text: {
    fontSize: 15,
    fontFamily: 'Inter_400Regular',
  },
});
