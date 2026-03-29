import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import React, { useEffect } from 'react';
import {
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import Animated, {
  useAnimatedStyle,
  useSharedValue,
  withDelay,
  withSequence,
  withSpring,
  withTiming,
} from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useAuth } from '@/src/context/AuthContext';
import { useGame } from '@/src/context/GameContext';
import { useTheme } from '@/src/context/ThemeContext';
import { getCharacter } from '@/src/data/characters';
import { getBadge } from '@/src/data/badges';
import { NinoButton } from '@/src/components/NinoButton';
import { PremiumCard } from '@/src/components/PremiumCard';

type IoniconsName = React.ComponentProps<typeof Ionicons>['name'];

interface RewardBadgeProps {
  icon: IoniconsName;
  color: string;
  pale: string;
  label: string;
  value: string;
  delay: number;
  colors: ReturnType<typeof useTheme>['colors'];
}

function RewardBadge({ icon, color, pale, label, value, delay, colors }: RewardBadgeProps) {
  const opacity = useSharedValue(0);
  const translateY = useSharedValue(20);

  useEffect(() => {
    opacity.value = withDelay(delay, withTiming(1, { duration: 400 }));
    translateY.value = withDelay(delay, withSpring(0, { damping: 14 }));
  }, []);

  const style = useAnimatedStyle(() => ({
    opacity: opacity.value,
    transform: [{ translateY: translateY.value }],
  }));

  return (
    <Animated.View style={[styles.badge, { backgroundColor: pale }, style]}>
      <View style={[styles.badgeIcon, { backgroundColor: color }]}>
        <Ionicons name={icon} size={22} color={Colors.white} />
      </View>
      <Text style={[styles.badgeValue, { color }]}>{value}</Text>
      <Text style={[styles.badgeLabel, { color: colors.textSecondary }]}>{label}</Text>
    </Animated.View>
  );
}

function NewBadgeCard({ badgeId, delay, colors }: {
  badgeId: string;
  delay: number;
  colors: ReturnType<typeof useTheme>['colors'];
}) {
  const badge = getBadge(badgeId);
  const opacity = useSharedValue(0);
  const scale = useSharedValue(0.7);

  useEffect(() => {
    opacity.value = withDelay(delay, withTiming(1, { duration: 400 }));
    scale.value = withDelay(delay, withSpring(1, { damping: 12 }));
  }, []);

  const style = useAnimatedStyle(() => ({
    opacity: opacity.value,
    transform: [{ scale: scale.value }],
  }));

  if (!badge) return null;

  return (
    <Animated.View style={[styles.newBadge, { backgroundColor: badge.pale, borderColor: badge.color + '50' }, style]}>
      <View style={[styles.newBadgeIcon, { backgroundColor: badge.color }]}>
        <Ionicons name={badge.icon} size={20} color={Colors.white} />
      </View>
      <View style={styles.newBadgeText}>
        <Text style={[styles.newBadgeName, { color: colors.textPrimary }]}>{badge.name}</Text>
        <Text style={[styles.newBadgeDesc, { color: colors.textSecondary }]}>{badge.description}</Text>
      </View>
    </Animated.View>
  );
}

export default function RewardsScreen() {
  const insets = useSafeAreaInsets();
  const { profile, pendingBadgeIds, clearPendingBadges } = useAuth();
  const { session, rematch, resetGame } = useGame();
  const { colors } = useTheme();

  const bannerScale = useSharedValue(0.8);
  const bannerOpacity = useSharedValue(0);

  useEffect(() => {
    bannerOpacity.value = withTiming(1, { duration: 500 });
    bannerScale.value = withSpring(1, { damping: 10 });
    return () => {
      clearPendingBadges();
    };
  }, []);

  const bannerStyle = useAnimatedStyle(() => ({
    opacity: bannerOpacity.value,
    transform: [{ scale: bannerScale.value }],
  }));

  useEffect(() => {
    if (!profile) router.replace('/home');
  }, [profile]);

  if (!profile) return null;

  const character = getCharacter(profile.selectedCharacterId);
  const missionDone = profile.stats.missionProgress >= profile.stats.missionGoal;
  const isWin =
    session?.result &&
    session.result !== 'DRAW' &&
    session.players.find((p) => !p.isBot)?.symbol === session.result?.charAt(0);

  const handleContinue = () => {
    resetGame();
    router.replace('/home');
  };

  const handleRematch = () => {
    rematch();
    router.replace('/match');
  };

  return (
    <ScrollView
      style={[styles.container, { backgroundColor: colors.background, paddingTop: insets.top }]}
      contentContainerStyle={[styles.scrollContent, { paddingBottom: insets.bottom + 24 }]}
      showsVerticalScrollIndicator={false}
    >
      <View style={[styles.decorTL, { backgroundColor: Colors.yellowPale }]} />
      <View style={[styles.decorBR, { backgroundColor: Colors.greenPale }]} />

      <Animated.View
        style={[styles.banner, { backgroundColor: isWin ? Colors.green : Colors.yellow }, bannerStyle]}
      >
        <Text style={styles.bannerTitle}>{isWin ? 'Victory!' : 'Good Game!'}</Text>
        <Text style={styles.bannerSub}>
          {isWin ? character.reactions.win : character.reactions.draw}
        </Text>
      </Animated.View>

      <Text style={[styles.sectionTitle, { color: colors.textPrimary }]}>Your Rewards</Text>

      <View style={styles.badgeGrid}>
        <RewardBadge
          icon="trophy"
          color={Colors.yellow}
          pale={Colors.yellowPale}
          label="Total Wins"
          value={String(profile.stats.totalWins)}
          delay={100}
          colors={colors}
        />
        <RewardBadge
          icon="flame"
          color={Colors.coral}
          pale={Colors.coralPale}
          label="Current Streak"
          value={`${profile.stats.streak}🔥`}
          delay={200}
          colors={colors}
        />
        <RewardBadge
          icon="star"
          color={Colors.purple}
          pale={Colors.purplePale}
          label="Best Streak"
          value={String(profile.stats.bestStreak)}
          delay={300}
          colors={colors}
        />
        <RewardBadge
          icon="game-controller"
          color={Colors.blue}
          pale={Colors.bluePale}
          label="Matches Played"
          value={String(profile.stats.totalMatches)}
          delay={400}
          colors={colors}
        />
      </View>

      {pendingBadgeIds.length > 0 && (
        <View style={styles.newBadgesSection}>
          <View style={styles.newBadgesHeader}>
            <Ionicons name="ribbon" size={18} color={Colors.yellow} />
            <Text style={[styles.newBadgesTitle, { color: Colors.yellow }]}>New Badges Unlocked!</Text>
          </View>
          {pendingBadgeIds.map((id, i) => (
            <NewBadgeCard key={id} badgeId={id} delay={500 + i * 150} colors={colors} />
          ))}
        </View>
      )}

      {missionDone && (
        <PremiumCard style={styles.missionCompleteCard}>
          <Ionicons name="checkmark-circle" size={32} color={Colors.purple} />
          <Text style={[styles.missionCompleteTitle, { color: Colors.purple }]}>
            Daily Mission Complete!
          </Text>
          <Text style={[styles.missionCompleteSub, { color: colors.textSecondary }]}>
            You played {profile.stats.missionGoal} matches today. Come back tomorrow!
          </Text>
        </PremiumCard>
      )}

      <View style={styles.actions}>
        <NinoButton
          label="Play Again"
          onPress={handleRematch}
          color={isWin ? Colors.green : Colors.yellow}
          fullWidth
        />
        <NinoButton
          label="Back to Home"
          onPress={handleContinue}
          variant="secondary"
          fullWidth
        />
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  scrollContent: { paddingHorizontal: 20, gap: 20 },
  decorTL: {
    position: 'absolute',
    top: -60, left: -60,
    width: 180, height: 180,
    borderRadius: 90,
    opacity: 0.6,
  },
  decorBR: {
    position: 'absolute',
    bottom: -40, right: -40,
    width: 160, height: 160,
    borderRadius: 80,
    opacity: 0.6,
  },
  banner: {
    marginTop: 16,
    borderRadius: 24,
    padding: 24,
    alignItems: 'center',
    gap: 8,
  },
  bannerTitle: { fontSize: 36, fontFamily: 'Inter_700Bold', color: Colors.white },
  bannerSub: {
    fontSize: 15, fontFamily: 'Inter_400Regular',
    color: Colors.white, opacity: 0.9, textAlign: 'center',
  },
  sectionTitle: { fontSize: 18, fontFamily: 'Inter_700Bold' },
  badgeGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  badge: { flex: 1, minWidth: '45%', borderRadius: 20, padding: 16, alignItems: 'center', gap: 8 },
  badgeIcon: { width: 48, height: 48, borderRadius: 16, alignItems: 'center', justifyContent: 'center' },
  badgeValue: { fontSize: 28, fontFamily: 'Inter_700Bold' },
  badgeLabel: { fontSize: 12, fontFamily: 'Inter_400Regular', textAlign: 'center' },
  newBadgesSection: { gap: 10 },
  newBadgesHeader: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  newBadgesTitle: { fontSize: 16, fontFamily: 'Inter_700Bold' },
  newBadge: {
    flexDirection: 'row', alignItems: 'center', gap: 12,
    padding: 14, borderRadius: 18, borderWidth: 1,
  },
  newBadgeIcon: {
    width: 42, height: 42, borderRadius: 13,
    alignItems: 'center', justifyContent: 'center',
  },
  newBadgeText: { flex: 1 },
  newBadgeName: { fontSize: 15, fontFamily: 'Inter_700Bold' },
  newBadgeDesc: { fontSize: 12, fontFamily: 'Inter_400Regular', marginTop: 2 },
  missionCompleteCard: { alignItems: 'center', gap: 8 },
  missionCompleteTitle: { fontSize: 20, fontFamily: 'Inter_700Bold' },
  missionCompleteSub: {
    fontSize: 14, fontFamily: 'Inter_400Regular', textAlign: 'center', lineHeight: 20,
  },
  actions: { gap: 10, marginTop: 8 },
});
