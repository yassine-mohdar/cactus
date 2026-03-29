import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import React, { useState } from 'react';
import {
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useTheme } from '@/src/context/ThemeContext';
import { NinoButton } from '@/src/components/NinoButton';
import { PremiumCard } from '@/src/components/PremiumCard';

type IoniconsName = React.ComponentProps<typeof Ionicons>['name'];

const STEPS: { icon: IoniconsName; title: string; desc: string; color: string; colorPale: string }[] = [
  {
    icon: 'people',
    title: 'Choose Your Character',
    desc: 'Pick one of 6 unique NinoWorld cactus characters. Each has their own personality and style.',
    color: Colors.green,
    colorPale: Colors.greenPale,
  },
  {
    icon: 'hardware-chip',
    title: 'Select a Game Mode',
    desc: 'Play against a bot (Easy or Medium), find a quick match online, or create a private room for a friend.',
    color: Colors.blue,
    colorPale: Colors.bluePale,
  },
  {
    icon: 'grid',
    title: 'Play on the 3×3 Board',
    desc: 'Take turns placing your character token on the board. Get 3 in a row — horizontal, vertical, or diagonal — to win!',
    color: Colors.coral,
    colorPale: Colors.coralPale,
  },
  {
    icon: 'trophy',
    title: 'Earn Rewards',
    desc: 'Win matches to earn badges, build streaks, and complete daily missions. Track your stats on your profile.',
    color: Colors.yellow,
    colorPale: Colors.yellowPale,
  },
];

const TIPS = [
  'Control the center — it\'s the most powerful square on the board.',
  'Block your opponent before setting up your own win.',
  'Create two threats at once to guarantee a win.',
  'On Easy bot, the bot moves randomly. Practice your strategy!',
  'On Medium bot, it will try to win and block — play carefully.',
];

export default function HowToPlayScreen() {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const [expanded, setExpanded] = useState<number | null>(null);

  return (
    <ScrollView
      style={[styles.container, { backgroundColor: colors.background }]}
      contentContainerStyle={[styles.content, { paddingTop: insets.top + 12, paddingBottom: insets.bottom + 32 }]}
      showsVerticalScrollIndicator={false}
    >
      <View style={styles.header}>
        <Pressable onPress={() => router.back()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={24} color={colors.textPrimary} />
        </Pressable>
        <Text style={[styles.title, { color: colors.textPrimary }]}>How to Play</Text>
        <View style={{ width: 40 }} />
      </View>

      <PremiumCard style={styles.introCard}>
        <Text style={[styles.introText, { color: colors.textSecondary }]}>
          Nino XO is a premium 1v1 strategy game for NinoWorld subscribers. Master the classic
          Tic-Tac-Toe format with cactus characters and claim your streak!
        </Text>
      </PremiumCard>

      <Text style={[styles.sectionTitle, { color: colors.textPrimary }]}>Game Flow</Text>
      {STEPS.map((step, i) => (
        <Pressable
          key={i}
          onPress={() => setExpanded(expanded === i ? null : i)}
        >
          <PremiumCard style={styles.stepCard}>
            <View style={styles.stepHeader}>
              <View style={[styles.stepIcon, { backgroundColor: step.colorPale }]}>
                <Ionicons name={step.icon} size={20} color={step.color} />
              </View>
              <View style={styles.stepInfo}>
                <Text style={[styles.stepNum, { color: colors.textFaint }]}>Step {i + 1}</Text>
                <Text style={[styles.stepTitle, { color: colors.textPrimary }]}>{step.title}</Text>
              </View>
              <Ionicons
                name={expanded === i ? 'chevron-up' : 'chevron-down'}
                size={18}
                color={colors.textMuted}
              />
            </View>
            {expanded === i && (
              <Text style={[styles.stepDesc, { color: colors.textSecondary, borderTopColor: colors.border }]}>
                {step.desc}
              </Text>
            )}
          </PremiumCard>
        </Pressable>
      ))}

      <Text style={[styles.sectionTitle, { color: colors.textPrimary }]}>Pro Tips</Text>
      <PremiumCard style={styles.tipsCard}>
        {TIPS.map((tip, i) => (
          <View key={i} style={styles.tipRow}>
            <View style={[styles.tipBullet, { backgroundColor: Colors.green }]}>
              <Text style={styles.tipNum}>{i + 1}</Text>
            </View>
            <Text style={[styles.tipText, { color: colors.textSecondary }]}>{tip}</Text>
          </View>
        ))}
      </PremiumCard>

      <Text style={[styles.sectionTitle, { color: colors.textPrimary }]}>Winning Conditions</Text>
      <PremiumCard style={styles.winCard}>
        {['Top row', 'Middle row', 'Bottom row', 'Left column', 'Middle column', 'Right column', 'Diagonal ↘', 'Diagonal ↗'].map((line, i) => (
          <View key={i} style={styles.winRow}>
            <Ionicons name="checkmark-circle" size={16} color={Colors.green} />
            <Text style={[styles.winText, { color: colors.textSecondary }]}>{line}</Text>
          </View>
        ))}
      </PremiumCard>

      <NinoButton
        label="Let's Play!"
        onPress={() => router.replace('/home')}
        color={Colors.green}
        fullWidth
      />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  content: { paddingHorizontal: 20, gap: 14 },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 4,
  },
  backBtn: { width: 40, height: 40, alignItems: 'center', justifyContent: 'center' },
  title: { fontSize: 20, fontFamily: 'Inter_700Bold' },
  introCard: {},
  introText: {
    fontSize: 14,
    fontFamily: 'Inter_400Regular',
    lineHeight: 21,
  },
  sectionTitle: {
    fontSize: 16,
    fontFamily: 'Inter_700Bold',
    marginTop: 4,
    marginBottom: -4,
  },
  stepCard: { gap: 0 },
  stepHeader: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  stepIcon: { width: 40, height: 40, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
  stepInfo: { flex: 1 },
  stepNum: { fontSize: 11, fontFamily: 'Inter_500Medium', textTransform: 'uppercase', letterSpacing: 0.5 },
  stepTitle: { fontSize: 15, fontFamily: 'Inter_600SemiBold' },
  stepDesc: {
    fontSize: 13,
    fontFamily: 'Inter_400Regular',
    lineHeight: 19,
    marginTop: 12,
    paddingTop: 12,
    borderTopWidth: 1,
  },
  tipsCard: { gap: 12 },
  tipRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 10 },
  tipBullet: { width: 22, height: 22, borderRadius: 11, alignItems: 'center', justifyContent: 'center', marginTop: 1 },
  tipNum: { fontSize: 11, fontFamily: 'Inter_700Bold', color: Colors.white },
  tipText: { flex: 1, fontSize: 13, fontFamily: 'Inter_400Regular', lineHeight: 19 },
  winCard: { gap: 8, flexWrap: 'wrap', flexDirection: 'row' },
  winRow: { flexDirection: 'row', alignItems: 'center', gap: 6, width: '48%' },
  winText: { fontSize: 12, fontFamily: 'Inter_400Regular' },
});
