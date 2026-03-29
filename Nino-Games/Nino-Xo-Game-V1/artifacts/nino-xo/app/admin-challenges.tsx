import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import React, { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useAuth } from '@/src/context/AuthContext';
import { useTheme } from '@/src/context/ThemeContext';
import {
  fetchAdminChallenges,
  createChallenge,
  updateChallenge,
  completeChallenge,
  cancelChallenge,
  type AdminChallenge,
  type Challenge,
} from '@/src/services/challengeService';

function statusColor(status: Challenge['status']): string {
  switch (status) {
    case 'active': return Colors.green;
    case 'upcoming': return Colors.yellow;
    case 'completed': return Colors.purple;
    case 'cancelled': return Colors.coral;
    default: return Colors.purple;
  }
}

function defaultDatetimeLocal(offsetHours = 0): string {
  const d = new Date(Date.now() + offsetHours * 3600000);
  return d.toISOString().slice(0, 16);
}

function parseLocalDatetime(val: string): string {
  return new Date(val).toISOString();
}

type FormMode = 'create' | 'edit';

function ChallengeForm({
  visible,
  mode,
  initial,
  onClose,
  onSaved,
  colors,
}: {
  visible: boolean;
  mode: FormMode;
  initial: Challenge | null;
  onClose: () => void;
  onSaved: () => void;
  colors: ReturnType<typeof useTheme>['colors'];
}) {
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [entryFee, setEntryFee] = useState('0');
  const [prizePool, setPrizePool] = useState('0');
  const [rank1Reward, setRank1Reward] = useState('');
  const [rank2Reward, setRank2Reward] = useState('');
  const [rank3Reward, setRank3Reward] = useState('');
  const [startsAt, setStartsAt] = useState(defaultDatetimeLocal(0));
  const [endsAt, setEndsAt] = useState(defaultDatetimeLocal(24));
  const [maxParticipants, setMaxParticipants] = useState('');
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (visible) {
      if (mode === 'edit' && initial) {
        setTitle(initial.title);
        setDescription(initial.description ?? '');
        setEntryFee(String(initial.entryFee));
        setPrizePool(String(initial.prizePool));
        setRank1Reward(initial.rank1Reward != null ? String(initial.rank1Reward) : '');
        setRank2Reward(initial.rank2Reward != null ? String(initial.rank2Reward) : '');
        setRank3Reward(initial.rank3Reward != null ? String(initial.rank3Reward) : '');
        setStartsAt(new Date(initial.startAt).toISOString().slice(0, 16));
        setEndsAt(new Date(initial.endAt).toISOString().slice(0, 16));
        setMaxParticipants(initial.maxParticipants ? String(initial.maxParticipants) : '');
      } else {
        setTitle('');
        setDescription('');
        setEntryFee('0');
        setPrizePool('0');
        setRank1Reward('');
        setRank2Reward('');
        setRank3Reward('');
        setStartsAt(defaultDatetimeLocal(0));
        setEndsAt(defaultDatetimeLocal(24));
        setMaxParticipants('');
      }
    }
  }, [visible, mode, initial]);

  const handleSave = async () => {
    if (!title.trim()) {
      Alert.alert('Required', 'Title is required');
      return;
    }
    setSaving(true);
    const params = {
      title: title.trim(),
      description: description.trim() || undefined,
      entryFee: parseInt(entryFee) || 0,
      prizePool: parseInt(prizePool) || 0,
      rank1Reward: rank1Reward ? parseInt(rank1Reward) : undefined,
      rank2Reward: rank2Reward ? parseInt(rank2Reward) : undefined,
      rank3Reward: rank3Reward ? parseInt(rank3Reward) : undefined,
      startsAt: parseLocalDatetime(startsAt),
      endsAt: parseLocalDatetime(endsAt),
      maxParticipants: maxParticipants ? parseInt(maxParticipants) : undefined,
    };

    let ok = false;
    if (mode === 'create') {
      const result = await createChallenge(params);
      ok = result !== null;
    } else if (mode === 'edit' && initial) {
      const result = await updateChallenge(initial.id, params);
      ok = result !== null;
    }
    setSaving(false);
    if (ok) {
      onSaved();
      onClose();
    } else {
      Alert.alert('Error', 'Failed to save challenge. Please try again.');
    }
  };

  const fieldStyle = [styles.input, { backgroundColor: colors.inputBg, borderColor: colors.border, color: colors.textPrimary }];
  const placeholderColor = colors.textFaint;

  return (
    <Modal transparent visible={visible} animationType="slide" onRequestClose={onClose}>
      <View style={styles.formOverlay}>
        <View style={[styles.formSheet, { backgroundColor: colors.card, borderColor: colors.border }]}>
          <View style={styles.formHeader}>
            <Text style={[styles.formTitle, { color: colors.textPrimary }]}>
              {mode === 'create' ? 'New Challenge' : 'Edit Challenge'}
            </Text>
            <Pressable onPress={onClose} style={[styles.closeBtn, { backgroundColor: colors.inputBg }]}>
              <Ionicons name="close" size={20} color={colors.textMuted} />
            </Pressable>
          </View>
          <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={{ gap: 12, paddingBottom: 20 }}>
            <View>
              <Text style={[styles.fieldLabel, { color: colors.textSecondary }]}>Title *</Text>
              <TextInput
                style={fieldStyle}
                value={title}
                onChangeText={setTitle}
                placeholder="e.g. Weekend Warrior"
                placeholderTextColor={placeholderColor}
              />
            </View>
            <View>
              <Text style={[styles.fieldLabel, { color: colors.textSecondary }]}>Description</Text>
              <TextInput
                style={[fieldStyle, { height: 70, textAlignVertical: 'top' }]}
                value={description}
                onChangeText={setDescription}
                placeholder="Optional description"
                placeholderTextColor={placeholderColor}
                multiline
              />
            </View>
            <View style={styles.row}>
              <View style={{ flex: 1 }}>
                <Text style={[styles.fieldLabel, { color: colors.textSecondary }]}>Entry Fee (tokens)</Text>
                <TextInput
                  style={fieldStyle}
                  value={entryFee}
                  onChangeText={setEntryFee}
                  keyboardType="numeric"
                  placeholder="0"
                  placeholderTextColor={placeholderColor}
                />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={[styles.fieldLabel, { color: colors.textSecondary }]}>Prize Pool (tokens)</Text>
                <TextInput
                  style={fieldStyle}
                  value={prizePool}
                  onChangeText={setPrizePool}
                  keyboardType="numeric"
                  placeholder="0"
                  placeholderTextColor={placeholderColor}
                />
              </View>
            </View>
            <Text style={[styles.fieldLabel, { color: Colors.purple, marginBottom: -4 }]}>
              Rank Rewards (optional — overrides % split)
            </Text>
            <View style={styles.row}>
              <View style={{ flex: 1 }}>
                <Text style={[styles.fieldLabel, { color: colors.textSecondary }]}>🥇 1st Place</Text>
                <TextInput
                  style={fieldStyle}
                  value={rank1Reward}
                  onChangeText={setRank1Reward}
                  keyboardType="numeric"
                  placeholder="e.g. 500"
                  placeholderTextColor={placeholderColor}
                />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={[styles.fieldLabel, { color: colors.textSecondary }]}>🥈 2nd Place</Text>
                <TextInput
                  style={fieldStyle}
                  value={rank2Reward}
                  onChangeText={setRank2Reward}
                  keyboardType="numeric"
                  placeholder="e.g. 300"
                  placeholderTextColor={placeholderColor}
                />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={[styles.fieldLabel, { color: colors.textSecondary }]}>🥉 3rd Place</Text>
                <TextInput
                  style={fieldStyle}
                  value={rank3Reward}
                  onChangeText={setRank3Reward}
                  keyboardType="numeric"
                  placeholder="e.g. 200"
                  placeholderTextColor={placeholderColor}
                />
              </View>
            </View>
            <View>
              <Text style={[styles.fieldLabel, { color: colors.textSecondary }]}>Starts At (YYYY-MM-DDTHH:mm)</Text>
              <TextInput
                style={fieldStyle}
                value={startsAt}
                onChangeText={setStartsAt}
                placeholder="2025-01-01T12:00"
                placeholderTextColor={placeholderColor}
                autoCapitalize="none"
              />
            </View>
            <View>
              <Text style={[styles.fieldLabel, { color: colors.textSecondary }]}>Ends At (YYYY-MM-DDTHH:mm)</Text>
              <TextInput
                style={fieldStyle}
                value={endsAt}
                onChangeText={setEndsAt}
                placeholder="2025-01-02T12:00"
                placeholderTextColor={placeholderColor}
                autoCapitalize="none"
              />
            </View>
            <View>
              <Text style={[styles.fieldLabel, { color: colors.textSecondary }]}>Max Participants (blank = unlimited)</Text>
              <TextInput
                style={fieldStyle}
                value={maxParticipants}
                onChangeText={setMaxParticipants}
                keyboardType="numeric"
                placeholder="e.g. 50"
                placeholderTextColor={placeholderColor}
              />
            </View>
          </ScrollView>
          <Pressable
            onPress={handleSave}
            style={[styles.saveBtn, saving && { opacity: 0.7 }]}
            disabled={saving}
          >
            {saving ? (
              <ActivityIndicator size="small" color={Colors.white} />
            ) : (
              <Text style={styles.saveBtnText}>{mode === 'create' ? 'Create Challenge' : 'Save Changes'}</Text>
            )}
          </Pressable>
        </View>
      </View>
    </Modal>
  );
}

function LeaderboardRow({ entry, colors }: {
  entry: { rank: number; name: string; wins: number; losses: number };
  colors: ReturnType<typeof useTheme>['colors'];
}) {
  const medal = entry.rank === 1 ? '🥇' : entry.rank === 2 ? '🥈' : '🥉';
  return (
    <View style={styles.lbRow}>
      <Text style={styles.lbMedal}>{medal}</Text>
      <Text style={[styles.lbName, { color: colors.textPrimary }]} numberOfLines={1}>{entry.name}</Text>
      <Text style={[styles.lbStat, { color: colors.textMuted }]}>{entry.wins}W / {entry.losses}L</Text>
    </View>
  );
}

export default function AdminChallengesScreen() {
  const insets = useSafeAreaInsets();
  const { isAdmin } = useAuth();
  const { colors } = useTheme();
  const [challenges, setChallenges] = useState<AdminChallenge[]>([]);
  const [loading, setLoading] = useState(false);
  const [formVisible, setFormVisible] = useState(false);
  const [formMode, setFormMode] = useState<FormMode>('create');
  const [editTarget, setEditTarget] = useState<Challenge | null>(null);

  useEffect(() => {
    if (!isAdmin) {
      router.replace('/(tabs)/me');
    }
  }, [isAdmin]);

  const load = useCallback(async () => {
    setLoading(true);
    const data = await fetchAdminChallenges();
    setChallenges(data as AdminChallenge[]);
    setLoading(false);
  }, []);

  useEffect(() => {
    if (isAdmin) load();
  }, [isAdmin, load]);

  const openCreate = () => {
    setFormMode('create');
    setEditTarget(null);
    setFormVisible(true);
  };

  const openEdit = (c: Challenge) => {
    setFormMode('edit');
    setEditTarget(c);
    setFormVisible(true);
  };

  const handleComplete = (c: Challenge) => {
    Alert.alert(
      'Complete Challenge',
      `Distribute prizes and close "${c.title}"? This cannot be undone.`,
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Complete',
          style: 'destructive',
          onPress: async () => {
            const result = await completeChallenge(c.id);
            if (result.ok) {
              if (result.rewards && result.rewards.length > 0) {
                const summary = result.rewards
                  .map((r) => `Rank #${r.rank}: ${r.name} — ${r.amount} tokens`)
                  .join('\n');
                Alert.alert('Rewards Distributed', summary);
              }
              load();
            } else {
              Alert.alert('Error', 'Failed to complete challenge');
            }
          },
        },
      ],
    );
  };

  const handleCancel = (c: Challenge) => {
    Alert.alert(
      'Cancel Challenge',
      `Cancel "${c.title}"? Entry fees will be refunded.`,
      [
        { text: 'No', style: 'cancel' },
        {
          text: 'Cancel Challenge',
          style: 'destructive',
          onPress: async () => {
            const ok = await cancelChallenge(c.id);
            if (ok) load();
            else Alert.alert('Error', 'Failed to cancel challenge');
          },
        },
      ],
    );
  };

  const handleActivate = async (c: Challenge) => {
    const ok = await updateChallenge(c.id, { status: 'active' });
    if (ok) load();
    else Alert.alert('Error', 'Failed to activate challenge');
  };

  if (!isAdmin) {
    return (
      <View style={[styles.container, styles.center, { backgroundColor: colors.background }]}>
        <Ionicons name="lock-closed-outline" size={48} color={colors.textFaint} />
        <Text style={[styles.accessDeniedText, { color: colors.textMuted }]}>Access denied</Text>
      </View>
    );
  }

  return (
    <View style={[styles.container, { paddingTop: insets.top, backgroundColor: colors.background }]}>
      <View style={styles.header}>
        <Pressable onPress={() => router.back()} style={[styles.backBtn, { backgroundColor: colors.inputBg }]}>
          <Ionicons name="arrow-back" size={22} color={colors.textPrimary} />
        </Pressable>
        <View style={{ flex: 1 }}>
          <Text style={[styles.headerTitle, { color: colors.textPrimary }]}>Admin: Challenges</Text>
          <Text style={[styles.headerSub, { color: colors.textMuted }]}>Manage tournament challenges</Text>
        </View>
        <Pressable onPress={load} style={[styles.refreshBtn, { backgroundColor: colors.inputBg }]}>
          <Ionicons name="refresh" size={18} color={Colors.purple} />
        </Pressable>
        <Pressable onPress={openCreate} style={[styles.addBtn, { backgroundColor: Colors.purple + '22' }]}>
          <Ionicons name="add" size={22} color={colors.textPrimary} />
        </Pressable>
      </View>

      {loading ? (
        <View style={styles.center}>
          <ActivityIndicator color={Colors.purple} size="large" />
        </View>
      ) : (
        <FlatList
          data={challenges}
          keyExtractor={(item) => item.id}
          contentContainerStyle={[styles.list, { paddingBottom: insets.bottom + 20 }]}
          showsVerticalScrollIndicator={false}
          ListEmptyComponent={
            <View style={styles.empty}>
              <Ionicons name="trophy-outline" size={48} color={colors.textFaint} />
              <Text style={[styles.emptyText, { color: colors.textMuted }]}>No challenges yet. Tap + to create one.</Text>
            </View>
          }
          renderItem={({ item: c }) => {
            const color = statusColor(c.status);
            const canActivate = c.status === 'upcoming';
            const canComplete = c.status === 'active';
            const canCancel = c.status === 'active' || c.status === 'upcoming';
            const topLb = (c as AdminChallenge).topLeaderboard ?? [];

            return (
              <View style={[styles.adminCard, { backgroundColor: colors.card, borderColor: colors.border }]}>
                <View style={styles.adminCardTop}>
                  <View style={[styles.statusChip, { backgroundColor: color + '22', borderColor: color + '50' }]}>
                    <View style={[styles.statusDot, { backgroundColor: color }]} />
                    <Text style={[styles.statusText, { color }]}>{c.status.toUpperCase()}</Text>
                  </View>
                  <Text style={[styles.participantCount, { color: colors.textMuted }]}>{c.participantCount} joined</Text>
                </View>
                <Text style={[styles.adminCardTitle, { color: colors.textPrimary }]}>{c.title}</Text>
                <View style={styles.adminMeta}>
                  <Text style={[styles.metaText, { color: colors.textMuted }]}>Fee: 🪙{c.entryFee} · Pool: 🏆{c.prizePool}</Text>
                  {(c.rank1Reward || c.rank2Reward || c.rank3Reward) ? (
                    <Text style={[styles.metaText, { color: colors.textMuted }]}>
                      Rewards: 🥇{c.rank1Reward ?? '–'} 🥈{c.rank2Reward ?? '–'} 🥉{c.rank3Reward ?? '–'}
                    </Text>
                  ) : null}
                  <Text style={[styles.metaText, { color: colors.textMuted }]}>
                    {new Date(c.startAt).toLocaleDateString()} → {new Date(c.endAt).toLocaleDateString()}
                  </Text>
                </View>

                {topLb.length > 0 && (
                  <View style={[styles.lbSection, { backgroundColor: colors.subtleBg }]}>
                    <Text style={[styles.lbTitle, { color: colors.textMuted }]}>Top Players</Text>
                    {topLb.map((entry) => (
                      <LeaderboardRow key={entry.playerId} entry={entry} colors={colors} />
                    ))}
                  </View>
                )}

                <View style={styles.adminActions}>
                  <Pressable onPress={() => openEdit(c)} style={[styles.actionBtn, { backgroundColor: colors.inputBg }]}>
                    <Ionicons name="pencil" size={14} color={Colors.blue} />
                    <Text style={[styles.actionText, { color: Colors.blue }]}>Edit</Text>
                  </Pressable>
                  {canActivate && (
                    <Pressable onPress={() => handleActivate(c)} style={[styles.actionBtn, { backgroundColor: colors.inputBg }]}>
                      <Ionicons name="play" size={14} color={Colors.green} />
                      <Text style={[styles.actionText, { color: Colors.green }]}>Activate</Text>
                    </Pressable>
                  )}
                  {canComplete && (
                    <Pressable onPress={() => handleComplete(c)} style={[styles.actionBtn, { backgroundColor: colors.inputBg }]}>
                      <Ionicons name="ribbon" size={14} color={Colors.yellow} />
                      <Text style={[styles.actionText, { color: Colors.yellow }]}>Complete</Text>
                    </Pressable>
                  )}
                  {canCancel && (
                    <Pressable onPress={() => handleCancel(c)} style={[styles.actionBtn, { backgroundColor: colors.inputBg }]}>
                      <Ionicons name="close-circle" size={14} color={Colors.coral} />
                      <Text style={[styles.actionText, { color: Colors.coral }]}>Cancel</Text>
                    </Pressable>
                  )}
                </View>
              </View>
            );
          }}
        />
      )}

      <ChallengeForm
        visible={formVisible}
        mode={formMode}
        initial={editTarget}
        onClose={() => setFormVisible(false)}
        onSaved={load}
        colors={colors}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },

  header: {
    flexDirection: 'row', alignItems: 'center',
    paddingHorizontal: 18, paddingVertical: 14, gap: 12,
  },
  backBtn: {
    width: 40, height: 40, borderRadius: 12,
    alignItems: 'center', justifyContent: 'center',
  },
  headerTitle: { fontSize: 20, fontFamily: 'Inter_700Bold' },
  headerSub: { fontSize: 12, fontFamily: 'Inter_400Regular', marginTop: 1 },
  addBtn: {
    width: 40, height: 40, borderRadius: 12,
    alignItems: 'center', justifyContent: 'center',
  },
  refreshBtn: {
    width: 36, height: 36, borderRadius: 10,
    alignItems: 'center', justifyContent: 'center',
  },

  list: { paddingHorizontal: 18, gap: 12, paddingTop: 4 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  empty: { alignItems: 'center', paddingTop: 60, gap: 12 },
  emptyText: { fontSize: 14, fontFamily: 'Inter_400Regular', textAlign: 'center' },

  accessDeniedText: {
    fontSize: 16, fontFamily: 'Inter_600SemiBold', marginTop: 12,
  },

  adminCard: {
    borderRadius: 18, padding: 16, gap: 8,
    borderWidth: 1,
  },
  adminCardTop: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  statusChip: {
    flexDirection: 'row', alignItems: 'center', gap: 5,
    paddingHorizontal: 10, paddingVertical: 4,
    borderRadius: 20, borderWidth: 1,
  },
  statusDot: { width: 6, height: 6, borderRadius: 3 },
  statusText: { fontSize: 10, fontFamily: 'Inter_700Bold', letterSpacing: 0.8 },
  participantCount: { fontSize: 12, fontFamily: 'Inter_400Regular' },
  adminCardTitle: { fontSize: 16, fontFamily: 'Inter_700Bold' },
  adminMeta: { gap: 2 },
  metaText: { fontSize: 12, fontFamily: 'Inter_400Regular' },

  lbSection: {
    borderRadius: 10,
    padding: 10, gap: 6, marginTop: 2,
  },
  lbTitle: { fontSize: 11, fontFamily: 'Inter_600SemiBold', marginBottom: 2 },
  lbRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  lbMedal: { fontSize: 14, width: 20 },
  lbName: { flex: 1, fontSize: 13, fontFamily: 'Inter_600SemiBold' },
  lbStat: { fontSize: 12, fontFamily: 'Inter_400Regular' },

  adminActions: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 4 },
  actionBtn: {
    flexDirection: 'row', alignItems: 'center', gap: 4,
    paddingHorizontal: 12, paddingVertical: 6,
    borderRadius: 10,
  },
  actionText: { fontSize: 12, fontFamily: 'Inter_600SemiBold' },

  formOverlay: {
    flex: 1, backgroundColor: 'rgba(0,0,0,0.7)',
    justifyContent: 'flex-end',
  },
  formSheet: {
    borderTopLeftRadius: 28, borderTopRightRadius: 28,
    padding: 24, maxHeight: '90%',
    borderWidth: 1,
  },
  formHeader: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    marginBottom: 16,
  },
  formTitle: { fontSize: 20, fontFamily: 'Inter_700Bold' },
  closeBtn: {
    width: 36, height: 36, borderRadius: 10,
    alignItems: 'center', justifyContent: 'center',
  },
  row: { flexDirection: 'row', gap: 10 },
  fieldLabel: { fontSize: 12, fontFamily: 'Inter_600SemiBold', marginBottom: 6 },
  input: {
    borderRadius: 12, padding: 12,
    fontFamily: 'Inter_400Regular', fontSize: 14,
    borderWidth: 1,
  },
  saveBtn: {
    backgroundColor: Colors.purple, borderRadius: 14, padding: 16,
    alignItems: 'center', marginTop: 8,
  },
  saveBtnText: { fontSize: 16, fontFamily: 'Inter_700Bold', color: Colors.white },
});
