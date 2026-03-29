import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import React, { useEffect } from 'react';
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
import { NinoButton } from '@/src/components/NinoButton';

type IoniconsName = React.ComponentProps<typeof Ionicons>['name'];

interface StatItem {
  label: string;
  value: string | number;
  icon: IoniconsName;
  color: string;
  colorPale: string;
  description: string;
}

export default function StatsScreen() {
  const insets = useSafeAreaInsets();
  const profile = useAuthStore(s => s.profile);
  const { colors } = useTheme();

  useEffect(() => {
    if (!profile) router.replace('/login');
  }, [profile]);

  if (!profile) return null;

  const { stats } = profile;
  const winRate = stats.totalMatches > 0
    ? ((stats.totalWins / stats.totalMatches) * 100).toFixed(1)
    : '0.0';
  const losses = stats.totalMatches - stats.totalWins;

  const statItems: StatItem[] = [
    {
      label: 'Total Wins',
      value: stats.totalWins,
      icon: 'trophy',
      color: Colors.yellow,
      colorPale: Colors.yellowPale,
      description: 'Matches won across all modes',
    },
    {
      label: 'Win Rate',
      value: `${winRate}%`,
      icon: 'trending-up',
      color: Colors.green,
      colorPale: Colors.greenPale,
      description: 'Percentage of games won',
    },
    {
      label: 'Best Streak',
      value: stats.bestStreak,
      icon: 'flame',
      color: Colors.coral,
      colorPale: Colors.coralPale,
      description: 'Highest consecutive wins',
    },
    {
      label: 'Current Streak',
      value: stats.streak,
      icon: 'flash',
      color: Colors.orange ?? Colors.coral,
      colorPale: Colors.coralPale,
      description: 'Current win streak',
    },
    {
      label: 'Total Played',
      value: stats.totalMatches,
      icon: 'game-controller',
      color: Colors.blue,
      colorPale: Colors.bluePale,
      description: 'Total matches played',
    },
    {
      label: 'Missions Done',
      value: `${stats.missionProgress}/${stats.missionGoal}`,
      icon: 'flag',
      color: Colors.purple,
      colorPale: Colors.purplePale,
      description: 'Daily mission progress',
    },
  ];

  return (
    <View style={[styles.container, { paddingTop: insets.top, backgroundColor: colors.background }]}>
      <View style={styles.header}>
        <Pressable onPress={() => router.back()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={24} color={colors.textPrimary} />
        </Pressable>
        <Text style={[styles.title, { color: colors.textPrimary }]}>Detailed Stats</Text>
        <View style={{ width: 40 }} />
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.list}>
        <PremiumCard style={styles.summaryCard}>
          <Text style={[styles.sectionLabel, { color: colors.textMuted }]}>Performance Overview</Text>
          <View style={styles.perfRow}>
            <View style={[styles.perfBlock, { backgroundColor: Colors.greenPale }]}>
              <Text style={[styles.perfNum, { color: Colors.green }]}>{stats.totalWins}</Text>
              <Text style={[styles.perfLabel, { color: colors.textSecondary }]}>W</Text>
            </View>
            <View style={styles.perfDash}><Text style={[styles.perfDashText, { color: colors.textMuted }]}>–</Text></View>
            <View style={[styles.perfBlock, { backgroundColor: Colors.coralPale }]}>
              <Text style={[styles.perfNum, { color: Colors.coral }]}>{losses}</Text>
              <Text style={[styles.perfLabel, { color: colors.textSecondary }]}>L</Text>
            </View>
            <View style={styles.perfDash}><Text style={[styles.perfDashText, { color: colors.textMuted }]}>·</Text></View>
            <View style={[styles.perfBlock, { backgroundColor: Colors.yellowPale }]}>
              <Text style={[styles.perfNum, { color: Colors.yellow }]}>{winRate}%</Text>
              <Text style={[styles.perfLabel, { color: colors.textSecondary }]}>Win Rate</Text>
            </View>
          </View>
        </PremiumCard>

        <View style={styles.grid}>
          {statItems.map((item, i) => (
            <PremiumCard key={i} style={styles.statCard}>
              <View style={[styles.statIcon, { backgroundColor: item.colorPale }]}>
                <Ionicons name={item.icon} size={20} color={item.color} />
              </View>
              <Text style={[styles.statNum, { color: item.color }]}>{item.value}</Text>
              <Text style={[styles.statLabel, { color: colors.textPrimary }]}>{item.label}</Text>
              <Text style={[styles.statDesc, { color: colors.textMuted }]}>{item.description}</Text>
            </PremiumCard>
          ))}
        </View>

        {stats.totalMatches === 0 && (
          <NinoButton
            label="Play Your First Match"
            onPress={() => router.replace('/home')}
            color={Colors.green}
            fullWidth
          />
        )}
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
  list: { paddingHorizontal: 20, paddingBottom: 32, gap: 14 },
  summaryCard: {},
  sectionLabel: {
    fontSize: 12,
    fontFamily: 'Inter_500Medium',
    textTransform: 'uppercase',
    letterSpacing: 0.8,
    marginBottom: 12,
  },
  perfRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8 },
  perfBlock: { borderRadius: 14, paddingVertical: 12, paddingHorizontal: 16, alignItems: 'center', minWidth: 56 },
  perfNum: { fontSize: 26, fontFamily: 'Inter_700Bold' },
  perfLabel: { fontSize: 12, fontFamily: 'Inter_500Medium', marginTop: 2 },
  perfDash: {},
  perfDashText: { fontSize: 20, fontFamily: 'Inter_400Regular' },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  statCard: { width: '46%', gap: 6 },
  statIcon: { width: 40, height: 40, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
  statNum: { fontSize: 28, fontFamily: 'Inter_700Bold' },
  statLabel: { fontSize: 14, fontFamily: 'Inter_600SemiBold' },
  statDesc: { fontSize: 11, fontFamily: 'Inter_400Regular', lineHeight: 15 },
});
