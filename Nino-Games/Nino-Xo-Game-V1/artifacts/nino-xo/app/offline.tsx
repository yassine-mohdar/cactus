import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import React, { useState } from 'react';
import {
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { NinoButton } from '@/src/components/NinoButton';
import { useTheme } from '@/src/context/ThemeContext';

export default function OfflineScreen() {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const [checking, setChecking] = useState(false);

  const handleRetry = async () => {
    setChecking(true);
    await new Promise(r => setTimeout(r, 1500));
    setChecking(false);
    router.replace('/');
  };

  return (
    <View style={[styles.container, { backgroundColor: colors.background, paddingTop: insets.top + 40, paddingBottom: insets.bottom + 40 }]}>
      <View style={[styles.iconWrap, { backgroundColor: colors.inputBg }]}>
        <Ionicons name="cloud-offline-outline" size={80} color={colors.textMuted} />
      </View>
      <Text style={[styles.title, { color: colors.textPrimary }]}>You're offline</Text>
      <Text style={[styles.subtitle, { color: colors.textSecondary }]}>
        Nino XO needs an internet connection for matchmaking and account features.
        Bot matches and friend rooms still work offline.
      </Text>
      <View style={styles.tips}>
        <View style={[styles.tip, { backgroundColor: colors.card }]}>
          <Ionicons name="hardware-chip-outline" size={20} color={Colors.green} />
          <Text style={[styles.tipText, { color: colors.textPrimary }]}>Bot matches work offline</Text>
        </View>
        <View style={[styles.tip, { backgroundColor: colors.card }]}>
          <Ionicons name="people-outline" size={20} color={Colors.blue} />
          <Text style={[styles.tipText, { color: colors.textPrimary }]}>Friend rooms work on local Wi-Fi</Text>
        </View>
        <View style={[styles.tip, { backgroundColor: colors.card }]}>
          <Ionicons name="flash-outline" size={20} color={Colors.coral} />
          <Text style={[styles.tipText, { color: colors.textPrimary }]}>Quick match requires connection</Text>
        </View>
      </View>
      <NinoButton
        label={checking ? 'Checking connection...' : 'Try Again'}
        onPress={handleRetry}
        loading={checking}
        color={Colors.green}
        fullWidth
      />
      <NinoButton
        label="Continue Offline"
        onPress={() => router.replace('/home')}
        variant="secondary"
        fullWidth
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    paddingHorizontal: 28,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 20,
  },
  iconWrap: {
    width: 120,
    height: 120,
    borderRadius: 60,
    alignItems: 'center',
    justifyContent: 'center',
  },
  title: {
    fontSize: 28,
    fontFamily: 'Inter_700Bold',
    textAlign: 'center',
  },
  subtitle: {
    fontSize: 15,
    fontFamily: 'Inter_400Regular',
    textAlign: 'center',
    lineHeight: 22,
  },
  tips: { width: '100%', gap: 12 },
  tip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    padding: 14,
    borderRadius: 14,
  },
  tipText: { fontSize: 14, fontFamily: 'Inter_500Medium' },
});
