import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import React, { useState, useEffect, useCallback } from 'react';
import {
  ActivityIndicator,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useAuthStore } from '@/src/stores/authStore';
import { useTheme } from '@/src/context/ThemeContext';
import { CharacterAvatar } from '@/src/components/CharacterAvatar';
import { PremiumCard } from '@/src/components/PremiumCard';
import { getCharacter } from '@/src/data/characters';
import { CharacterId } from '@/src/types';
import { fetchLeaderboard, LeaderboardEntry } from '@/src/services/playerSyncService';

type Tab = 'global' | 'friends';
const RANK_COLORS = ['#FFD700', '#C0C0C0', '#CD7F32'];

export default function LeaderboardScreen() {
  const insets = useSafeAreaInsets();
  const profile = useAuthStore(s => s.profile);
  const { colors } = useTheme();
  const [tab, setTab] = useState<Tab>('global');
  const [leaders, setLeaders] = useState<LeaderboardEntry[]>([]);
  const [loading, setLoading] = useState(true);
  const [myRank, setMyRank] = useState<number | null>(null);

  const myWins = profile?.stats.totalWins ?? 0;
  const myMatches = profile?.stats.totalMatches ?? 0;
  const myRate = myMatches > 0 ? Math.round((myWins / myMatches) * 100) : 0;

  const loadLeaderboard = useCallback(async () => {
    setLoading(true);
    try {
      const data = await fetchLeaderboard(20);
      setLeaders(data);
      if (profile) {
        const idx = data.findIndex(e => e.id === profile.id);
        setMyRank(idx >= 0 ? idx + 1 : null);
      }
    } catch {
      setLeaders([]);
    } finally {
      setLoading(false);
    }
  }, [profile?.id]);

  useEffect(() => { loadLeaderboard(); }, [loadLeaderboard]);

  return (
    <View style={[styles.container, { paddingTop: insets.top, backgroundColor: colors.background }]}>
      <View style={styles.header}>
        <Pressable onPress={() => router.back()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={24} color={colors.textPrimary} />
        </Pressable>
        <Text style={[styles.title, { color: colors.textPrimary }]}>Leaderboard</Text>
        <Pressable onPress={loadLeaderboard} style={styles.backBtn}>
          <Ionicons name="refresh" size={20} color={colors.textMuted} />
        </Pressable>
      </View>

      <View style={[styles.tabs, { backgroundColor: colors.inputBg }]}>
        {(['global', 'friends'] as Tab[]).map(t => (
          <Pressable
            key={t}
            style={[styles.tab, tab === t && [styles.tabActive, { backgroundColor: colors.card }]]}
            onPress={() => setTab(t)}
          >
            <Text style={[
              styles.tabLabel,
              { color: colors.textFaint },
              tab === t && [styles.tabLabelActive, { color: colors.textPrimary }],
            ]}>
              {t === 'global' ? 'Global' : 'Friends'}
            </Text>
          </Pressable>
        ))}
      </View>

      {profile && (
        <PremiumCard style={styles.myCard}>
          <View style={styles.myRow}>
            <View style={[styles.myRankBadge, { backgroundColor: Colors.greenPale }]}>
              <Text style={[styles.myRankText, { color: Colors.green }]}>
                {myRank ? `#${myRank}` : '—'}
              </Text>
            </View>
            <CharacterAvatar character={getCharacter(profile.selectedCharacterId)} size={36} />
            <View style={styles.myInfo}>
              <Text style={[styles.myName, { color: colors.textPrimary }]}>{profile.name} (You)</Text>
              <Text style={[styles.mySub, { color: colors.textFaint }]}>{myWins} wins · {myRate}% win rate</Text>
            </View>
            <View style={styles.myStreak}>
              <Ionicons name="flame" size={14} color={Colors.coral} />
              <Text style={[styles.myStreakNum, { color: Colors.coral }]}>{profile.stats.streak}</Text>
            </View>
          </View>
        </PremiumCard>
      )}

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.list}>
        {tab === 'global' ? (
          loading ? (
            <View style={styles.centered}>
              <ActivityIndicator color={Colors.yellow} size="large" />
            </View>
          ) : leaders.length === 0 ? (
            <View style={styles.emptyState}>
              <Text style={{ fontSize: 48 }}>🏆</Text>
              <Text style={[styles.emptyTitle, { color: colors.textPrimary }]}>No players yet</Text>
              <Text style={[styles.emptyDesc, { color: colors.textSecondary }]}>
                Play your first match to appear on the leaderboard!
              </Text>
            </View>
          ) : (
            leaders.map((entry, i) => {
              const rank = i + 1;
              const charId = (entry.characterId as CharacterId) ?? 'nino';
              const isMe = profile?.id === entry.id;
              return (
                <Pressable
                  key={entry.id}
                  onPress={() => router.push(`/public-profile?id=${entry.id}`)}
                >
                  <PremiumCard style={[styles.entryCard, isMe && { borderColor: Colors.green + '60' }]}>
                    <View style={styles.entryRow}>
                      <View style={styles.rankContainer}>
                        {rank <= 3 ? (
                          <Ionicons name="trophy" size={20} color={RANK_COLORS[rank - 1]} />
                        ) : (
                          <Text style={[styles.rankNum, { color: colors.textMuted }]}>{rank}</Text>
                        )}
                      </View>
                      <CharacterAvatar character={getCharacter(charId)} size={36} />
                      <View style={styles.entryInfo}>
                        <Text style={[styles.entryName, { color: colors.textPrimary }]}>
                          {entry.name}{isMe ? <Text style={[styles.youTag, { color: colors.textMuted }]}> (You)</Text> : null}
                        </Text>
                        <Text style={[styles.entrySub, { color: colors.textFaint }]}>{entry.totalWins} wins · {entry.winRate}% win rate</Text>
                      </View>
                      <View style={styles.streakBadge}>
                        <Ionicons name="flame" size={12} color={Colors.coral} />
                        <Text style={[styles.streakNum, { color: Colors.coral }]}>{entry.streak}</Text>
                      </View>
                    </View>
                  </PremiumCard>
                </Pressable>
              );
            })
          )
        ) : (
          <View style={styles.emptyState}>
            <Ionicons name="people-outline" size={56} color={colors.textFaint} />
            <Text style={[styles.emptyTitle, { color: colors.textPrimary }]}>No friends yet</Text>
            <Text style={[styles.emptyDesc, { color: colors.textSecondary }]}>
              Create a friend room and share the code to challenge someone!
            </Text>
          </View>
        )}
        <View style={{ height: 32 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: 20, paddingVertical: 12,
  },
  backBtn: { width: 40, height: 40, alignItems: 'center', justifyContent: 'center' },
  title: { fontSize: 20, fontFamily: 'Inter_700Bold' },
  tabs: {
    flexDirection: 'row', marginHorizontal: 20,
    borderRadius: 12, padding: 4, marginBottom: 12,
  },
  tab: { flex: 1, paddingVertical: 8, alignItems: 'center', borderRadius: 10 },
  tabActive: {},
  tabLabel: { fontSize: 14, fontFamily: 'Inter_500Medium' },
  tabLabelActive: { fontFamily: 'Inter_600SemiBold' },
  myCard: { marginHorizontal: 20, marginBottom: 8 },
  myRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  myRankBadge: { width: 36, height: 36, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
  myRankText: { fontSize: 13, fontFamily: 'Inter_700Bold' },
  myInfo: { flex: 1 },
  myName: { fontSize: 14, fontFamily: 'Inter_600SemiBold' },
  youTag: { fontSize: 12, fontFamily: 'Inter_400Regular' },
  mySub: { fontSize: 12, fontFamily: 'Inter_400Regular' },
  myStreak: { flexDirection: 'row', alignItems: 'center', gap: 3 },
  myStreakNum: { fontSize: 14, fontFamily: 'Inter_700Bold' },
  list: { paddingHorizontal: 20, paddingBottom: 32, gap: 8 },
  centered: { paddingTop: 60, alignItems: 'center' },
  entryCard: {},
  entryRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  rankContainer: { width: 28, alignItems: 'center' },
  rankNum: { fontSize: 14, fontFamily: 'Inter_700Bold' },
  entryInfo: { flex: 1 },
  entryName: { fontSize: 14, fontFamily: 'Inter_600SemiBold' },
  entrySub: { fontSize: 12, fontFamily: 'Inter_400Regular' },
  streakBadge: { flexDirection: 'row', alignItems: 'center', gap: 3 },
  streakNum: { fontSize: 13, fontFamily: 'Inter_600SemiBold' },
  emptyState: { alignItems: 'center', gap: 12, paddingTop: 60 },
  emptyTitle: { fontSize: 18, fontFamily: 'Inter_600SemiBold' },
  emptyDesc: { fontSize: 14, fontFamily: 'Inter_400Regular', textAlign: 'center', lineHeight: 20, maxWidth: 260 },
});
