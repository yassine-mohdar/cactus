import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import React from 'react';
import {
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
import { PremiumCard } from '@/src/components/PremiumCard';

type IoniconsName = React.ComponentProps<typeof Ionicons>['name'];

interface Mission {
  id: string;
  icon: IoniconsName;
  title: string;
  desc: string;
  progress: number;
  goal: number;
  reward: string;
  color: string;
  colorPale: string;
  completed: boolean;
}

export default function MissionsScreen() {
  const insets = useSafeAreaInsets();
  const profile = useAuthStore(s => s.profile);
  const { colors } = useTheme();
  const stats = profile?.stats;

  const missions: Mission[] = [
    {
      id: 'first_win',
      icon: 'trophy',
      title: 'First Victory',
      desc: 'Win your first match',
      progress: Math.min(stats?.totalWins ?? 0, 1),
      goal: 1,
      reward: '🌵 Cactus Badge',
      color: Colors.green,
      colorPale: Colors.greenPale,
      completed: (stats?.totalWins ?? 0) >= 1,
    },
    {
      id: 'win_5',
      icon: 'flame',
      title: 'On Fire',
      desc: 'Win 5 matches',
      progress: Math.min(stats?.totalWins ?? 0, 5),
      goal: 5,
      reward: '🔥 Fire Badge',
      color: Colors.coral,
      colorPale: Colors.coralPale,
      completed: (stats?.totalWins ?? 0) >= 5,
    },
    {
      id: 'streak_3',
      icon: 'flash',
      title: 'Hot Streak',
      desc: 'Win 3 matches in a row',
      progress: Math.min(stats?.bestStreak ?? 0, 3),
      goal: 3,
      reward: '⚡ Lightning Badge',
      color: Colors.yellow,
      colorPale: Colors.yellowPale,
      completed: (stats?.bestStreak ?? 0) >= 3,
    },
    {
      id: 'play_10',
      icon: 'game-controller',
      title: 'Dedicated Player',
      desc: 'Play 10 total matches',
      progress: Math.min(stats?.totalMatches ?? 0, 10),
      goal: 10,
      reward: '🎮 Controller Badge',
      color: Colors.blue,
      colorPale: Colors.bluePale,
      completed: (stats?.totalMatches ?? 0) >= 10,
    },
    {
      id: 'win_10',
      icon: 'star',
      title: 'XO Champion',
      desc: 'Win 10 matches',
      progress: Math.min(stats?.totalWins ?? 0, 10),
      goal: 10,
      reward: '⭐ Star Badge',
      color: Colors.purple,
      colorPale: Colors.purplePale,
      completed: (stats?.totalWins ?? 0) >= 10,
    },
    {
      id: 'streak_5',
      icon: 'diamond',
      title: 'Unstoppable',
      desc: 'Win 5 matches in a row',
      progress: Math.min(stats?.bestStreak ?? 0, 5),
      goal: 5,
      reward: '💎 Diamond Badge',
      color: Colors.green,
      colorPale: Colors.greenPale,
      completed: (stats?.bestStreak ?? 0) >= 5,
    },
  ];

  const completed = missions.filter(m => m.completed).length;

  return (
    <View style={[styles.container, { paddingTop: insets.top, backgroundColor: colors.background }]}>
      <View style={styles.header}>
        <Pressable onPress={() => router.back()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={24} color={colors.textPrimary} />
        </Pressable>
        <Text style={[styles.title, { color: colors.textPrimary }]}>Missions</Text>
        <View style={{ width: 40 }} />
      </View>

      <PremiumCard style={styles.progressCard}>
        <View style={styles.progressRow}>
          <View>
            <Text style={[styles.progressLabel, { color: colors.textMuted }]}>Missions Completed</Text>
            <Text style={[styles.progressCount, { color: colors.textPrimary }]}>{completed}/{missions.length}</Text>
          </View>
          <View style={styles.progressBarWrap}>
            <View style={[styles.progressBarBg, { backgroundColor: colors.inputBg }]}>
              <View style={[styles.progressBarFill, { width: `${(completed / missions.length) * 100}%` }]} />
            </View>
          </View>
        </View>
      </PremiumCard>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.list}>
        {missions.map(m => (
          <PremiumCard key={m.id} style={[styles.missionCard, m.completed && styles.missionCompleted]}>
            <View style={styles.missionHeader}>
              <View style={[styles.missionIcon, { backgroundColor: m.completed ? m.color : m.colorPale }]}>
                <Ionicons name={m.icon} size={20} color={m.completed ? Colors.white : m.color} />
              </View>
              <View style={styles.missionInfo}>
                <Text style={[styles.missionTitle, { color: colors.textPrimary }]}>{m.title}</Text>
                <Text style={[styles.missionDesc, { color: colors.textSecondary }]}>{m.desc}</Text>
              </View>
              {m.completed && (
                <View style={[styles.completedBadge, { backgroundColor: m.colorPale }]}>
                  <Ionicons name="checkmark" size={16} color={m.color} />
                </View>
              )}
            </View>

            {!m.completed && (
              <View style={styles.progressSection}>
                <View style={[styles.progressBarBg, { backgroundColor: colors.inputBg }]}>
                  <View
                    style={[
                      styles.progressBarFill,
                      { width: `${(m.progress / m.goal) * 100}%`, backgroundColor: m.color },
                    ]}
                  />
                </View>
                <Text style={[styles.progressText, { color: colors.textFaint }]}>{m.progress}/{m.goal}</Text>
              </View>
            )}

            <View style={styles.rewardRow}>
              <Ionicons name="gift-outline" size={14} color={m.completed ? m.color : colors.textFaint} />
              <Text style={[styles.rewardText, { color: m.completed ? m.color : colors.textFaint }]}>{m.reward}</Text>
            </View>
          </PremiumCard>
        ))}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingVertical: 12,
  },
  backBtn: { width: 40, height: 40, alignItems: 'center', justifyContent: 'center' },
  title: { fontSize: 20, fontFamily: 'Inter_700Bold' },
  progressCard: { marginHorizontal: 20, marginBottom: 4 },
  progressRow: { gap: 10 },
  progressLabel: {
    fontSize: 13, fontFamily: 'Inter_500Medium',
    textTransform: 'uppercase', letterSpacing: 0.5,
  },
  progressCount: { fontSize: 28, fontFamily: 'Inter_700Bold' },
  progressBarWrap: {},
  progressBarBg: { height: 6, borderRadius: 3, overflow: 'hidden' },
  progressBarFill: { height: '100%', backgroundColor: Colors.green, borderRadius: 3 },
  list: { paddingHorizontal: 20, paddingBottom: 32, gap: 10 },
  missionCard: { gap: 10 },
  missionCompleted: { opacity: 0.9 },
  missionHeader: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  missionIcon: { width: 44, height: 44, borderRadius: 14, alignItems: 'center', justifyContent: 'center' },
  missionInfo: { flex: 1 },
  missionTitle: { fontSize: 15, fontFamily: 'Inter_600SemiBold' },
  missionDesc: { fontSize: 12, fontFamily: 'Inter_400Regular', marginTop: 2 },
  completedBadge: { width: 32, height: 32, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
  progressSection: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  progressText: { fontSize: 12, fontFamily: 'Inter_500Medium', minWidth: 28 },
  rewardRow: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  rewardText: { fontSize: 12, fontFamily: 'Inter_500Medium' },
});
