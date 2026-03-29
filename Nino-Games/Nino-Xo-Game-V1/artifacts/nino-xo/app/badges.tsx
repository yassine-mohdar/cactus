import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import React, { useState } from 'react';
import {
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import Animated, {
  useAnimatedStyle,
  useSharedValue,
  withSpring,
  withTiming,
} from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useAuth } from '@/src/context/AuthContext';
import { BADGES, type Badge } from '@/src/data/badges';
import { useTheme } from '@/src/context/ThemeContext';

const TIER_ORDER = ['platinum', 'gold', 'silver', 'bronze'] as const;
const TIER_LABELS: Record<string, string> = {
  platinum: 'Platinum',
  gold: 'Gold',
  silver: 'Silver',
  bronze: 'Bronze',
};
const TIER_COLORS: Record<string, string> = {
  platinum: '#06B6D4',
  gold: '#FFD700',
  silver: '#94A3B8',
  bronze: '#CD7F32',
};

function BadgeInfoSheet({ badge, isEarned, onClose }: {
  badge: Badge;
  isEarned: boolean;
  onClose: () => void;
}) {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const translateY = useSharedValue(300);
  const opacity = useSharedValue(0);

  React.useEffect(() => {
    opacity.value = withTiming(1, { duration: 180 });
    translateY.value = withSpring(0, { damping: 18, stiffness: 200 });
  }, []);

  function handleClose() {
    opacity.value = withTiming(0, { duration: 160 });
    translateY.value = withTiming(300, { duration: 200 });
    setTimeout(onClose, 200);
  }

  const sheetStyle = useAnimatedStyle(() => ({
    transform: [{ translateY: translateY.value }],
  }));
  const overlayStyle = useAnimatedStyle(() => ({
    opacity: opacity.value,
  }));

  return (
    <Modal transparent animationType="none" statusBarTranslucent onRequestClose={handleClose}>
      <Animated.View style={[styles.sheetOverlay, overlayStyle]}>
        <Pressable style={StyleSheet.absoluteFill} onPress={handleClose} />
        <Animated.View style={[styles.sheet, { backgroundColor: colors.card, borderColor: colors.border }, sheetStyle, { paddingBottom: insets.bottom + 20 }]}>
          <View style={[styles.sheetHandle, { backgroundColor: colors.borderMid }]} />

          <View style={[styles.sheetIconWrap, { backgroundColor: isEarned ? badge.color : colors.inputBg }]}>
            <Ionicons
              name={badge.icon}
              size={36}
              color={isEarned ? Colors.white : colors.textFaint}
            />
          </View>

          {!isEarned && (
            <View style={[styles.lockBadge, { backgroundColor: colors.subtleBg }]}>
              <Ionicons name="lock-closed" size={13} color={colors.textFaint} />
              <Text style={[styles.lockBadgeText, { color: colors.textFaint }]}>Locked</Text>
            </View>
          )}

          <Text style={[styles.sheetBadgeName, { color: isEarned ? badge.color : colors.textMuted }]}>
            {badge.name}
          </Text>

          <View style={[styles.sheetTierPill, { backgroundColor: TIER_COLORS[badge.tier] + '22' }]}>
            <View style={[styles.sheetTierDot, { backgroundColor: TIER_COLORS[badge.tier] }]} />
            <Text style={[styles.sheetTierText, { color: TIER_COLORS[badge.tier] }]}>
              {TIER_LABELS[badge.tier]}
            </Text>
          </View>

          <View style={[styles.sheetDescWrap, { backgroundColor: isEarned ? badge.pale : colors.subtleBg }]}>
            <Text style={[styles.sheetReqLabel, { color: colors.textFaint }]}>Requirement</Text>
            <Text style={[styles.sheetDesc, { color: colors.textSecondary }]}>{badge.description}</Text>
          </View>

          {isEarned && (
            <View style={styles.sheetEarnedRow}>
              <Ionicons name="checkmark-circle" size={18} color={Colors.green} />
              <Text style={styles.sheetEarnedText}>You've earned this badge!</Text>
            </View>
          )}

          <Pressable style={[styles.sheetCloseBtn, { backgroundColor: colors.inputBg }]} onPress={handleClose}>
            <Text style={[styles.sheetCloseBtnText, { color: colors.textSecondary }]}>Close</Text>
          </Pressable>
        </Animated.View>
      </Animated.View>
    </Modal>
  );
}

export default function BadgesScreen() {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const { profile } = useAuth();
  const [selectedBadge, setSelectedBadge] = useState<Badge | null>(null);

  const earned = new Set(profile?.earnedBadgeIds ?? []);
  const totalEarned = earned.size;
  const totalBadges = BADGES.length;

  const badgesByTier = TIER_ORDER.map((tier) => ({
    tier,
    badges: BADGES.filter((b) => b.tier === tier),
  }));

  return (
    <View style={[styles.container, { paddingTop: insets.top, backgroundColor: colors.background }]}>
      <View style={styles.header}>
        <Pressable onPress={() => router.back()} style={[styles.backBtn, { backgroundColor: colors.inputBg }]}>
          <Ionicons name="chevron-back" size={24} color={colors.textPrimary} />
        </Pressable>
        <Text style={[styles.title, { color: colors.textPrimary }]}>Badges</Text>
        <View style={styles.headerRight}>
          <Text style={[styles.progress, { color: colors.textFaint }]}>
            {totalEarned}/{totalBadges}
          </Text>
        </View>
      </View>

      <ScrollView
        style={styles.scroll}
        contentContainerStyle={[styles.scrollContent, { paddingBottom: insets.bottom + 24 }]}
        showsVerticalScrollIndicator={false}
      >
        <View style={[styles.progressCard, { backgroundColor: colors.card, borderColor: colors.border }]}>
          <View style={styles.progressRow}>
            <Text style={[styles.progressLabel, { color: colors.textPrimary }]}>Collection Progress</Text>
            <Text style={[styles.progressFraction, { color: colors.textFaint }]}>
              {totalEarned} / {totalBadges} earned
            </Text>
          </View>
          <View style={[styles.progressTrack, { backgroundColor: colors.inputBg }]}>
            <View
              style={[
                styles.progressFill,
                { width: `${totalBadges > 0 ? (totalEarned / totalBadges) * 100 : 0}%` },
              ]}
            />
          </View>
        </View>

        {badgesByTier.map(({ tier, badges }) => (
          <View key={tier} style={styles.tierSection}>
            <View style={styles.tierHeader}>
              <View style={[styles.tierDot, { backgroundColor: TIER_COLORS[tier] }]} />
              <Text style={[styles.tierLabel, { color: TIER_COLORS[tier] }]}>
                {TIER_LABELS[tier]}
              </Text>
              <Text style={[styles.tierCount, { color: colors.textFaint }]}>
                {badges.filter((b) => earned.has(b.id)).length}/{badges.length}
              </Text>
            </View>
            <View style={styles.badgeGrid}>
              {badges.map((badge) => {
                const isEarned = earned.has(badge.id);
                return (
                  <Pressable
                    key={badge.id}
                    onPress={() => setSelectedBadge(badge)}
                    style={[
                      styles.badgeCard,
                      isEarned
                        ? { borderColor: badge.color + '40', backgroundColor: badge.pale }
                        : { borderColor: colors.border, backgroundColor: colors.subtleBg },
                    ]}
                  >
                    <View
                      style={[
                        styles.badgeIconWrap,
                        { backgroundColor: isEarned ? badge.color : colors.inputBg },
                      ]}
                    >
                      <Ionicons
                        name={badge.icon}
                        size={22}
                        color={isEarned ? Colors.white : colors.textFaint}
                      />
                    </View>
                    <Text
                      style={[
                        styles.badgeName,
                        { color: isEarned ? colors.textPrimary : colors.textMuted },
                      ]}
                      numberOfLines={1}
                    >
                      {badge.name}
                    </Text>
                    <Text
                      style={[
                        styles.badgeDesc,
                        { color: isEarned ? colors.textSecondary : colors.textFaint },
                      ]}
                      numberOfLines={2}
                    >
                      {badge.description}
                    </Text>
                    {isEarned && (
                      <View style={[styles.earnedDot, { backgroundColor: badge.color }]} />
                    )}
                    {!isEarned && (
                      <Ionicons
                        name="lock-closed"
                        size={12}
                        color={colors.textVeryFaint}
                        style={styles.lockIcon}
                      />
                    )}
                  </Pressable>
                );
              })}
            </View>
          </View>
        ))}
      </ScrollView>

      {selectedBadge && (
        <BadgeInfoSheet
          badge={selectedBadge}
          isEarned={earned.has(selectedBadge.id)}
          onClose={() => setSelectedBadge(null)}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },

  header: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 14,
  },
  backBtn: {
    width: 40,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 12,
  },
  title: {
    flex: 1,
    textAlign: 'center',
    fontSize: 18,
    fontFamily: 'Inter_700Bold',
  },
  headerRight: { width: 40, alignItems: 'flex-end' },
  progress: { fontSize: 13, fontFamily: 'Inter_600SemiBold' },

  scroll: { flex: 1 },
  scrollContent: { paddingHorizontal: 16, gap: 20, paddingTop: 4 },

  progressCard: {
    borderRadius: 20,
    padding: 18,
    gap: 12,
    borderWidth: 1,
  },
  progressRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  progressLabel: { fontSize: 14, fontFamily: 'Inter_600SemiBold' },
  progressFraction: { fontSize: 13, fontFamily: 'Inter_400Regular' },
  progressTrack: {
    height: 8,
    borderRadius: 4,
    overflow: 'hidden',
  },
  progressFill: {
    height: 8,
    borderRadius: 4,
    backgroundColor: Colors.purple,
  },

  tierSection: { gap: 12 },
  tierHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  tierDot: { width: 8, height: 8, borderRadius: 4 },
  tierLabel: { flex: 1, fontSize: 13, fontFamily: 'Inter_700Bold', letterSpacing: 0.8, textTransform: 'uppercase' },
  tierCount: { fontSize: 12, fontFamily: 'Inter_400Regular' },

  badgeGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  badgeCard: {
    width: '47%',
    borderRadius: 18,
    padding: 14,
    gap: 6,
    borderWidth: 1,
    position: 'relative',
  },
  badgeIconWrap: {
    width: 44,
    height: 44,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 2,
  },
  badgeName: { fontSize: 14, fontFamily: 'Inter_700Bold' },
  badgeDesc: { fontSize: 11, fontFamily: 'Inter_400Regular', lineHeight: 16 },
  earnedDot: {
    position: 'absolute',
    top: 10,
    right: 10,
    width: 8,
    height: 8,
    borderRadius: 4,
  },
  lockIcon: { position: 'absolute', top: 12, right: 12 },

  sheetOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.65)',
    justifyContent: 'flex-end',
  },
  sheet: {
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    padding: 24,
    alignItems: 'center',
    gap: 14,
    borderTopWidth: 1,
  },
  sheetHandle: {
    width: 40,
    height: 4,
    borderRadius: 2,
    marginBottom: 4,
  },
  sheetIconWrap: {
    width: 72,
    height: 72,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
  },
  lockBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 20,
    marginTop: -6,
  },
  lockBadgeText: {
    fontSize: 11,
    fontFamily: 'Inter_600SemiBold',
  },
  sheetBadgeName: {
    fontSize: 22,
    fontFamily: 'Inter_700Bold',
    textAlign: 'center',
  },
  sheetTierPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 12,
    paddingVertical: 5,
    borderRadius: 20,
  },
  sheetTierDot: { width: 7, height: 7, borderRadius: 4 },
  sheetTierText: {
    fontSize: 12,
    fontFamily: 'Inter_700Bold',
    letterSpacing: 0.6,
    textTransform: 'uppercase',
  },
  sheetDescWrap: {
    borderRadius: 16,
    padding: 14,
    width: '100%',
    gap: 6,
  },
  sheetReqLabel: {
    fontSize: 11,
    fontFamily: 'Inter_700Bold',
    textTransform: 'uppercase',
    letterSpacing: 0.8,
  },
  sheetDesc: {
    fontSize: 14,
    fontFamily: 'Inter_400Regular',
    lineHeight: 22,
  },
  sheetEarnedRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  sheetEarnedText: {
    fontSize: 14,
    fontFamily: 'Inter_600SemiBold',
    color: Colors.green,
  },
  sheetCloseBtn: {
    paddingVertical: 14,
    paddingHorizontal: 40,
    borderRadius: 16,
    width: '100%',
    alignItems: 'center',
    marginTop: 4,
  },
  sheetCloseBtnText: {
    fontSize: 15,
    fontFamily: 'Inter_600SemiBold',
  },
});
