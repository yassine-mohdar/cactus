import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import React, { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useAuth } from '@/src/context/AuthContext';
import { useTheme } from '@/src/context/ThemeContext';
import { fetchAllChallenges, type Challenge } from '@/src/services/challengeService';

function statusColor(status: Challenge['status']): string {
  switch (status) {
    case 'active': return Colors.green;
    case 'upcoming': return Colors.yellow;
    case 'completed': return Colors.purple;
    case 'cancelled': return Colors.coral;
    default: return Colors.purple;
  }
}

function statusLabel(status: Challenge['status']): string {
  switch (status) {
    case 'active': return 'LIVE';
    case 'upcoming': return 'UPCOMING';
    case 'completed': return 'ENDED';
    case 'cancelled': return 'CANCELLED';
    default: return status.toUpperCase();
  }
}

function timeLabel(challenge: Challenge): string {
  const now = Date.now();
  const start = new Date(challenge.startAt).getTime();
  const end = new Date(challenge.endAt).getTime();

  if (challenge.status === 'active') {
    const diff = end - now;
    if (diff <= 0) return 'Ending soon';
    const hours = Math.floor(diff / 3600000);
    const mins = Math.floor((diff % 3600000) / 60000);
    if (hours > 24) return `${Math.floor(hours / 24)}d remaining`;
    if (hours > 0) return `${hours}h ${mins}m remaining`;
    return `${mins}m remaining`;
  }

  if (challenge.status === 'upcoming') {
    const diff = start - now;
    if (diff <= 0) return 'Starting soon';
    const hours = Math.floor(diff / 3600000);
    if (hours > 24) return `Starts in ${Math.floor(hours / 24)}d`;
    if (hours > 0) return `Starts in ${hours}h`;
    return `Starts in ${Math.floor((diff % 3600000) / 60000)}m`;
  }

  if (challenge.status === 'completed') {
    return 'Ended ' + new Date(challenge.endAt).toLocaleDateString();
  }

  return '';
}

function ChallengeCard({
  challenge,
  onPress,
  colors,
}: {
  challenge: Challenge;
  onPress: () => void;
  colors: ReturnType<typeof useTheme>['colors'];
}) {
  const color = statusColor(challenge.status);
  const time = timeLabel(challenge);
  const isCompleted = challenge.status === 'completed';
  const isCancelled = challenge.status === 'cancelled';

  return (
    <Pressable
      onPress={onPress}
      style={({ pressed }) => [
        styles.card,
        { backgroundColor: colors.card, borderColor: colors.border },
        (isCompleted || isCancelled) && styles.cardDimmed,
        pressed && { opacity: 0.85 },
      ]}
    >
      <View style={[styles.cardAccent, { backgroundColor: color }]} />
      <View style={styles.cardBody}>
        <View style={styles.cardTop}>
          <View style={[styles.statusChip, { backgroundColor: color + '22', borderColor: color + '50' }]}>
            {challenge.status === 'active' && <View style={[styles.statusDot, { backgroundColor: color }]} />}
            <Text style={[styles.statusText, { color }]}>{statusLabel(challenge.status)}</Text>
          </View>
          {challenge.entryFee > 0 && (
            <View style={[styles.feeChip, { backgroundColor: Colors.yellow + '18' }]}>
              <Text style={[styles.feeText, { color: Colors.yellow }]}>🪙 {challenge.entryFee}</Text>
            </View>
          )}
        </View>

        <Text style={[styles.cardTitle, { color: colors.textPrimary }, (isCompleted || isCancelled) && { opacity: 0.7 }]}>
          {challenge.title}
        </Text>
        {challenge.description ? (
          <Text style={[styles.cardDesc, { color: colors.textSecondary }]} numberOfLines={2}>{challenge.description}</Text>
        ) : null}

        <View style={styles.cardFooter}>
          <View style={styles.footerRow}>
            <Ionicons name="people" size={13} color={colors.textMuted} />
            <Text style={[styles.footerText, { color: colors.textMuted }]}>{challenge.participantCount} joined</Text>
            {challenge.maxParticipants ? (
              <Text style={[styles.footerMuted, { color: colors.textFaint }]}>/ {challenge.maxParticipants} max</Text>
            ) : null}
          </View>
          {challenge.prizePool > 0 && (
            <View style={styles.prizeRow}>
              <Ionicons name="trophy" size={13} color={Colors.yellow} />
              <Text style={[styles.prizeText, { color: Colors.yellow }]}>{challenge.prizePool} prize pool</Text>
            </View>
          )}
        </View>

        {time ? (
          <Text style={[styles.timeText, { color }]}>{time}</Text>
        ) : null}
      </View>
      <View style={styles.cardArrow}>
        <Ionicons name="chevron-forward" size={18} color={colors.textFaint} />
      </View>
    </Pressable>
  );
}

function SectionHeader({ title, count, colors }: {
  title: string;
  count: number;
  colors: ReturnType<typeof useTheme>['colors'];
}) {
  return (
    <View style={styles.sectionHeader}>
      <Text style={[styles.sectionTitle, { color: colors.textMuted }]}>{title}</Text>
      {count > 0 && (
        <View style={[styles.sectionBadge, { backgroundColor: colors.inputBg }]}>
          <Text style={[styles.sectionBadgeText, { color: colors.textSecondary }]}>{count}</Text>
        </View>
      )}
    </View>
  );
}

export default function ChallengesScreen() {
  const insets = useSafeAreaInsets();
  const { profile } = useAuth();
  const { colors } = useTheme();
  const [challenges, setChallenges] = useState<Challenge[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const load = useCallback(async () => {
    const data = await fetchAllChallenges();
    setChallenges(data);
    setLoading(false);
    setRefreshing(false);
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const onRefresh = () => {
    setRefreshing(true);
    load();
  };

  if (!profile) return null;

  const active = challenges.filter((c) => c.status === 'active');
  const upcoming = challenges.filter((c) => c.status === 'upcoming');
  const completed = challenges.filter((c) => c.status === 'completed');
  const cancelled = challenges.filter((c) => c.status === 'cancelled');

  const hasAny = challenges.length > 0;

  const goToDetail = (id: string) => router.push(`/challenge-detail?id=${id}`);

  return (
    <View style={[styles.container, { paddingTop: insets.top, backgroundColor: colors.background }]}>
      <View style={styles.header}>
        <Pressable onPress={() => router.back()} style={[styles.backBtn, { backgroundColor: colors.inputBg }]}>
          <Ionicons name="arrow-back" size={22} color={colors.textPrimary} />
        </Pressable>
        <View>
          <Text style={[styles.headerTitle, { color: colors.textPrimary }]}>Challenges</Text>
          <Text style={[styles.headerSub, { color: colors.textMuted }]}>Compete for prizes</Text>
        </View>
        <View style={[styles.purpleIcon, { backgroundColor: Colors.purple + '18' }]}>
          <Ionicons name="trophy" size={20} color={Colors.purple} />
        </View>
      </View>

      {loading ? (
        <View style={styles.center}>
          <ActivityIndicator color={Colors.purple} size="large" />
        </View>
      ) : !hasAny ? (
        <View style={[styles.center, { paddingTop: 60 }]}>
          <Ionicons name="trophy-outline" size={56} color={colors.textFaint} />
          <Text style={[styles.emptyTitle, { color: colors.textMuted }]}>No challenges yet</Text>
          <Text style={[styles.emptyDesc, { color: colors.textFaint }]}>Check back soon for upcoming tournaments!</Text>
        </View>
      ) : (
        <ScrollView
          contentContainerStyle={[styles.list, { paddingBottom: insets.bottom + 20 }]}
          showsVerticalScrollIndicator={false}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={Colors.purple} />
          }
        >
          {active.length > 0 && (
            <>
              <SectionHeader title="Live Now" count={active.length} colors={colors} />
              {active.map((c) => (
                <ChallengeCard key={c.id} challenge={c} onPress={() => goToDetail(c.id)} colors={colors} />
              ))}
            </>
          )}

          {upcoming.length > 0 && (
            <>
              <SectionHeader title="Upcoming" count={upcoming.length} colors={colors} />
              {upcoming.map((c) => (
                <ChallengeCard key={c.id} challenge={c} onPress={() => goToDetail(c.id)} colors={colors} />
              ))}
            </>
          )}

          {completed.length > 0 && (
            <>
              <SectionHeader title="Completed" count={completed.length} colors={colors} />
              {completed.map((c) => (
                <ChallengeCard key={c.id} challenge={c} onPress={() => goToDetail(c.id)} colors={colors} />
              ))}
            </>
          )}

          {cancelled.length > 0 && (
            <>
              <SectionHeader title="Cancelled" count={cancelled.length} colors={colors} />
              {cancelled.map((c) => (
                <ChallengeCard key={c.id} challenge={c} onPress={() => goToDetail(c.id)} colors={colors} />
              ))}
            </>
          )}
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },

  header: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 18,
    paddingVertical: 14,
    gap: 12,
  },
  backBtn: {
    width: 40, height: 40, borderRadius: 12,
    alignItems: 'center', justifyContent: 'center',
  },
  headerTitle: { fontSize: 20, fontFamily: 'Inter_700Bold' },
  headerSub: { fontSize: 12, fontFamily: 'Inter_400Regular', marginTop: 1 },
  purpleIcon: {
    marginLeft: 'auto',
    width: 40, height: 40, borderRadius: 12,
    alignItems: 'center', justifyContent: 'center',
  },

  list: { paddingHorizontal: 18, gap: 10, paddingTop: 4 },

  sectionHeader: {
    flexDirection: 'row', alignItems: 'center', gap: 8, marginTop: 8, marginBottom: 4,
  },
  sectionTitle: { fontSize: 13, fontFamily: 'Inter_700Bold', letterSpacing: 0.8, textTransform: 'uppercase' },
  sectionBadge: {
    borderRadius: 10,
    paddingHorizontal: 7, paddingVertical: 2,
  },
  sectionBadgeText: { fontSize: 11, fontFamily: 'Inter_700Bold' },

  card: {
    borderRadius: 20,
    flexDirection: 'row',
    overflow: 'hidden',
    borderWidth: 1,
  },
  cardDimmed: { opacity: 0.75 },
  cardAccent: { width: 4, borderTopLeftRadius: 20, borderBottomLeftRadius: 20 },
  cardBody: { flex: 1, padding: 16, gap: 6 },
  cardTop: { flexDirection: 'row', alignItems: 'center', gap: 8, flexWrap: 'wrap' },
  statusChip: {
    flexDirection: 'row', alignItems: 'center', gap: 5,
    paddingHorizontal: 10, paddingVertical: 4,
    borderRadius: 20, borderWidth: 1,
  },
  statusDot: { width: 6, height: 6, borderRadius: 3 },
  statusText: { fontSize: 10, fontFamily: 'Inter_700Bold', letterSpacing: 0.8 },
  feeChip: {
    paddingHorizontal: 8, paddingVertical: 3,
    borderRadius: 8,
  },
  feeText: { fontSize: 11, fontFamily: 'Inter_600SemiBold' },

  cardTitle: { fontSize: 17, fontFamily: 'Inter_700Bold', marginTop: 2 },
  cardDesc: { fontSize: 13, fontFamily: 'Inter_400Regular', lineHeight: 18 },

  cardFooter: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 4 },
  footerRow: { flexDirection: 'row', alignItems: 'center', gap: 5 },
  footerText: { fontSize: 12, fontFamily: 'Inter_400Regular' },
  footerMuted: { fontSize: 12, fontFamily: 'Inter_400Regular' },
  prizeRow: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  prizeText: { fontSize: 12, fontFamily: 'Inter_600SemiBold' },

  timeText: { fontSize: 11, fontFamily: 'Inter_600SemiBold', marginTop: 2 },

  cardArrow: { alignSelf: 'center', paddingRight: 14 },

  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  emptyTitle: { fontSize: 18, fontFamily: 'Inter_700Bold', marginTop: 16 },
  emptyDesc: { fontSize: 13, fontFamily: 'Inter_400Regular', textAlign: 'center', maxWidth: 260 },
});
