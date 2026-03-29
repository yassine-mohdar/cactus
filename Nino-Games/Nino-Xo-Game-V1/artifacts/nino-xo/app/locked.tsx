import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import React from 'react';
import {
  Image,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useAuth } from '@/src/context/AuthContext';
import { useAuthStore } from '@/src/stores/authStore';
import { subscriptionService } from '@/src/services/subscriptionService';
import { NinoButton } from '@/src/components/NinoButton';
import { PremiumCard } from '@/src/components/PremiumCard';
import { useTheme } from '@/src/context/ThemeContext';

type IoniconsName = React.ComponentProps<typeof Ionicons>['name'];

const PERKS: { icon: IoniconsName; label: string; desc: string }[] = [
  { icon: 'people', label: 'All 6 Characters', desc: 'Play as any NinoWorld cactus' },
  { icon: 'hardware-chip', label: 'Bot Matches', desc: 'Practice against smart AI' },
  { icon: 'flash', label: 'Quick Matches', desc: 'Instant matchmaking' },
  { icon: 'people-outline', label: 'Friend Rooms', desc: 'Private rooms with friends' },
  { icon: 'flame', label: 'Daily Missions', desc: 'Earn streaks and rewards' },
  { icon: 'trophy', label: 'Win Streaks', desc: 'Track your best performance' },
];

export default function LockedScreen() {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const { profile } = useAuth();
  const setSubscription = useAuthStore(s => s.setSubscription);

  const grantAccess = () => {
    subscriptionService.setAccessOverride(true);
    setSubscription(true);
    router.replace('/home');
  };

  const handleSubscribe = () => {
    grantAccess();
  };

  const handleRestore = () => {
    grantAccess();
  };

  return (
    <ScrollView
      style={[styles.container, { backgroundColor: colors.background }]}
      contentContainerStyle={[styles.content, { paddingTop: insets.top + 12, paddingBottom: insets.bottom + 32 }]}
      showsVerticalScrollIndicator={false}
    >
      <Pressable onPress={() => router.back()} style={styles.closeBtn}>
        <Ionicons name="close" size={24} color={colors.textMuted} />
      </Pressable>

      <View style={styles.hero}>
        <Image
          source={require('@/assets/images/char_nino.png')}
          style={styles.heroImage}
          resizeMode="contain"
        />
        <View style={[styles.heroBadge, { backgroundColor: Colors.yellow }]}>
          <Ionicons name="diamond" size={14} color={Colors.white} />
          <Text style={styles.heroBadgeText}>PREMIUM EXCLUSIVE</Text>
        </View>
        <Text style={[styles.heroTitle, { color: colors.textPrimary }]}>Nino XO</Text>
        <Text style={[styles.heroSub, { color: colors.textSecondary }]}>
          A premium NinoWorld game for subscribers.{'\n'}Unlock the full cactus battle experience.
        </Text>
      </View>

      <PremiumCard style={styles.perksCard}>
        <Text style={[styles.perksTitle, { color: colors.textPrimary }]}>What's included</Text>
        {PERKS.map((perk, i) => (
          <View key={i} style={styles.perkRow}>
            <View style={[styles.perkIcon, { backgroundColor: Colors.greenPale }]}>
              <Ionicons name={perk.icon} size={16} color={Colors.green} />
            </View>
            <View style={styles.perkInfo}>
              <Text style={[styles.perkLabel, { color: colors.textPrimary }]}>{perk.label}</Text>
              <Text style={[styles.perkDesc, { color: colors.textMuted }]}>{perk.desc}</Text>
            </View>
            <Ionicons name="checkmark-circle" size={20} color={Colors.green} />
          </View>
        ))}
      </PremiumCard>

      <PremiumCard style={[styles.pricingCard, { backgroundColor: Colors.textDark }]}>
        <Text style={styles.pricingTitle}>NinoWorld Premium</Text>
        <Text style={styles.pricingPrice}>$4.99<Text style={styles.pricingPer}> / month</Text></Text>
        <Text style={styles.pricingDesc}>Access all NinoWorld premium games and content</Text>
      </PremiumCard>

      <NinoButton
        label="Subscribe to NinoWorld Premium"
        onPress={handleSubscribe}
        color={Colors.green}
        fullWidth
      />

      <Pressable onPress={handleRestore} style={styles.restoreBtn}>
        <Text style={styles.restoreText}>Restore Purchase</Text>
      </Pressable>

      <Text style={[styles.legal, { color: colors.textFaint }]}>
        Subscription auto-renews monthly. Cancel anytime in App Store settings.
        By subscribing, you agree to our Terms of Service and Privacy Policy.
      </Text>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  content: { paddingHorizontal: 24, gap: 16 },
  closeBtn: {
    alignSelf: 'flex-end',
    width: 40,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 4,
  },
  hero: { alignItems: 'center', gap: 10 },
  heroImage: { width: 120, height: 120 },
  heroBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 14,
    paddingVertical: 6,
    borderRadius: 12,
  },
  heroBadgeText: {
    fontSize: 11,
    fontFamily: 'Inter_700Bold',
    color: Colors.white,
    letterSpacing: 1.2,
  },
  heroTitle: { fontSize: 36, fontFamily: 'Inter_700Bold' },
  heroSub: {
    fontSize: 15,
    fontFamily: 'Inter_400Regular',
    textAlign: 'center',
    lineHeight: 22,
  },
  perksCard: { gap: 12 },
  perksTitle: { fontSize: 16, fontFamily: 'Inter_700Bold', marginBottom: 4 },
  perkRow: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  perkIcon: { width: 36, height: 36, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
  perkInfo: { flex: 1 },
  perkLabel: { fontSize: 14, fontFamily: 'Inter_600SemiBold' },
  perkDesc: { fontSize: 12, fontFamily: 'Inter_400Regular', marginTop: 2 },
  pricingCard: { alignItems: 'center', gap: 6 },
  pricingTitle: { fontSize: 14, fontFamily: 'Inter_500Medium', color: 'rgba(255,255,255,0.25)', letterSpacing: 1, textTransform: 'uppercase' },
  pricingPrice: { fontSize: 48, fontFamily: 'Inter_700Bold', color: Colors.white },
  pricingPer: { fontSize: 18, fontFamily: 'Inter_400Regular', color: 'rgba(255,255,255,0.25)' },
  pricingDesc: { fontSize: 13, fontFamily: 'Inter_400Regular', color: 'rgba(255,255,255,0.25)', textAlign: 'center', lineHeight: 18 },
  restoreBtn: { alignItems: 'center', paddingVertical: 8 },
  restoreText: { fontSize: 14, fontFamily: 'Inter_500Medium', color: Colors.blue },
  legal: {
    fontSize: 10,
    fontFamily: 'Inter_400Regular',
    textAlign: 'center',
    lineHeight: 15,
  },
});
