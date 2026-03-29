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
import { useTheme } from '@/src/context/ThemeContext';
import { PremiumCard } from '@/src/components/PremiumCard';

type IoniconsName = React.ComponentProps<typeof Ionicons>['name'];

const FEATURES: { icon: IoniconsName; label: string; desc: string }[] = [
  { icon: 'people', label: '6 Cactus Characters', desc: 'Each with unique personality and style' },
  { icon: 'hardware-chip', label: 'Smart Bot AI', desc: 'Easy and medium difficulty opponents' },
  { icon: 'flash', label: 'Quick Matchmaking', desc: 'Instant global opponent matching' },
  { icon: 'people-outline', label: 'Friend Rooms', desc: 'Private rooms with shareable codes' },
  { icon: 'trophy', label: 'Missions & Rewards', desc: 'Daily goals and achievement badges' },
  { icon: 'bar-chart', label: 'Leaderboards', desc: 'Global and friends rankings' },
];

export default function AboutScreen() {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();

  return (
    <ScrollView
      style={[styles.container, { backgroundColor: colors.background }]}
      contentContainerStyle={[styles.content, { paddingTop: insets.top + 12, paddingBottom: insets.bottom + 32 }]}
      showsVerticalScrollIndicator={false}
    >
      <View style={styles.header}>
        <Pressable onPress={() => router.back()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={24} color={colors.textPrimary} />
        </Pressable>
        <Text style={[styles.title, { color: colors.textPrimary }]}>About Nino XO</Text>
        <View style={{ width: 40 }} />
      </View>

      <View style={styles.hero}>
        <Image
          source={require('@/assets/images/char_nino.png')}
          style={styles.heroImage}
          resizeMode="contain"
        />
        <Text style={[styles.heroTitle, { color: colors.textPrimary }]}>Nino XO</Text>
        <View style={[styles.badge, { backgroundColor: Colors.greenPale }]}>
          <Ionicons name="diamond" size={12} color={Colors.green} />
          <Text style={[styles.badgeText, { color: Colors.green }]}>NinoWorld Premium</Text>
        </View>
        <Text style={[styles.heroVersion, { color: colors.textFaint }]}>Version 1.0.0</Text>
      </View>

      <PremiumCard>
        <Text style={[styles.cardTitle, { color: colors.textPrimary }]}>About the Game</Text>
        <Text style={[styles.cardBody, { color: colors.textSecondary }]}>
          Nino XO is a premium 1v1 XO/Tic-Tac-Toe game set in the NinoWorld cactus character universe.
          Choose your favorite cactus character and battle friends or AI opponents in the classic
          strategy game reimagined with NinoWorld's unique art style.
        </Text>
      </PremiumCard>

      <PremiumCard>
        <Text style={[styles.cardTitle, { color: colors.textPrimary }]}>Features</Text>
        <View style={styles.featureList}>
          {FEATURES.map((f, i) => (
            <View key={i} style={styles.featureRow}>
              <View style={[styles.featureIcon, { backgroundColor: Colors.greenPale }]}>
                <Ionicons name={f.icon} size={16} color={Colors.green} />
              </View>
              <View style={styles.featureInfo}>
                <Text style={[styles.featureLabel, { color: colors.textPrimary }]}>{f.label}</Text>
                <Text style={[styles.featureDesc, { color: colors.textFaint }]}>{f.desc}</Text>
              </View>
            </View>
          ))}
        </View>
      </PremiumCard>

      <PremiumCard>
        <Text style={[styles.cardTitle, { color: colors.textPrimary }]}>NinoWorld</Text>
        <Text style={[styles.cardBody, { color: colors.textSecondary }]}>
          NinoWorld is a universe of cactus characters, each with their own personality, story, and style.
          Nino XO is part of the growing collection of NinoWorld premium games and experiences, exclusive
          to NinoWorld subscribers.
        </Text>
      </PremiumCard>

      <PremiumCard>
        <Text style={[styles.cardTitle, { color: colors.textPrimary }]}>Subscription</Text>
        <Text style={[styles.cardBody, { color: colors.textSecondary }]}>
          Nino XO is exclusively available to NinoWorld Premium subscribers at $4.99/month.
          Your subscription includes access to all NinoWorld premium games and future releases.
        </Text>
        <View style={styles.pricingRow}>
          <Text style={[styles.pricingPrice, { color: Colors.green }]}>$4.99</Text>
          <Text style={[styles.pricingPer, { color: colors.textFaint }]}>/month · Cancel anytime</Text>
        </View>
      </PremiumCard>

      <View style={styles.footer}>
        <Text style={[styles.footerText, { color: colors.textFaint }]}>© 2026 NinoWorld · All rights reserved</Text>
        <Text style={[styles.footerText, { color: colors.textFaint }]}>Made with love for cactus fans everywhere</Text>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  content: { paddingHorizontal: 20, gap: 14 },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 4,
  },
  backBtn: { width: 40, height: 40, alignItems: 'center', justifyContent: 'center' },
  title: { fontSize: 20, fontFamily: 'Inter_700Bold' },
  hero: { alignItems: 'center', gap: 8, paddingVertical: 16 },
  heroImage: { width: 100, height: 100 },
  heroTitle: { fontSize: 32, fontFamily: 'Inter_700Bold' },
  badge: { flexDirection: 'row', alignItems: 'center', gap: 6, paddingHorizontal: 12, paddingVertical: 5, borderRadius: 10 },
  badgeText: { fontSize: 12, fontFamily: 'Inter_600SemiBold' },
  heroVersion: { fontSize: 13, fontFamily: 'Inter_400Regular' },
  cardTitle: { fontSize: 16, fontFamily: 'Inter_700Bold', marginBottom: 8 },
  cardBody: { fontSize: 14, fontFamily: 'Inter_400Regular', lineHeight: 21 },
  featureList: { gap: 12 },
  featureRow: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  featureIcon: { width: 36, height: 36, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
  featureInfo: { flex: 1 },
  featureLabel: { fontSize: 14, fontFamily: 'Inter_600SemiBold' },
  featureDesc: { fontSize: 12, fontFamily: 'Inter_400Regular', marginTop: 2 },
  pricingRow: { flexDirection: 'row', alignItems: 'baseline', gap: 6, marginTop: 10 },
  pricingPrice: { fontSize: 28, fontFamily: 'Inter_700Bold' },
  pricingPer: { fontSize: 13, fontFamily: 'Inter_400Regular' },
  footer: { alignItems: 'center', gap: 4, paddingTop: 8 },
  footerText: { fontSize: 12, fontFamily: 'Inter_400Regular' },
});
