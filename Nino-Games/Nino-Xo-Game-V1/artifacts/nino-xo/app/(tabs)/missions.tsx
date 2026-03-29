import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useAuthStore } from '@/src/stores/authStore';
import { useTheme } from '@/src/context/ThemeContext';

type IoniconsName = React.ComponentProps<typeof Ionicons>['name'];

interface Mission {
  id: string; icon: IoniconsName; title: string; desc: string;
  progress: number; goal: number; reward: string; color: string; completed: boolean;
}

export default function MissionsTab() {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const profile = useAuthStore(s => s.profile);
  const stats = profile?.stats;

  const missions: Mission[] = [
    { id: 'first_win',  icon: 'trophy',        title: 'First Victory',    desc: 'Win your first match',          progress: Math.min(stats?.totalWins ?? 0, 1),  goal: 1,  reward: '🌵 Cactus Badge',  color: Colors.green,  completed: (stats?.totalWins ?? 0) >= 1 },
    { id: 'win_5',      icon: 'flame',          title: 'On Fire',          desc: 'Win 5 matches',                 progress: Math.min(stats?.totalWins ?? 0, 5),  goal: 5,  reward: '🔥 Fire Badge',    color: Colors.coral,  completed: (stats?.totalWins ?? 0) >= 5 },
    { id: 'streak_3',   icon: 'flash',          title: 'Hot Streak',       desc: 'Win 3 in a row',                progress: Math.min(stats?.streak ?? 0, 3),     goal: 3,  reward: '⚡ Streak Badge',  color: Colors.yellow, completed: (stats?.streak ?? 0) >= 3 },
    { id: 'play_10',    icon: 'game-controller',title: 'Dedicated Player', desc: 'Play 10 matches',               progress: Math.min(stats?.totalMatches ?? 0, 10), goal: 10, reward: '🎮 Player Badge', color: Colors.blue,   completed: (stats?.totalMatches ?? 0) >= 10 },
    { id: 'win_10',     icon: 'star',           title: 'Rising Star',      desc: 'Win 10 matches',                progress: Math.min(stats?.totalWins ?? 0, 10), goal: 10, reward: '⭐ Star Badge',    color: Colors.purple, completed: (stats?.totalWins ?? 0) >= 10 },
    { id: 'streak_5',   icon: 'infinite',       title: 'Unstoppable',      desc: 'Reach a 5-win streak',          progress: Math.min(stats?.streak ?? 0, 5),     goal: 5,  reward: '♾️ Infinity Badge', color: Colors.pink,  completed: (stats?.streak ?? 0) >= 5 },
  ];

  const completed = missions.filter(m => m.completed).length;

  return (
    <View style={[styles.container, { paddingTop: insets.top + 14, backgroundColor: colors.background }]}>
      <View style={[styles.blob, { top: -40, left: -60, backgroundColor: Colors.purple, width: 220, height: 220, opacity: 0.07 }]} />

      <View style={styles.header}>
        <View>
          <Text style={[styles.title, { color: colors.textPrimary }]}>Quests</Text>
          <Text style={[styles.subtitle, { color: colors.textFaint }]}>{completed}/{missions.length} completed</Text>
        </View>
        <View style={[styles.progressCircle, { borderColor: Colors.purple + '50', backgroundColor: colors.subtleBg }]}>
          <Text style={[styles.progressNum, { color: Colors.purple }]}>{completed}</Text>
          <Text style={[styles.progressDen, { color: colors.textFaint }]}>/{missions.length}</Text>
        </View>
      </View>

      <View style={[styles.overallBar, { backgroundColor: colors.border }]}>
        <View style={[styles.overallFill, { width: `${(completed / missions.length) * 100}%` as any, backgroundColor: Colors.purple }]} />
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.list}>
        {missions.map(m => {
          const pct = Math.min(m.progress / m.goal, 1);
          return (
            <View key={m.id} style={[styles.card, { backgroundColor: colors.card, borderColor: colors.border }, m.completed && styles.cardDone]}>
              <View style={[styles.iconWrap, { backgroundColor: m.color + '20' }]}>
                <Ionicons name={m.icon} size={22} color={m.completed ? m.color : m.color + 'AA'} />
              </View>
              <View style={styles.cardBody}>
                <View style={styles.cardTop}>
                  <Text style={[styles.cardTitle, { color: colors.textPrimary }, m.completed && { color: colors.textSecondary }]}>{m.title}</Text>
                  {m.completed && (
                    <View style={[styles.doneBadge, { backgroundColor: Colors.green + '22' }]}>
                      <Ionicons name="checkmark" size={12} color={Colors.green} />
                      <Text style={[styles.doneText, { color: Colors.green }]}>Done</Text>
                    </View>
                  )}
                </View>
                <Text style={[styles.cardDesc, { color: colors.textMuted }]}>{m.desc}</Text>
                <View style={[styles.progressTrack, { backgroundColor: colors.border }]}>
                  <View style={[styles.progressFill, { width: `${pct * 100}%` as any, backgroundColor: m.color }]} />
                </View>
                <View style={styles.cardBottom}>
                  <Text style={[styles.progressLabel, { color: colors.textFaint }]}>{m.progress}/{m.goal}</Text>
                  <Text style={[styles.rewardLabel, { color: m.color }]}>{m.reward}</Text>
                </View>
              </View>
            </View>
          );
        })}
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
  progressCircle: {
    width: 52, height: 52, borderRadius: 26,
    borderWidth: 2,
    alignItems: 'center', justifyContent: 'center', flexDirection: 'row',
  },
  progressNum: { fontSize: 16, fontFamily: 'Inter_700Bold' },
  progressDen: { fontSize: 11, fontFamily: 'Inter_400Regular' },

  overallBar: {
    height: 4,
    marginHorizontal: 20, borderRadius: 2, marginBottom: 16, overflow: 'hidden',
  },
  overallFill: { height: '100%', borderRadius: 2 },

  list: { paddingHorizontal: 18, gap: 10 },
  card: {
    flexDirection: 'row', gap: 14,
    borderRadius: 20, padding: 16,
    borderWidth: 1,
  },
  cardDone: { opacity: 0.6 },
  iconWrap: { width: 48, height: 48, borderRadius: 15, alignItems: 'center', justifyContent: 'center' },
  cardBody: { flex: 1, gap: 4 },
  cardTop: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  cardTitle: { fontSize: 15, fontFamily: 'Inter_700Bold' },
  doneBadge: { flexDirection: 'row', alignItems: 'center', gap: 4, paddingHorizontal: 8, paddingVertical: 3, borderRadius: 8 },
  doneText: { fontSize: 11, fontFamily: 'Inter_600SemiBold' },
  cardDesc: { fontSize: 12, fontFamily: 'Inter_400Regular' },
  progressTrack: { height: 4, borderRadius: 2, overflow: 'hidden', marginTop: 4 },
  progressFill: { height: '100%', borderRadius: 2 },
  cardBottom: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 2 },
  progressLabel: { fontSize: 11, fontFamily: 'Inter_600SemiBold' },
  rewardLabel: { fontSize: 11, fontFamily: 'Inter_700Bold' },
});
