import { Ionicons } from '@expo/vector-icons';
import React, { useState, useEffect, useCallback } from 'react';
import { ActivityIndicator, Image, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { router } from 'expo-router';
import { Colors } from '@/constants/colors';
import { useAuthStore } from '@/src/stores/authStore';
import { getCharacter } from '@/src/data/characters';
import { CharacterAvatar } from '@/src/components/CharacterAvatar';
import { fetchLeaderboard, LeaderboardEntry } from '@/src/services/playerSyncService';
import { CharacterId } from '@/src/types';
import { realtimeService } from '@/src/services/realtimeService';
import { API_BASE_URL } from '@/src/config';
import { useTheme } from '@/src/context/ThemeContext';

const RANK_COLORS: Record<number, string> = { 1: '#FFD700', 2: '#C0C0C0', 3: '#CD7F32' };
type Tab = 'global' | 'friends';

interface FriendEntry {
  id: string;
  name: string;
  characterId: string;
  hasSubscription: boolean;
  isOnline: boolean;
  isPlaying: boolean;
  socketId: string | null;
  stats: {
    totalMatches: number;
    totalWins: number;
    totalLosses: number;
    totalDraws: number;
    streak: number;
    bestStreak: number;
  };
}

type InviteState = 'idle' | 'sent';

export default function RankingsTab() {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const profile = useAuthStore(s => s.profile);
  const [tab, setTab] = useState<Tab>('global');
  const [leaders, setLeaders] = useState<LeaderboardEntry[]>([]);
  const [loading, setLoading] = useState(true);
  const [myRank, setMyRank] = useState<number | null>(null);

  const [friends, setFriends] = useState<FriendEntry[]>([]);
  const [friendsLoading, setFriendsLoading] = useState(false);
  const [inviteStates, setInviteStates] = useState<Record<string, InviteState>>({});

  const myWins = profile?.stats.totalWins ?? 0;
  const myMatches = profile?.stats.totalMatches ?? 0;
  const myRate = myMatches > 0 ? Math.round((myWins / myMatches) * 100) : 0;
  const character = profile ? getCharacter(profile.selectedCharacterId) : null;

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

  const loadFriends = useCallback(async () => {
    if (!profile) return;
    setFriendsLoading(true);
    try {
      const base = API_BASE_URL;
      const res = await fetch(`${base}/follows/friends?profileId=${profile.id}`);
      if (!res.ok) throw new Error('Failed');
      const data = await res.json() as { friends: FriendEntry[] };
      setFriends(data.friends);
    } catch {
      setFriends([]);
    } finally {
      setFriendsLoading(false);
    }
  }, [profile?.id]);

  useEffect(() => { loadLeaderboard(); }, [loadLeaderboard]);

  useEffect(() => {
    if (tab === 'friends') {
      loadFriends();
      setInviteStates({});
    }
  }, [tab, loadFriends]);

  const handleInvite = (friend: FriendEntry) => {
    if (!friend.socketId || !friend.isOnline || friend.isPlaying) return;
    realtimeService.sendRematchInvite(friend.socketId);
    setInviteStates(prev => ({ ...prev, [friend.id]: 'sent' }));
  };

  const getStatusLabel = (f: FriendEntry): string => {
    if (!f.isOnline) return 'Offline';
    if (f.isPlaying) return 'In Match';
    return 'Online';
  };

  const getStatusColor = (f: FriendEntry): string => {
    if (!f.isOnline) return colors.textFaint;
    if (f.isPlaying) return Colors.coral;
    return Colors.green;
  };

  return (
    <View style={[styles.container, { paddingTop: insets.top + 14, backgroundColor: colors.background }]}>
      <View style={[styles.blob, { top: -40, right: -60, backgroundColor: '#FFD700', width: 220, height: 220, opacity: 0.06 }]} />

      <View style={styles.header}>
        <View>
          <Text style={[styles.title, { color: colors.textPrimary }]}>Rankings</Text>
          <Text style={[styles.subtitle, { color: colors.textFaint }]}>Top players this season</Text>
        </View>
        <Pressable onPress={loadLeaderboard} style={styles.trophyCircle}>
          <Ionicons name="trophy" size={24} color="#FFD700" />
        </Pressable>
      </View>

      {profile && character && (
        <View style={[styles.myCard, { backgroundColor: colors.card, borderColor: character.accentColor + '40' }]}>
          <View style={[styles.myRankBadge, { backgroundColor: Colors.green + '22' }]}>
            <Text style={[styles.myRankText, { color: Colors.green }]}>
              {myRank ? `#${myRank}` : '—'}
            </Text>
          </View>
          <Image source={character.image} style={styles.myAvatar} resizeMode="contain" />
          <View style={styles.myInfo}>
            <Text style={[styles.myName, { color: colors.textPrimary }]}>{profile.name} <Text style={[styles.youTag, { color: colors.textMuted }]}>(You)</Text></Text>
            <Text style={[styles.mySub, { color: colors.textMuted }]}>{myWins} wins · {myRate}% win rate</Text>
          </View>
          <View style={styles.myStreak}>
            <Ionicons name="flame" size={14} color={Colors.coral} />
            <Text style={styles.myStreakNum}>{profile.stats.streak}</Text>
          </View>
        </View>
      )}

      <View style={[styles.tabs, { backgroundColor: colors.inputBg }]}>
        {(['global', 'friends'] as Tab[]).map(t => (
          <Pressable key={t} style={[styles.tabItem, tab === t && [styles.tabActive, { backgroundColor: colors.borderMid }]]} onPress={() => setTab(t)}>
            <Text style={[styles.tabLabel, { color: colors.textFaint }, tab === t && [styles.tabLabelActive, { color: colors.textPrimary }]]}>
              {t === 'global' ? '🌍  Global' : '👥  Friends'}
            </Text>
          </Pressable>
        ))}
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.list}>
        {tab === 'global' ? (
          loading ? (
            <View style={styles.centered}>
              <ActivityIndicator color={Colors.yellow} size="large" />
            </View>
          ) : leaders.length === 0 ? (
            <View style={styles.empty}>
              <Text style={styles.emptyEmoji}>🏆</Text>
              <Text style={[styles.emptyTitle, { color: colors.textPrimary }]}>No players yet</Text>
              <Text style={[styles.emptyDesc, { color: colors.textMuted }]}>Play your first match to appear on the leaderboard!</Text>
            </View>
          ) : (
            leaders.map((e, i) => {
              const rank = i + 1;
              const charId = (e.characterId as CharacterId) ?? 'nino';
              const isMe = profile?.id === e.id;
              return (
                <Pressable
                  key={e.id}
                  style={[styles.row, { backgroundColor: colors.card, borderColor: colors.border }, rank <= 3 && { borderColor: '#FFD70025' }, isMe && { borderColor: Colors.green + '40', backgroundColor: Colors.green + '10' }]}
                  onPress={() => router.push(`/public-profile?id=${e.id}`)}
                >
                  <View style={styles.rankCol}>
                    {rank <= 3
                      ? <Ionicons name="trophy" size={20} color={RANK_COLORS[rank]} />
                      : <Text style={[styles.rankNum, { color: colors.textMuted }]}>{rank}</Text>}
                  </View>
                  <CharacterAvatar character={getCharacter(charId)} size={40} />
                  <View style={styles.entryInfo}>
                    <Text style={[styles.entryName, { color: colors.textPrimary }]}>
                      {e.name}{isMe ? <Text style={[styles.youTag, { color: colors.textMuted }]}> (You)</Text> : null}
                    </Text>
                    <Text style={[styles.entrySub, { color: colors.textMuted }]}>{e.totalWins} wins · {e.winRate}% rate</Text>
                  </View>
                  <View style={styles.streakBadge}>
                    <Ionicons name="flame" size={12} color={Colors.coral} />
                    <Text style={styles.streakNum}>{e.streak}</Text>
                  </View>
                </Pressable>
              );
            })
          )
        ) : (
          friendsLoading ? (
            <View style={styles.centered}>
              <ActivityIndicator color={Colors.yellow} size="large" />
            </View>
          ) : friends.length === 0 ? (
            <View style={styles.empty}>
              <Text style={styles.emptyEmoji}>👥</Text>
              <Text style={[styles.emptyTitle, { color: colors.textPrimary }]}>No friends yet</Text>
              <Text style={[styles.emptyDesc, { color: colors.textMuted }]}>Follow someone back to see them here!</Text>
            </View>
          ) : (
            friends.map((f) => {
              const charId = (f.characterId as CharacterId) ?? 'nino';
              const isMe = profile?.id === f.id;
              const inviteState = inviteStates[f.id] ?? 'idle';
              const canInvite = f.isOnline && !f.isPlaying && !!f.socketId && !isMe;
              const statusColor = getStatusColor(f);
              const statusLabel = getStatusLabel(f);
              return (
                <Pressable
                  key={f.id}
                  style={[styles.row, { backgroundColor: colors.card, borderColor: colors.border }]}
                  onPress={() => router.push(`/public-profile?id=${f.id}`)}
                >
                  <CharacterAvatar character={getCharacter(charId)} size={44} />
                  <View style={styles.entryInfo}>
                    <Text style={[styles.entryName, { color: colors.textPrimary }]}>{f.name}</Text>
                    <View style={styles.statusRow}>
                      <View style={[styles.statusDot, { backgroundColor: statusColor }]} />
                      <Text style={[styles.statusText, { color: statusColor }]}>{statusLabel}</Text>
                    </View>
                  </View>
                  {!isMe && (
                    <Pressable
                      style={[
                        styles.inviteBtn,
                        !canInvite && { backgroundColor: colors.inputBg },
                        inviteState === 'sent' && { backgroundColor: Colors.green + '30' },
                      ]}
                      onPress={() => handleInvite(f)}
                      disabled={!canInvite || inviteState === 'sent'}
                    >
                      <Text style={[
                        styles.inviteBtnText,
                        (!canInvite || inviteState === 'sent') && { color: colors.textMuted },
                      ]}>
                        {inviteState === 'sent' ? 'Sent!' : !f.isOnline ? 'Offline' : f.isPlaying ? 'In Match' : 'Invite'}
                      </Text>
                    </Pressable>
                  )}
                </Pressable>
              );
            })
          )
        )}
        <View style={{ height: 20 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  blob: { position: 'absolute', borderRadius: 999 },

  header: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 20, marginBottom: 14 },
  title: { fontSize: 28, fontFamily: 'Inter_700Bold' },
  subtitle: { fontSize: 13, fontFamily: 'Inter_400Regular', marginTop: 2 },
  trophyCircle: {
    width: 50, height: 50, borderRadius: 25,
    backgroundColor: '#FFD70020', borderWidth: 1, borderColor: '#FFD70035',
    alignItems: 'center', justifyContent: 'center',
  },

  myCard: {
    flexDirection: 'row', alignItems: 'center', gap: 12,
    marginHorizontal: 18, marginBottom: 14,
    borderRadius: 18, padding: 14,
    borderWidth: 1,
  },
  myRankBadge: { width: 40, height: 40, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
  myRankText: { fontSize: 14, fontFamily: 'Inter_700Bold' },
  myAvatar: { width: 40, height: 40 },
  myInfo: { flex: 1 },
  myName: { fontSize: 14, fontFamily: 'Inter_700Bold' },
  youTag: { fontSize: 12, fontFamily: 'Inter_400Regular' },
  mySub: { fontSize: 12, fontFamily: 'Inter_400Regular', marginTop: 2 },
  myStreak: { flexDirection: 'row', alignItems: 'center', gap: 3 },
  myStreakNum: { fontSize: 15, fontFamily: 'Inter_700Bold', color: Colors.coral },

  tabs: {
    flexDirection: 'row', marginHorizontal: 18, marginBottom: 12,
    borderRadius: 14, padding: 4,
  },
  tabItem: { flex: 1, paddingVertical: 10, alignItems: 'center', borderRadius: 11 },
  tabActive: {},
  tabLabel: { fontSize: 14, fontFamily: 'Inter_500Medium' },
  tabLabelActive: { fontFamily: 'Inter_700Bold' },

  list: { paddingHorizontal: 18, gap: 8 },
  centered: { paddingTop: 60, alignItems: 'center' },
  row: {
    flexDirection: 'row', alignItems: 'center', gap: 12,
    borderRadius: 16, padding: 14,
    borderWidth: 1,
  },
  rankCol: { width: 28, alignItems: 'center' },
  rankNum: { fontSize: 15, fontFamily: 'Inter_700Bold' },
  entryInfo: { flex: 1 },
  entryName: { fontSize: 14, fontFamily: 'Inter_700Bold' },
  entrySub: { fontSize: 12, fontFamily: 'Inter_400Regular', marginTop: 2 },
  streakBadge: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  streakNum: { fontSize: 14, fontFamily: 'Inter_700Bold', color: Colors.coral },

  statusRow: { flexDirection: 'row', alignItems: 'center', gap: 5, marginTop: 3 },
  statusDot: { width: 7, height: 7, borderRadius: 4 },
  statusText: { fontSize: 12, fontFamily: 'Inter_500Medium' },

  inviteBtn: {
    paddingHorizontal: 14, paddingVertical: 8, borderRadius: 10,
    backgroundColor: Colors.purple, minWidth: 68, alignItems: 'center',
  },
  inviteBtnText: {
    fontSize: 13, fontFamily: 'Inter_700Bold', color: '#FFFFFF',
  },

  empty: { alignItems: 'center', gap: 12, paddingTop: 60 },
  emptyEmoji: { fontSize: 48 },
  emptyTitle: { fontSize: 18, fontFamily: 'Inter_700Bold' },
  emptyDesc: { fontSize: 14, fontFamily: 'Inter_400Regular', textAlign: 'center', lineHeight: 20, maxWidth: 260 },
});
