import { Ionicons } from '@expo/vector-icons';
import { router, useLocalSearchParams } from 'expo-router';
import React, { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useAuth } from '@/src/context/AuthContext';
import { useGame } from '@/src/context/GameContext';
import { useTheme } from '@/src/context/ThemeContext';
import {
  fetchChallengeDetail,
  joinChallenge,
  startChallengeMatch,
  type ChallengeDetail,
  type LeaderboardEntry,
} from '@/src/services/challengeService';

function timeLabel(challenge: ChallengeDetail): string {
  const now = Date.now();
  const end = new Date(challenge.endAt).getTime();
  const start = new Date(challenge.startAt).getTime();

  if (challenge.status === 'active') {
    const diff = end - now;
    if (diff <= 0) return 'Ending soon';
    const hours = Math.floor(diff / 3600000);
    const mins = Math.floor((diff % 3600000) / 60000);
    if (hours > 24) return `${Math.floor(hours / 24)}d ${hours % 24}h remaining`;
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

function RankMedal({ rank, colors }: { rank: number; colors: ReturnType<typeof useTheme>['colors'] }) {
  if (rank === 1) return <Text style={styles.medal}>🥇</Text>;
  if (rank === 2) return <Text style={styles.medal}>🥈</Text>;
  if (rank === 3) return <Text style={styles.medal}>🥉</Text>;
  return <Text style={[styles.rankNum, { color: colors.textMuted }]}>#{rank}</Text>;
}

function LeaderboardRow({ entry, isMe, colors }: {
  entry: LeaderboardEntry;
  isMe: boolean;
  colors: ReturnType<typeof useTheme>['colors'];
}) {
  return (
    <View style={[styles.lbRow, { borderBottomColor: colors.border }, isMe && { backgroundColor: Colors.purple + '12' }]}>
      <View style={styles.lbRank}>
        <RankMedal rank={entry.rank} colors={colors} />
      </View>
      <Text style={[styles.lbName, { color: isMe ? Colors.purple : colors.textPrimary }]} numberOfLines={1}>
        {entry.name}{isMe ? ' (you)' : ''}
      </Text>
      <View style={styles.lbRight}>
        <Text style={[styles.lbWins, { color: Colors.green }]}>{entry.wins}W</Text>
        <Text style={[styles.lbMatches, { color: colors.textFaint }]}>{entry.losses}L</Text>
      </View>
    </View>
  );
}

function JoinModal({
  visible,
  challenge,
  tokenBalance,
  onConfirm,
  onClose,
  joining,
  colors,
}: {
  visible: boolean;
  challenge: ChallengeDetail;
  tokenBalance: number;
  onConfirm: () => void;
  onClose: () => void;
  joining: boolean;
  colors: ReturnType<typeof useTheme>['colors'];
}) {
  const canAfford = challenge.entryFee === 0 || tokenBalance >= challenge.entryFee;
  const balanceAfter = tokenBalance - challenge.entryFee;

  return (
    <Modal transparent visible={visible} animationType="fade" onRequestClose={onClose}>
      <View style={styles.modalOverlay}>
        <View style={[styles.modalCard, { backgroundColor: colors.card, borderColor: Colors.purple + '40' }]}>
          <View style={[styles.modalIcon, { backgroundColor: Colors.purple + '18' }]}>
            <Ionicons name="trophy" size={28} color={Colors.purple} />
          </View>
          <Text style={[styles.modalTitle, { color: colors.textPrimary }]}>Join Challenge</Text>
          <Text style={[styles.modalName, { color: Colors.purple }]}>{challenge.title}</Text>

          {challenge.entryFee > 0 ? (
            <View style={[styles.feeBox, { backgroundColor: colors.inputBg }]}>
              <View style={styles.feeRow}>
                <Text style={[styles.feeLabel, { color: colors.textMuted }]}>Entry fee</Text>
                <Text style={[styles.feeAmount, { color: !canAfford ? Colors.coral : Colors.yellow }]}>
                  🪙 {challenge.entryFee} tokens
                </Text>
              </View>
              <View style={styles.feeRow}>
                <Text style={[styles.feeLabel, { color: colors.textMuted }]}>Your balance</Text>
                <Text style={[styles.feeBalance, { color: !canAfford ? Colors.coral : colors.textPrimary }]}>
                  🪙 {tokenBalance} tokens
                </Text>
              </View>
              {canAfford && (
                <View style={styles.feeRow}>
                  <Text style={[styles.feeLabel, { color: colors.textMuted }]}>Balance after</Text>
                  <Text style={[styles.feeBalance, { color: colors.textPrimary }]}>🪙 {balanceAfter} tokens</Text>
                </View>
              )}
              {!canAfford && (
                <Text style={[styles.modalDesc, { color: Colors.coral, marginTop: 4 }]}>
                  Not enough tokens to join this challenge.
                </Text>
              )}
            </View>
          ) : (
            <View style={[styles.feeBox, { backgroundColor: colors.inputBg }]}>
              <Text style={[styles.feeLabel, { color: colors.textMuted }]}>Entry</Text>
              <Text style={[styles.feeAmount, { color: Colors.green }]}>Free!</Text>
            </View>
          )}

          {challenge.prizePool > 0 && (
            <View style={[styles.prizeBox, { backgroundColor: Colors.yellow + '15' }]}>
              <Ionicons name="gift" size={14} color={Colors.yellow} />
              <Text style={[styles.prizeBoxText, { color: Colors.yellow }]}>{challenge.prizePool} token prize pool</Text>
            </View>
          )}

          <Text style={[styles.modalDesc, { color: colors.textSecondary }]}>
            {challenge.entryFee > 0 && canAfford
              ? `${challenge.entryFee} tokens will be deducted from your balance.`
              : challenge.entryFee === 0 ? 'This challenge is free to enter.' : ''}
            {canAfford && ' Once joined, play matches against bots to climb the leaderboard!'}
          </Text>

          <View style={styles.modalBtns}>
            <Pressable
              onPress={onClose}
              style={[styles.modalCancel, { backgroundColor: colors.inputBg }]}
              disabled={joining}
            >
              <Text style={[styles.modalCancelText, { color: colors.textSecondary }]}>Cancel</Text>
            </Pressable>
            <Pressable
              onPress={onConfirm}
              style={[styles.modalConfirm, { backgroundColor: Colors.purple }, (joining || !canAfford) && { opacity: 0.5 }]}
              disabled={joining || !canAfford}
            >
              {joining ? (
                <ActivityIndicator size="small" color={Colors.white} />
              ) : (
                <Text style={styles.modalConfirmText}>
                  {canAfford ? 'Join Now' : 'Not Enough Tokens'}
                </Text>
              )}
            </Pressable>
          </View>
        </View>
      </View>
    </Modal>
  );
}

export default function ChallengeDetailScreen() {
  const insets = useSafeAreaInsets();
  const { id } = useLocalSearchParams<{ id: string }>();
  const { profile, setTokenBalance } = useAuth();
  const { startChallengeGame } = useGame();
  const { colors } = useTheme();
  const [challenge, setChallenge] = useState<ChallengeDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [showJoinModal, setShowJoinModal] = useState(false);
  const [joining, setJoining] = useState(false);

  const load = useCallback(async () => {
    if (!id || !profile) return;
    const data = await fetchChallengeDetail(id, profile.id);
    setChallenge(data);
    setLoading(false);
  }, [id, profile?.id]);

  useEffect(() => { load(); }, [load]);

  const handleJoin = async () => {
    if (!challenge || !profile) return;
    setJoining(true);
    const result = await joinChallenge(challenge.id, profile.id);
    setJoining(false);
    setShowJoinModal(false);

    if (!result.ok) {
      Alert.alert('Could not join', result.error ?? 'Please try again.');
      return;
    }

    if (result.tokenBalance !== undefined) {
      setTokenBalance(result.tokenBalance);
    }
    await load();
  };

  const handlePlay = async () => {
    if (!challenge || !profile) return;
    const matchToken = await startChallengeMatch(challenge.id, profile.id);
    if (!matchToken) {
      Alert.alert('Could not start match', 'The server could not authorize this game. The challenge may have ended. Please try again.');
      return;
    }
    startChallengeGame(profile.selectedCharacterId, profile.name, challenge.id, 'medium', matchToken);
    router.push('/match');
  };

  if (loading) {
    return (
      <View style={[styles.container, { backgroundColor: colors.background, paddingTop: insets.top, alignItems: 'center', justifyContent: 'center' }]}>
        <ActivityIndicator color={Colors.purple} size="large" />
      </View>
    );
  }

  if (!challenge) {
    return (
      <View style={[styles.container, { backgroundColor: colors.background, paddingTop: insets.top }]}>
        <View style={styles.header}>
          <Pressable onPress={() => router.back()} style={[styles.backBtn, { backgroundColor: colors.inputBg }]}>
            <Ionicons name="arrow-back" size={22} color={colors.textPrimary} />
          </Pressable>
          <Text style={[styles.headerTitle, { color: colors.textPrimary }]}>Challenge</Text>
        </View>
        <View style={styles.center}>
          <Text style={[styles.errorText, { color: colors.textMuted }]}>Challenge not found</Text>
        </View>
      </View>
    );
  }

  const isActive = challenge.status === 'active';
  const isUpcoming = challenge.status === 'upcoming';
  const canJoin = isActive && !challenge.isJoined;
  const canPlay = isActive && challenge.isJoined;
  const time = timeLabel(challenge);

  const statusColor = isActive ? Colors.green
    : isUpcoming ? Colors.yellow
    : challenge.status === 'completed' ? Colors.purple
    : Colors.coral;

  return (
    <View style={[styles.container, { backgroundColor: colors.background, paddingTop: insets.top }]}>
      <View style={styles.header}>
        <Pressable onPress={() => router.back()} style={[styles.backBtn, { backgroundColor: colors.inputBg }]}>
          <Ionicons name="arrow-back" size={22} color={colors.textPrimary} />
        </Pressable>
        <Text style={[styles.headerTitle, { color: colors.textPrimary }]}>Challenge</Text>
        <View style={[styles.statusPill, { backgroundColor: statusColor + '22', borderColor: statusColor + '50' }]}>
          <View style={[styles.statusDot, { backgroundColor: statusColor }]} />
          <Text style={[styles.statusText, { color: statusColor }]}>
            {challenge.status === 'active' ? 'LIVE' : challenge.status.toUpperCase()}
          </Text>
        </View>
      </View>

      <ScrollView
        contentContainerStyle={[styles.scroll, { paddingBottom: insets.bottom + 30 }]}
        showsVerticalScrollIndicator={false}
      >
        <View style={[styles.titleCard, { backgroundColor: colors.card, borderColor: Colors.purple + '25' }]}>
          <View style={[styles.titleIconWrap, { backgroundColor: Colors.purple + '18' }]}>
            <Ionicons name="trophy" size={32} color={Colors.purple} />
          </View>
          <Text style={[styles.title, { color: colors.textPrimary }]}>{challenge.title}</Text>
          {challenge.description ? (
            <Text style={[styles.description, { color: colors.textSecondary }]}>{challenge.description}</Text>
          ) : null}
          {time ? <Text style={[styles.timeText, { color: statusColor }]}>{time}</Text> : null}
        </View>

        <View style={styles.statsRow}>
          <View style={[styles.statBox, { backgroundColor: colors.card, borderColor: colors.border }]}>
            <Text style={[styles.statNum, { color: colors.textPrimary }]}>{challenge.participantCount}</Text>
            <Text style={[styles.statLabel, { color: colors.textFaint }]}>Players</Text>
          </View>
          {challenge.entryFee > 0 ? (
            <View style={[styles.statBox, { backgroundColor: colors.card, borderColor: colors.border }]}>
              <Text style={[styles.statNum, { color: Colors.yellow }]}>🪙 {challenge.entryFee}</Text>
              <Text style={[styles.statLabel, { color: colors.textFaint }]}>Entry Fee</Text>
            </View>
          ) : (
            <View style={[styles.statBox, { backgroundColor: colors.card, borderColor: colors.border }]}>
              <Text style={[styles.statNum, { color: Colors.green }]}>FREE</Text>
              <Text style={[styles.statLabel, { color: colors.textFaint }]}>Entry</Text>
            </View>
          )}
          <View style={[styles.statBox, { backgroundColor: colors.card, borderColor: colors.border }]}>
            <Text style={[styles.statNum, { color: Colors.yellow }]}>
              {challenge.prizePool > 0 ? `🏆 ${challenge.prizePool}` : '—'}
            </Text>
            <Text style={[styles.statLabel, { color: colors.textFaint }]}>Prize Pool</Text>
          </View>
        </View>

        {challenge.isJoined && challenge.myStats && (
          <View style={[styles.myStatsCard, { backgroundColor: colors.card, borderColor: Colors.purple + '30' }]}>
            <Text style={[styles.sectionTitle, { color: colors.textPrimary }]}>Your Standing</Text>
            <View style={styles.myStatsRow}>
              <View style={styles.myStatItem}>
                <Text style={[styles.myStatNum, { color: colors.textPrimary }]}>{challenge.myStats.rank}</Text>
                <Text style={[styles.myStatLabel, { color: colors.textFaint }]}>Rank</Text>
              </View>
              <View style={styles.myStatItem}>
                <Text style={[styles.myStatNum, { color: Colors.green }]}>{challenge.myStats.wins}</Text>
                <Text style={[styles.myStatLabel, { color: colors.textFaint }]}>Wins</Text>
              </View>
              <View style={styles.myStatItem}>
                <Text style={[styles.myStatNum, { color: colors.textPrimary }]}>{challenge.myStats.matches}</Text>
                <Text style={[styles.myStatLabel, { color: colors.textFaint }]}>Matches</Text>
              </View>
            </View>
          </View>
        )}

        {challenge.isJoined && !challenge.myStats && (
          <View style={[styles.joinedBanner, { backgroundColor: Colors.green + '15', borderColor: Colors.green + '30' }]}>
            <Ionicons name="checkmark-circle" size={16} color={Colors.green} />
            <Text style={[styles.joinedText, { color: Colors.green }]}>You've joined! Play a match to appear on the leaderboard.</Text>
          </View>
        )}

        {canPlay && (
          <Pressable onPress={handlePlay} style={styles.playBtn}>
            <Ionicons name="flash" size={18} color={Colors.textDark} />
            <Text style={styles.playBtnText}>Play Challenge Match</Text>
          </Pressable>
        )}

        {canJoin && (
          <Pressable onPress={() => setShowJoinModal(true)} style={styles.joinBtn}>
            <Ionicons name="trophy-outline" size={18} color={Colors.white} />
            <Text style={styles.joinBtnText}>
              {challenge.entryFee > 0 ? `Join for 🪙 ${challenge.entryFee}` : 'Join Free'}
            </Text>
          </Pressable>
        )}

        {challenge.status === 'completed' && (
          <>
            <View style={[styles.completedBanner, { backgroundColor: Colors.purple + '15', borderColor: Colors.purple + '30' }]}>
              <Ionicons name="ribbon" size={16} color={Colors.purple} />
              <Text style={[styles.completedText, { color: Colors.purple }]}>Challenge ended — final results below</Text>
            </View>
            {challenge.isJoined && challenge.rewardReceived && (
              <View style={[styles.rewardBanner, { backgroundColor: Colors.yellow + '15', borderColor: Colors.yellow + '35' }]}>
                <Ionicons name="gift" size={16} color={Colors.yellow} />
                <Text style={[styles.rewardText, { color: colors.textSecondary }]}>
                  You finished Rank #{challenge.rewardReceived.rank} and earned{' '}
                  <Text style={styles.rewardAmount}>🪙 {challenge.rewardReceived.amount} tokens</Text>!
                </Text>
              </View>
            )}
            {challenge.isJoined && !challenge.rewardReceived && challenge.prizePool > 0 && (
              <View style={[styles.noRewardBanner, { backgroundColor: colors.subtleBg, borderColor: colors.border }]}>
                <Ionicons name="sad-outline" size={16} color={colors.textFaint} />
                <Text style={[styles.noRewardText, { color: colors.textMuted }]}>You didn't finish in a reward position this time.</Text>
              </View>
            )}
          </>
        )}

        <Text style={[styles.sectionTitle, { color: colors.textPrimary }]}>Leaderboard</Text>
        {challenge.leaderboard.length === 0 ? (
          <View style={[styles.emptyLb, { backgroundColor: colors.card, borderColor: colors.border }]}>
            <Text style={[styles.emptyLbText, { color: colors.textMuted }]}>No matches played yet</Text>
          </View>
        ) : (
          <View style={[styles.lbContainer, { backgroundColor: colors.card, borderColor: colors.border }]}>
            {challenge.leaderboard.map((entry) => (
              <LeaderboardRow
                key={entry.playerId}
                entry={entry}
                isMe={entry.playerId === profile?.id}
                colors={colors}
              />
            ))}
          </View>
        )}
      </ScrollView>

      {challenge && (
        <JoinModal
          visible={showJoinModal}
          challenge={challenge}
          tokenBalance={profile?.tokenBalance ?? 0}
          onConfirm={handleJoin}
          onClose={() => setShowJoinModal(false)}
          joining={joining}
          colors={colors}
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
    paddingHorizontal: 18,
    paddingVertical: 14,
    gap: 12,
  },
  backBtn: {
    width: 40, height: 40, borderRadius: 12,
    alignItems: 'center', justifyContent: 'center',
  },
  headerTitle: { fontSize: 20, fontFamily: 'Inter_700Bold', flex: 1 },
  statusPill: {
    flexDirection: 'row', alignItems: 'center', gap: 5,
    paddingHorizontal: 10, paddingVertical: 5,
    borderRadius: 20, borderWidth: 1,
  },
  statusDot: { width: 6, height: 6, borderRadius: 3 },
  statusText: { fontSize: 10, fontFamily: 'Inter_700Bold', letterSpacing: 0.8 },

  scroll: { paddingHorizontal: 18, gap: 14, paddingTop: 4 },

  titleCard: {
    borderRadius: 20, padding: 20,
    alignItems: 'center', gap: 10,
    borderWidth: 1,
  },
  titleIconWrap: {
    width: 64, height: 64, borderRadius: 20,
    alignItems: 'center', justifyContent: 'center',
    marginBottom: 4,
  },
  title: { fontSize: 24, fontFamily: 'Inter_700Bold', textAlign: 'center' },
  description: {
    fontSize: 14, fontFamily: 'Inter_400Regular',
    textAlign: 'center', lineHeight: 20,
  },
  timeText: { fontSize: 13, fontFamily: 'Inter_600SemiBold' },

  statsRow: { flexDirection: 'row', gap: 10 },
  statBox: {
    flex: 1, borderRadius: 16, padding: 14,
    alignItems: 'center', gap: 4,
    borderWidth: 1,
  },
  statNum: { fontSize: 18, fontFamily: 'Inter_700Bold' },
  statLabel: { fontSize: 11, fontFamily: 'Inter_400Regular' },

  myStatsCard: {
    borderRadius: 16, padding: 16,
    borderWidth: 1, gap: 10,
  },
  myStatsRow: { flexDirection: 'row', justifyContent: 'space-around' },
  myStatItem: { alignItems: 'center', gap: 4 },
  myStatNum: { fontSize: 22, fontFamily: 'Inter_700Bold' },
  myStatLabel: { fontSize: 11, fontFamily: 'Inter_400Regular' },

  joinedBanner: {
    flexDirection: 'row', alignItems: 'center', gap: 8,
    borderRadius: 14, padding: 14, borderWidth: 1,
  },
  joinedText: { fontSize: 13, fontFamily: 'Inter_400Regular', flex: 1 },

  completedBanner: {
    flexDirection: 'row', alignItems: 'center', gap: 8,
    borderRadius: 14, padding: 14, borderWidth: 1,
  },
  completedText: { fontSize: 13, fontFamily: 'Inter_400Regular' },

  playBtn: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 10,
    backgroundColor: Colors.yellow, borderRadius: 16, padding: 16,
  },
  playBtnText: { fontSize: 16, fontFamily: 'Inter_700Bold', color: Colors.textDark },

  joinBtn: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 10,
    backgroundColor: Colors.purple, borderRadius: 16, padding: 16,
  },
  joinBtnText: { fontSize: 16, fontFamily: 'Inter_700Bold', color: Colors.white },

  sectionTitle: { fontSize: 16, fontFamily: 'Inter_700Bold', marginBottom: 4 },

  lbContainer: {
    borderRadius: 16, borderWidth: 1, overflow: 'hidden',
  },
  lbRow: {
    flexDirection: 'row', alignItems: 'center',
    paddingHorizontal: 16, paddingVertical: 12,
    borderBottomWidth: 1,
  },
  lbRank: { width: 36, alignItems: 'center' },
  medal: { fontSize: 18 },
  rankNum: { fontSize: 14, fontFamily: 'Inter_700Bold' },
  lbName: { flex: 1, fontSize: 14, fontFamily: 'Inter_600SemiBold', marginLeft: 4 },
  lbRight: { flexDirection: 'row', gap: 10, alignItems: 'center' },
  lbWins: { fontSize: 14, fontFamily: 'Inter_700Bold' },
  lbMatches: { fontSize: 12, fontFamily: 'Inter_400Regular' },

  rewardBanner: {
    flexDirection: 'row', alignItems: 'flex-start', gap: 8,
    borderRadius: 14, padding: 14, borderWidth: 1,
  },
  rewardText: { fontSize: 13, fontFamily: 'Inter_400Regular', flex: 1, lineHeight: 20 },
  rewardAmount: { fontFamily: 'Inter_700Bold', color: Colors.yellow },
  noRewardBanner: {
    flexDirection: 'row', alignItems: 'center', gap: 8,
    borderRadius: 14, padding: 14, borderWidth: 1,
  },
  noRewardText: { fontSize: 13, fontFamily: 'Inter_400Regular', flex: 1 },
  emptyLb: {
    borderRadius: 16, padding: 24,
    alignItems: 'center', borderWidth: 1,
  },
  emptyLbText: { fontSize: 14, fontFamily: 'Inter_400Regular' },

  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  errorText: { fontSize: 16, fontFamily: 'Inter_400Regular' },

  modalOverlay: {
    flex: 1, backgroundColor: 'rgba(0,0,0,0.7)',
    alignItems: 'center', justifyContent: 'center', padding: 24,
  },
  modalCard: {
    borderRadius: 24, padding: 24, width: '100%',
    alignItems: 'center', gap: 12,
    borderWidth: 1,
  },
  modalIcon: {
    width: 60, height: 60, borderRadius: 18,
    alignItems: 'center', justifyContent: 'center', marginBottom: 4,
  },
  modalTitle: { fontSize: 20, fontFamily: 'Inter_700Bold' },
  modalName: { fontSize: 15, fontFamily: 'Inter_600SemiBold', textAlign: 'center' },
  feeBox: {
    borderRadius: 12, padding: 14,
    gap: 6, width: '100%',
  },
  feeRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  feeLabel: { fontSize: 12, fontFamily: 'Inter_400Regular' },
  feeAmount: { fontSize: 18, fontFamily: 'Inter_700Bold' },
  feeBalance: { fontSize: 14, fontFamily: 'Inter_600SemiBold' },
  prizeBox: {
    flexDirection: 'row', alignItems: 'center', gap: 6,
    borderRadius: 10, paddingHorizontal: 12, paddingVertical: 6,
  },
  prizeBoxText: { fontSize: 13, fontFamily: 'Inter_600SemiBold' },
  modalDesc: {
    fontSize: 13, fontFamily: 'Inter_400Regular',
    textAlign: 'center', lineHeight: 19,
  },
  modalBtns: { flexDirection: 'row', gap: 10, width: '100%', marginTop: 4 },
  modalCancel: {
    flex: 1, borderRadius: 14, padding: 14, alignItems: 'center',
  },
  modalCancelText: { fontSize: 15, fontFamily: 'Inter_600SemiBold' },
  modalConfirm: {
    flex: 2, borderRadius: 14, padding: 14, alignItems: 'center',
  },
  modalConfirmText: { fontSize: 15, fontFamily: 'Inter_700Bold', color: Colors.white },
});
