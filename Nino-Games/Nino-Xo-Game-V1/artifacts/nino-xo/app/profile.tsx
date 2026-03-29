import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import * as StoreReview from 'expo-store-review';
import React, { useEffect, useState } from 'react';
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
import { getCharacter } from '@/src/data/characters';
import { BouncingCharacter } from '@/src/components/BouncingCharacter';
import { PremiumCard } from '@/src/components/PremiumCard';
import { BADGES, getBadge } from '@/src/data/badges';
import { fetchPlayerChallenges, type PlayerChallenge } from '@/src/services/challengeService';
import { useTheme } from '@/src/context/ThemeContext';

function BadgeInfoSheet({ badge, isEarned, onClose }: {
  badge: ReturnType<typeof getBadge>;
  isEarned: boolean;
  onClose: () => void;
}) {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const translateY = useSharedValue(300);
  const opacity = useSharedValue(0);

  React.useEffect(() => {
    if (!badge) return;
    opacity.value = withTiming(1, { duration: 180 });
    translateY.value = withSpring(0, { damping: 18, stiffness: 200 });
  }, [badge]);

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

  if (!badge) return null;

  return (
    <Modal transparent animationType="none" statusBarTranslucent onRequestClose={handleClose}>
      <Animated.View style={[profileStyles.sheetOverlay, overlayStyle]}>
        <Pressable style={StyleSheet.absoluteFill} onPress={handleClose} />
        <Animated.View style={[profileStyles.sheet, { backgroundColor: colors.card, borderColor: colors.border }, sheetStyle, { paddingBottom: insets.bottom + 20 }]}>
          <View style={[profileStyles.sheetHandle, { backgroundColor: colors.borderMid }]} />
          <View style={[profileStyles.sheetIconWrap, { backgroundColor: isEarned ? badge.color : colors.inputBg }]}>
            <Ionicons
              name={badge.icon}
              size={36}
              color={isEarned ? Colors.white : colors.textFaint}
            />
          </View>
          <Text style={[profileStyles.sheetBadgeName, { color: isEarned ? badge.color : colors.textMuted }]}>
            {badge.name}
          </Text>
          <View style={[profileStyles.sheetDescWrap, { backgroundColor: isEarned ? badge.pale : colors.subtleBg }]}>
            <Text style={[profileStyles.sheetReqLabel, { color: colors.textFaint }]}>Requirement</Text>
            <Text style={[profileStyles.sheetDesc, { color: colors.textSecondary }]}>{badge.description}</Text>
          </View>
          {isEarned && (
            <View style={profileStyles.sheetEarnedRow}>
              <Ionicons name="checkmark-circle" size={16} color={Colors.green} />
              <Text style={profileStyles.sheetEarnedText}>Earned!</Text>
            </View>
          )}
          {!isEarned && (
            <View style={profileStyles.sheetLockedRow}>
              <Ionicons name="lock-closed" size={14} color={colors.textFaint} />
              <Text style={[profileStyles.sheetLockedText, { color: colors.textFaint }]}>Not yet unlocked</Text>
            </View>
          )}
          <Pressable style={[profileStyles.sheetCloseBtn, { backgroundColor: colors.inputBg }]} onPress={handleClose}>
            <Text style={[profileStyles.sheetCloseBtnText, { color: colors.textSecondary }]}>Close</Text>
          </Pressable>
        </Animated.View>
      </Animated.View>
    </Modal>
  );
}

export default function ProfileScreen() {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const { profile } = useAuth();
  const [selectedBadgeId, setSelectedBadgeId] = useState<string | null>(null);
  const [myChallenges, setMyChallenges] = useState<PlayerChallenge[]>([]);

  React.useEffect(() => {
    if (!profile) router.replace('/login');
  }, [profile]);

  useEffect(() => {
    if (!profile) return;
    let cancelled = false;
    fetchPlayerChallenges(profile.id).then((results) => {
      if (!cancelled) setMyChallenges(results);
    });
    return () => { cancelled = true; };
  }, [profile?.id]);

  if (!profile) return null;

  const character = getCharacter(profile.selectedCharacterId);
  const winRate = profile.stats.totalMatches > 0
    ? Math.round((profile.stats.totalWins / profile.stats.totalMatches) * 100)
    : 0;

  const earnedSet = new Set(profile.earnedBadgeIds ?? []);
  const selectedBadge = selectedBadgeId ? getBadge(selectedBadgeId) : undefined;

  return (
    <ScrollView
      style={[styles.container, { backgroundColor: colors.background }]}
      contentContainerStyle={[styles.content, { paddingTop: insets.top + 8, paddingBottom: insets.bottom + 24 }]}
      showsVerticalScrollIndicator={false}
    >
      <View style={styles.toolbar}>
        <Pressable onPress={() => router.back()} style={styles.backBtn}>
          <Ionicons name="chevron-back" size={26} color={colors.textPrimary} />
        </Pressable>
        <Text style={[styles.toolbarTitle, { color: colors.textPrimary }]}>Profile</Text>
        <Pressable onPress={() => router.push('/settings')} style={styles.backBtn}>
          <Ionicons name="settings-outline" size={22} color={colors.textMuted} />
        </Pressable>
      </View>

      <PremiumCard style={[styles.profileCard, { backgroundColor: character.accentPale }]}>
        <BouncingCharacter character={character} size={130} reactionType="idle" />
        <Text style={[styles.profileName, { color: character.accentColor }]}>{profile.name}</Text>
        <Text style={[styles.profileChar, { color: colors.textSecondary }]}>{character.name} · {character.personality}</Text>
        <View style={[styles.premiumBadge, { backgroundColor: character.accentColor }]}>
          <Ionicons name="checkmark-circle" size={14} color={Colors.white} />
          <Text style={styles.premiumBadgeText}>Premium Member</Text>
        </View>
      </PremiumCard>

      <View style={styles.statsGrid}>
        <View style={[styles.bigStat, { backgroundColor: Colors.greenPale }]}>
          <Text style={[styles.bigStatNum, { color: Colors.green }]}>{profile.stats.totalWins}</Text>
          <Text style={[styles.bigStatLabel, { color: colors.textSecondary }]}>Total Wins</Text>
        </View>
        <View style={[styles.bigStat, { backgroundColor: Colors.coralPale }]}>
          <Text style={[styles.bigStatNum, { color: Colors.coral }]}>{winRate}%</Text>
          <Text style={[styles.bigStatLabel, { color: colors.textSecondary }]}>Win Rate</Text>
        </View>
        <View style={[styles.bigStat, { backgroundColor: Colors.yellowPale }]}>
          <Text style={[styles.bigStatNum, { color: Colors.yellow }]}>{profile.stats.streak}</Text>
          <Text style={[styles.bigStatLabel, { color: colors.textSecondary }]}>Streak</Text>
        </View>
        <View style={[styles.bigStat, { backgroundColor: Colors.purplePale }]}>
          <Text style={[styles.bigStatNum, { color: Colors.purple }]}>{profile.stats.bestStreak}</Text>
          <Text style={[styles.bigStatLabel, { color: colors.textSecondary }]}>Best Streak</Text>
        </View>
      </View>

      <Pressable onPress={() => router.push('/match-history')}>
        <PremiumCard>
          <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
            <Text style={[styles.sectionTitle, { color: colors.textPrimary }]}>Match History</Text>
            <Ionicons name="chevron-forward" size={18} color={colors.textMuted} />
          </View>
          <View style={styles.matchRow}>
            <Ionicons name="game-controller-outline" size={18} color={colors.textMuted} />
            <Text style={[styles.matchLabel, { color: colors.textSecondary }]}>Total Matches Played</Text>
            <Text style={[styles.matchVal, { color: colors.textPrimary }]}>{profile.stats.totalMatches}</Text>
          </View>
          <View style={[styles.divider, { backgroundColor: colors.border }]} />
          <View style={styles.matchRow}>
            <Ionicons name="trophy-outline" size={18} color={colors.textMuted} />
            <Text style={[styles.matchLabel, { color: colors.textSecondary }]}>Wins</Text>
            <Text style={[styles.matchVal, { color: Colors.green }]}>{profile.stats.totalWins}</Text>
          </View>
          <View style={[styles.divider, { backgroundColor: colors.border }]} />
          <View style={styles.matchRow}>
            <Ionicons name="close-circle-outline" size={18} color={colors.textMuted} />
            <Text style={[styles.matchLabel, { color: colors.textSecondary }]}>Losses</Text>
            <Text style={[styles.matchVal, { color: Colors.coral }]}>
              {profile.stats.totalMatches - profile.stats.totalWins}
            </Text>
          </View>
        </PremiumCard>
      </Pressable>

      <Pressable onPress={() => router.push('/stats')}>
        <PremiumCard>
          <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
            <Text style={[styles.sectionTitle, { color: colors.textPrimary }]}>Detailed Stats</Text>
            <Ionicons name="chevron-forward" size={18} color={colors.textMuted} />
          </View>
          <Text style={[{ fontSize: 13, fontFamily: 'Inter_400Regular', marginTop: 4 }, { color: colors.textSecondary }]}>
            View win rates, streaks, and full performance breakdown
          </Text>
        </PremiumCard>
      </Pressable>

      <PremiumCard>
        <Text style={[styles.sectionTitle, { color: colors.textPrimary }]}>Daily Mission</Text>
        <View style={styles.missionRow}>
          <Ionicons name="flame" size={20} color={Colors.purple} />
          <View style={styles.missionInfo}>
            <Text style={[styles.missionTitle, { color: colors.textPrimary }]}>Play {profile.stats.missionGoal} matches</Text>
            <Text style={[styles.missionSub, { color: colors.textMuted }]}>{profile.stats.missionProgress} / {profile.stats.missionGoal} completed</Text>
          </View>
        </View>
        <View style={[styles.progressBar, { backgroundColor: colors.inputBg }]}>
          <View style={[styles.progressFill, {
            width: `${Math.min(100, Math.round(profile.stats.missionProgress / profile.stats.missionGoal * 100))}%`,
            backgroundColor: Colors.purple,
          }]} />
        </View>
      </PremiumCard>

      <PremiumCard>
        <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
          <Text style={[styles.sectionTitle, { color: colors.textPrimary }]}>Badges</Text>
          <Pressable onPress={() => router.push('/badges')}>
            <Text style={styles.viewAllLink}>{earnedSet.size}/{BADGES.length} earned</Text>
          </Pressable>
        </View>
        <View style={styles.badgesGrid}>
          {BADGES.map((badge) => {
            const isEarned = earnedSet.has(badge.id);
            return (
              <Pressable
                key={badge.id}
                style={[
                  styles.badgePip,
                  isEarned ? { backgroundColor: badge.pale } : { backgroundColor: colors.subtleBg },
                ]}
                onPress={() => setSelectedBadgeId(badge.id)}
              >
                <Ionicons
                  name={badge.icon}
                  size={18}
                  color={isEarned ? badge.color : colors.textVeryFaint}
                />
                {!isEarned && (
                  <View style={[styles.lockOverlay, { backgroundColor: colors.card + 'E6' }]}>
                    <Ionicons name="lock-closed" size={8} color={colors.textFaint} />
                  </View>
                )}
              </Pressable>
            );
          })}
        </View>
      </PremiumCard>

      {myChallenges.length > 0 && (
        <PremiumCard>
          <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 }}>
            <Text style={[styles.sectionTitle, { color: colors.textPrimary }]}>My Challenges</Text>
            <Ionicons name="trophy" size={16} color={Colors.purple} />
          </View>
          {myChallenges.map((c) => {
            const isCompleted = c.status === 'completed';
            const isCancelled = c.status === 'cancelled';
            const chipColor = isCompleted
              ? (c.rewardReceived ? Colors.yellow : Colors.purple)
              : isCancelled ? colors.textMuted
              : Colors.green;
            const chipBg = chipColor + '18';
            const chipBorder = chipColor + '40';
            const statusLabel = isCompleted ? 'ENDED' : isCancelled ? 'CANCELLED' : c.status === 'active' ? 'LIVE' : 'UPCOMING';
            return (
              <Pressable
                key={c.id}
                onPress={() => router.push(`/challenge-detail?id=${c.id}`)}
                style={[profileStyles.challengeBadgeRow, { borderColor: chipBorder, backgroundColor: chipBg }]}
              >
                <View style={[profileStyles.challengeBadgeIcon, { backgroundColor: chipColor + '22' }]}>
                  <Ionicons
                    name={isCompleted ? (c.rewardReceived ? 'gift' : 'ribbon') : 'flash'}
                    size={14}
                    color={chipColor}
                  />
                </View>
                <View style={{ flex: 1 }}>
                  <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
                    <Text style={[profileStyles.challengeBadgeName, { color: colors.textPrimary }]} numberOfLines={1}>
                      {c.title}
                    </Text>
                    <View style={[profileStyles.challengeStatusChip, { backgroundColor: chipColor + '30' }]}>
                      <Text style={[profileStyles.challengeStatusText, { color: chipColor }]}>{statusLabel}</Text>
                    </View>
                  </View>
                  {c.rewardReceived ? (
                    <Text style={[profileStyles.challengeBadgeStat, { color: Colors.yellow }]}>
                      Rank #{c.rewardReceived.rank} · Won 🪙 {c.rewardReceived.amount}
                    </Text>
                  ) : (
                    <Text style={[profileStyles.challengeBadgeStat, { color: colors.textFaint }]}>
                      {c.myWins > 0 || c.myLosses > 0
                        ? `${c.myWins}W / ${c.myLosses}L`
                        : isCompleted ? 'No matches played' : 'Joined — play to rank up!'}
                    </Text>
                  )}
                </View>
                <Ionicons name="chevron-forward" size={14} color={colors.textFaint} />
              </Pressable>
            );
          })}
        </PremiumCard>
      )}

      <Pressable onPress={() => router.push('/characters')} style={[styles.changeCharBtn, { backgroundColor: Colors.bluePale }]}>
        <Text style={styles.changeCharText}>Change Character</Text>
        <Ionicons name="chevron-forward" size={18} color={Colors.blue} />
      </Pressable>

      <Pressable
        onPress={async () => {
          const available = await StoreReview.isAvailableAsync();
          if (available) {
            await StoreReview.requestReview();
          }
        }}
        style={[styles.rateBtn, { backgroundColor: 'rgba(255,215,0,0.08)' }]}
      >
        <View style={styles.rateIconWrap}>
          <Ionicons name="star" size={18} color="#FFD700" />
        </View>
        <Text style={[styles.rateBtnText, { color: colors.textPrimary }]}>Rate the App</Text>
        <Ionicons name="chevron-forward" size={18} color={colors.textFaint} />
      </Pressable>

      {selectedBadge && (
        <BadgeInfoSheet
          badge={selectedBadge}
          isEarned={earnedSet.has(selectedBadge.id)}
          onClose={() => setSelectedBadgeId(null)}
        />
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  content: { paddingHorizontal: 20, gap: 16 },
  toolbar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingBottom: 8,
  },
  backBtn: { width: 40, height: 40, alignItems: 'center', justifyContent: 'center' },
  toolbarTitle: { fontSize: 17, fontFamily: 'Inter_600SemiBold' },
  profileCard: { alignItems: 'center', gap: 8 },
  profileName: { fontSize: 28, fontFamily: 'Inter_700Bold' },
  profileChar: { fontSize: 14, fontFamily: 'Inter_400Regular' },
  premiumBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 12,
    marginTop: 4,
  },
  premiumBadgeText: { fontSize: 12, fontFamily: 'Inter_600SemiBold', color: Colors.white },
  statsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  bigStat: {
    flex: 1,
    minWidth: '45%',
    borderRadius: 18,
    padding: 16,
    alignItems: 'center',
    gap: 4,
  },
  bigStatNum: { fontSize: 34, fontFamily: 'Inter_700Bold' },
  bigStatLabel: { fontSize: 12, fontFamily: 'Inter_400Regular' },
  sectionTitle: { fontSize: 16, fontFamily: 'Inter_700Bold', marginBottom: 12 },
  matchRow: { flexDirection: 'row', alignItems: 'center', gap: 10, paddingVertical: 4 },
  matchLabel: { flex: 1, fontSize: 14, fontFamily: 'Inter_400Regular' },
  matchVal: { fontSize: 16, fontFamily: 'Inter_700Bold' },
  divider: { height: 1, marginVertical: 8 },
  missionRow: { flexDirection: 'row', alignItems: 'center', gap: 12, marginBottom: 12 },
  missionInfo: { flex: 1 },
  missionTitle: { fontSize: 14, fontFamily: 'Inter_600SemiBold' },
  missionSub: { fontSize: 12, fontFamily: 'Inter_400Regular', marginTop: 2 },
  progressBar: {
    height: 8,
    borderRadius: 4,
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    borderRadius: 4,
    minWidth: 8,
  },
  changeCharBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    padding: 16,
    borderRadius: 18,
  },
  changeCharText: { fontSize: 15, fontFamily: 'Inter_600SemiBold', color: Colors.blue },
  rateBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    padding: 16,
    borderRadius: 18,
    marginBottom: 8,
  },
  rateIconWrap: {
    width: 32,
    height: 32,
    borderRadius: 10,
    backgroundColor: 'rgba(255,215,0,0.18)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  rateBtnText: { flex: 1, fontSize: 15, fontFamily: 'Inter_600SemiBold' },

  viewAllLink: { fontSize: 13, fontFamily: 'Inter_600SemiBold', color: Colors.purple },
  badgesGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginTop: 4,
  },
  badgePip: {
    width: 40, height: 40, borderRadius: 12,
    alignItems: 'center', justifyContent: 'center',
    position: 'relative',
  },
  lockOverlay: {
    position: 'absolute', bottom: 2, right: 2,
    width: 13, height: 13, borderRadius: 7,
    alignItems: 'center', justifyContent: 'center',
  },
});

const profileStyles = StyleSheet.create({
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
    width: 40, height: 4, borderRadius: 2,
    marginBottom: 4,
  },
  sheetIconWrap: {
    width: 68, height: 68, borderRadius: 20,
    alignItems: 'center', justifyContent: 'center',
  },
  sheetBadgeName: {
    fontSize: 20, fontFamily: 'Inter_700Bold', textAlign: 'center',
  },
  sheetDescWrap: {
    borderRadius: 14, padding: 14, width: '100%', gap: 5,
  },
  sheetReqLabel: {
    fontSize: 10, fontFamily: 'Inter_700Bold',
    textTransform: 'uppercase', letterSpacing: 0.8,
  },
  sheetDesc: {
    fontSize: 14, fontFamily: 'Inter_400Regular',
    lineHeight: 22,
  },
  sheetEarnedRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  sheetEarnedText: { fontSize: 14, fontFamily: 'Inter_600SemiBold', color: Colors.green },
  sheetLockedRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  sheetLockedText: { fontSize: 14, fontFamily: 'Inter_600SemiBold' },
  sheetCloseBtn: {
    paddingVertical: 14, paddingHorizontal: 40,
    borderRadius: 16, width: '100%', alignItems: 'center', marginTop: 4,
  },
  sheetCloseBtnText: {
    fontSize: 15, fontFamily: 'Inter_600SemiBold',
  },
  challengeBadgeRow: {
    flexDirection: 'row', alignItems: 'center', gap: 10,
    paddingVertical: 10, paddingHorizontal: 12, marginBottom: 8,
    borderRadius: 14, borderWidth: 1,
  },
  challengeBadgeIcon: {
    width: 30, height: 30, borderRadius: 10,
    alignItems: 'center', justifyContent: 'center',
  },
  challengeBadgeName: { fontSize: 14, fontFamily: 'Inter_600SemiBold', flexShrink: 1 },
  challengeBadgeStat: { fontSize: 12, fontFamily: 'Inter_400Regular', marginTop: 2 },
  challengeStatusChip: {
    borderRadius: 6, paddingHorizontal: 5, paddingVertical: 2,
  },
  challengeStatusText: { fontSize: 9, fontFamily: 'Inter_700Bold', letterSpacing: 0.6 },
});
