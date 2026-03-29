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
import { useAuthStore } from '@/src/stores/authStore';
import { useTheme } from '@/src/context/ThemeContext';
import { NinoButton } from '@/src/components/NinoButton';
import { apiFetch } from '@/src/services/apiClient';

type ResultFilter = 'all' | 'win' | 'loss' | 'draw';

interface MatchRecord {
  id: string;
  result: 'win' | 'loss' | 'draw';
  opponentName: string;
  opponentCharacterId: string | null;
  mode: string;
  duration: string | null;
  date: string;
  tokensAwarded: boolean;
}

const FILTERS: { key: ResultFilter; label: string }[] = [
  { key: 'all', label: 'All' },
  { key: 'win', label: 'Wins' },
  { key: 'loss', label: 'Losses' },
  { key: 'draw', label: 'Draws' },
];

const MODE_LABEL: Record<string, string> = {
  bot: 'vs Bot',
  quick: 'Quick Match',
  friend: 'Friend Room',
  online: 'Online',
};

const MODE_ICON: Record<string, React.ComponentProps<typeof Ionicons>['name']> = {
  bot: 'hardware-chip-outline',
  quick: 'flash-outline',
  friend: 'people-outline',
  online: 'globe-outline',
};

const CHAR_COLORS: Record<string, string> = {
  nino: Colors.green,
  blue_detective: Colors.blue,
  pink: Colors.pink,
  yellow: Colors.yellow,
  purple: Colors.purple,
  coral: Colors.coral,
  bot: Colors.purple,
};

function charColor(characterId: string | null): string {
  if (!characterId) return Colors.purple;
  return CHAR_COLORS[characterId] ?? Colors.purple;
}

function relativeDate(isoString: string): string {
  const date = new Date(isoString);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
  if (diffDays === 0) return 'Today';
  if (diffDays === 1) return 'Yesterday';
  if (diffDays < 7) return `${diffDays}d ago`;
  if (diffDays < 30) return `${Math.floor(diffDays / 7)}w ago`;
  return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

function OpponentAvatar({ name, characterId, colors }: {
  name: string;
  characterId: string | null;
  colors: ReturnType<typeof useTheme>['colors'];
}) {
  const color = charColor(characterId);
  const initial = name.trim().charAt(0).toUpperCase() || '?';
  return (
    <View style={[styles.avatar, { borderColor: `${color}55`, backgroundColor: `${color}18` }]}>
      <Text style={[styles.avatarInitial, { color }]}>{initial}</Text>
    </View>
  );
}

function ResultBadge({ result }: { result: 'win' | 'loss' | 'draw' }) {
  const color = result === 'win' ? Colors.green : result === 'loss' ? Colors.coral : Colors.yellow;
  const label = result === 'win' ? 'WIN' : result === 'loss' ? 'LOSS' : 'DRAW';
  return (
    <View style={[styles.resultBadge, { backgroundColor: `${color}22` }]}>
      <Text style={[styles.resultBadgeText, { color }]}>{label}</Text>
    </View>
  );
}

function MatchCard({ match, colors }: { match: MatchRecord; colors: ReturnType<typeof useTheme>['colors'] }) {
  const accentColor = match.result === 'win' ? Colors.green
    : match.result === 'loss' ? Colors.coral
    : Colors.yellow;

  const modeIcon = MODE_ICON[match.mode] ?? 'game-controller-outline';
  const modeLabel = MODE_LABEL[match.mode] ?? match.mode;

  return (
    <View style={[styles.matchCard, { backgroundColor: colors.subtleBg, borderColor: colors.border }]}>
      <View style={[styles.matchAccent, { backgroundColor: accentColor }]} />
      <View style={styles.matchContent}>
        <OpponentAvatar name={match.opponentName} characterId={match.opponentCharacterId} colors={colors} />
        <View style={styles.matchBody}>
          <View style={styles.matchTopRow}>
            <Text style={[styles.opponentName, { color: colors.textPrimary }]} numberOfLines={1}>{match.opponentName}</Text>
            <ResultBadge result={match.result} />
          </View>
          <View style={styles.matchBottomRow}>
            <View style={styles.modeTag}>
              <Ionicons name={modeIcon} size={11} color={colors.textMuted} />
              <Text style={[styles.modeText, { color: colors.textMuted }]}>{modeLabel}</Text>
            </View>
            {match.duration && (
              <>
                <Text style={[styles.sep, { color: colors.textFaint }]}>·</Text>
                <Ionicons name="time-outline" size={11} color={colors.textFaint} />
                <Text style={[styles.modeText, { color: colors.textMuted }]}>{match.duration}</Text>
              </>
            )}
            {match.tokensAwarded && (
              <>
                <Text style={[styles.sep, { color: colors.textFaint }]}>·</Text>
                <Text style={styles.tokenTag}>+🪙</Text>
              </>
            )}
          </View>
        </View>
        <Text style={[styles.dateText, { color: colors.textFaint }]}>{relativeDate(match.date)}</Text>
      </View>
    </View>
  );
}

function SkeletonCard({ colors }: { colors: ReturnType<typeof useTheme>['colors'] }) {
  return (
    <View style={[styles.matchCard, { opacity: 0.5, backgroundColor: colors.subtleBg, borderColor: colors.border }]}>
      <View style={[styles.matchAccent, { backgroundColor: colors.border }]} />
      <View style={styles.matchContent}>
        <View style={[styles.avatar, { borderColor: colors.border, backgroundColor: colors.subtleBg }]} />
        <View style={styles.matchBody}>
          <View style={styles.matchTopRow}>
            <View style={[styles.skeletonBar, { width: 110, backgroundColor: colors.inputBg }]} />
            <View style={[styles.skeletonBar, { width: 40, borderRadius: 6, backgroundColor: colors.inputBg }]} />
          </View>
          <View style={[styles.skeletonBar, { width: 90, height: 10, marginTop: 2, backgroundColor: colors.inputBg }]} />
        </View>
        <View style={[styles.skeletonBar, { width: 32, height: 10, backgroundColor: colors.inputBg }]} />
      </View>
    </View>
  );
}

export default function MatchHistoryScreen() {
  const insets = useSafeAreaInsets();
  const profile = useAuthStore(s => s.profile);
  const stats = profile?.stats;
  const { colors } = useTheme();

  const [filter, setFilter] = useState<ResultFilter>('all');
  const [matches, setMatches] = useState<MatchRecord[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchHistory = useCallback(async (resultParam: ResultFilter = 'all', isRefresh = false) => {
    if (!profile?.id) {
      setLoading(false);
      return;
    }
    if (isRefresh) setRefreshing(true);
    else setLoading(true);
    setError(null);

    try {
      const params = new URLSearchParams({ limit: '30' });
      if (resultParam !== 'all') params.append('result', resultParam);
      const res = await apiFetch(`/matches/history?${params.toString()}`);
      if (!res.ok) throw new Error('Failed to load');
      const data = await res.json() as { matches: MatchRecord[] };
      setMatches(data.matches ?? []);
    } catch {
      setError('Could not load match history. Pull down to retry.');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [profile?.id]);

  useEffect(() => { fetchHistory(filter); }, [fetchHistory, filter]);

  const winRate = stats && stats.totalMatches > 0
    ? Math.round((stats.totalWins / stats.totalMatches) * 100)
    : 0;

  return (
    <View style={[styles.container, { paddingTop: insets.top, backgroundColor: colors.background }]}>
      <View style={styles.header}>
        <Pressable onPress={() => router.back()} style={[styles.backBtn, { backgroundColor: colors.inputBg }]}>
          <Ionicons name="chevron-back" size={26} color={colors.textPrimary} />
        </Pressable>
        <Text style={[styles.title, { color: colors.textPrimary }]}>Match History</Text>
        <View style={{ width: 40 }} />
      </View>

      <View style={styles.summaryRow}>
        <View style={[styles.summaryCard, { borderColor: 'rgba(92,175,122,0.3)', backgroundColor: 'rgba(92,175,122,0.08)' }]}>
          <Text style={[styles.summaryNum, { color: Colors.green }]}>{stats?.totalWins ?? 0}</Text>
          <Text style={[styles.summaryLabel, { color: colors.textMuted }]}>Wins</Text>
        </View>
        <View style={[styles.summaryCard, { borderColor: 'rgba(240,128,96,0.3)', backgroundColor: 'rgba(240,128,96,0.08)' }]}>
          <Text style={[styles.summaryNum, { color: Colors.coral }]}>{stats?.totalLosses ?? 0}</Text>
          <Text style={[styles.summaryLabel, { color: colors.textMuted }]}>Losses</Text>
        </View>
        <View style={[styles.summaryCard, { borderColor: 'rgba(242,184,75,0.3)', backgroundColor: 'rgba(242,184,75,0.08)' }]}>
          <Text style={[styles.summaryNum, { color: Colors.yellow }]}>{stats?.totalDraws ?? 0}</Text>
          <Text style={[styles.summaryLabel, { color: colors.textMuted }]}>Draws</Text>
        </View>
        <View style={[styles.summaryCard, { borderColor: 'rgba(155,127,212,0.3)', backgroundColor: 'rgba(155,127,212,0.08)' }]}>
          <Text style={[styles.summaryNum, { color: Colors.purple }]}>{winRate}%</Text>
          <Text style={[styles.summaryLabel, { color: colors.textMuted }]}>Win Rate</Text>
        </View>
      </View>

      <ScrollView
        style={styles.filterScroll}
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.filterRow}
      >
        {FILTERS.map((f) => (
          <Pressable
            key={f.key}
            style={[
              styles.filterTab,
              { backgroundColor: colors.inputBg, borderColor: colors.border },
              filter === f.key && { backgroundColor: Colors.purple, borderColor: Colors.purple },
            ]}
            onPress={() => setFilter(f.key)}
          >
            <Text style={[
              styles.filterLabel,
              { color: colors.textMuted },
              filter === f.key && { color: Colors.white },
            ]}>
              {f.label}
            </Text>
          </Pressable>
        ))}
      </ScrollView>

      {loading ? (
        <ScrollView contentContainerStyle={styles.list} showsVerticalScrollIndicator={false}>
          {[1, 2, 3, 4, 5].map((i) => <SkeletonCard key={i} colors={colors} />)}
        </ScrollView>
      ) : error ? (
        <ScrollView
          contentContainerStyle={styles.centerState}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={() => fetchHistory(filter, true)}
              tintColor={Colors.purple}
              colors={[Colors.purple]}
            />
          }
        >
          <Ionicons name="cloud-offline-outline" size={56} color={colors.textFaint} />
          <Text style={[styles.emptyTitle, { color: colors.textPrimary }]}>Something went wrong</Text>
          <Text style={[styles.emptyDesc, { color: colors.textSecondary }]}>{error}</Text>
          <NinoButton label="Retry" onPress={() => fetchHistory(filter)} color={Colors.purple} />
        </ScrollView>
      ) : matches.length === 0 ? (
        <ScrollView
          contentContainerStyle={styles.centerState}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={() => fetchHistory(filter, true)}
              tintColor={Colors.purple}
              colors={[Colors.purple]}
            />
          }
        >
          <Ionicons name="game-controller-outline" size={60} color={colors.textFaint} />
          <Text style={[styles.emptyTitle, { color: colors.textPrimary }]}>
            {filter === 'all' ? 'No matches yet' : `No ${filter}s yet`}
          </Text>
          <Text style={[styles.emptyDesc, { color: colors.textSecondary }]}>
            {filter === 'all'
              ? 'Play your first match to see your history here!'
              : `You don't have any ${filter}s recorded.`}
          </Text>
          {filter === 'all' && (
            <NinoButton label="Play Now" onPress={() => router.replace('/home')} color={Colors.green} />
          )}
        </ScrollView>
      ) : (
        <ScrollView
          showsVerticalScrollIndicator={false}
          contentContainerStyle={styles.list}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={() => fetchHistory(filter, true)}
              tintColor={Colors.purple}
              colors={[Colors.purple]}
            />
          }
        >
          {matches.map((match) => (
            <MatchCard key={match.id} match={match} colors={colors} />
          ))}
          <Text style={[styles.listFooter, { color: colors.textFaint }]}>
            Showing {matches.length} match{matches.length !== 1 ? 'es' : ''}
          </Text>
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
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingVertical: 10,
    marginBottom: 4,
  },
  backBtn: {
    width: 40, height: 40,
    alignItems: 'center', justifyContent: 'center',
    borderRadius: 12,
  },
  title: { fontSize: 19, fontFamily: 'Inter_700Bold' },

  summaryRow: {
    flexDirection: 'row',
    gap: 8,
    paddingHorizontal: 16,
    marginBottom: 16,
  },
  summaryCard: {
    flex: 1,
    alignItems: 'center',
    paddingVertical: 12,
    borderRadius: 14,
    borderWidth: 1,
    gap: 2,
  },
  summaryNum: { fontSize: 22, fontFamily: 'Inter_700Bold' },
  summaryLabel: {
    fontSize: 10,
    fontFamily: 'Inter_500Medium',
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },

  filterScroll: { flexGrow: 0, marginBottom: 12 },
  filterRow: { paddingHorizontal: 16, gap: 8, flexDirection: 'row' },
  filterTab: {
    paddingHorizontal: 16, paddingVertical: 8,
    borderRadius: 20,
    borderWidth: 1,
  },
  filterLabel: { fontSize: 13, fontFamily: 'Inter_600SemiBold' },

  list: { paddingHorizontal: 16, paddingBottom: 32, gap: 8 },

  matchCard: {
    flexDirection: 'row',
    borderRadius: 16,
    borderWidth: 1,
    overflow: 'hidden',
  },
  matchAccent: { width: 4, flexShrink: 0 },
  matchContent: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    padding: 12,
  },

  avatar: {
    width: 44, height: 44,
    borderRadius: 14,
    borderWidth: 1.5,
    alignItems: 'center',
    justifyContent: 'center',
    flexShrink: 0,
  },
  avatarInitial: { fontSize: 18, fontFamily: 'Inter_700Bold', lineHeight: 22 },

  matchBody: { flex: 1, gap: 4 },
  matchTopRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  opponentName: { flex: 1, fontSize: 15, fontFamily: 'Inter_600SemiBold' },

  resultBadge: {
    paddingHorizontal: 8, paddingVertical: 3,
    borderRadius: 8, flexShrink: 0,
  },
  resultBadgeText: { fontSize: 11, fontFamily: 'Inter_700Bold', letterSpacing: 0.8 },

  matchBottomRow: { flexDirection: 'row', alignItems: 'center', gap: 4, flexWrap: 'wrap' },
  modeTag: { flexDirection: 'row', alignItems: 'center', gap: 3 },
  modeText: { fontSize: 11, fontFamily: 'Inter_400Regular' },
  sep: { fontSize: 11 },
  tokenTag: { fontSize: 11 },

  dateText: { fontSize: 11, fontFamily: 'Inter_400Regular', flexShrink: 0 },

  skeletonBar: {
    height: 14,
    width: 120,
    borderRadius: 7,
  },

  centerState: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 12, padding: 40 },
  emptyTitle: { fontSize: 18, fontFamily: 'Inter_700Bold', textAlign: 'center' },
  emptyDesc: {
    fontSize: 14,
    fontFamily: 'Inter_400Regular',
    textAlign: 'center',
    lineHeight: 21,
  },

  listFooter: {
    textAlign: 'center',
    fontSize: 12,
    fontFamily: 'Inter_400Regular',
    marginTop: 8,
  },
});
