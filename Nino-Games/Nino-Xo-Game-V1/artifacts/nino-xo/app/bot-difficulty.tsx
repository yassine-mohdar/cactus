import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import React, { useMemo, useRef, useState } from 'react';
import {
  Animated,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useAuth } from '@/src/context/AuthContext';
import { useGame } from '@/src/context/GameContext';
import { BotDifficulty } from '@/src/types';
import { NinoButton } from '@/src/components/NinoButton';
import { BouncingCharacter } from '@/src/components/BouncingCharacter';
import { pickBotCharacter } from '@/src/stores/gameStore';
import { useTheme } from '@/src/context/ThemeContext';
import { ThemeColors } from '@/constants/theme';

type IoniconsName = React.ComponentProps<typeof Ionicons>['name'];

interface Tier {
  difficulty: BotDifficulty;
  label: string;
  tagline: string;
  description: string;
  icon: IoniconsName;
  color: string;
  gradient: readonly [string, string];
  bars: number;
}

const TIERS: Tier[] = [
  {
    difficulty: 'easy',
    label: 'Easy',
    tagline: 'Learning mode',
    description: 'Random moves — perfect for warming up and learning the ropes.',
    icon: 'leaf',
    color: '#4ADE80',
    gradient: ['#1A3D2B', '#0F2A1C'],
    bars: 1,
  },
  {
    difficulty: 'medium',
    label: 'Medium',
    tagline: 'Strategic rival',
    description: 'Blocks your wins and seizes every opportunity. A real test.',
    icon: 'flash',
    color: '#FF6B6B',
    gradient: ['#3D1A1A', '#2A0F0F'],
    bars: 2,
  },
  {
    difficulty: 'hard',
    label: 'Hard',
    tagline: 'Unbeatable AI',
    description: 'Perfect minimax play — it never makes a mistake. Beat it if you can.',
    icon: 'skull',
    color: '#A78BFA',
    gradient: ['#1E1830', '#120E20'],
    bars: 3,
  },
];

function DifficultyBars({ count, filled, color, colors }: { count: number; filled: boolean; color: string; colors: ThemeColors }) {
  return (
    <View style={barStyles.row}>
      {Array.from({ length: 3 }).map((_, i) => (
        <View
          key={i}
          style={[
            barStyles.bar,
            { height: 8 + i * 4 },
            i < count && filled
              ? { backgroundColor: color }
              : i < count
                ? { backgroundColor: color + '30' }
                : { backgroundColor: colors.border },
          ]}
        />
      ))}
    </View>
  );
}

interface CardProps {
  tier: Tier;
  selected: boolean;
  onPress: () => void;
  colors: ThemeColors;
}

function TierCard({ tier, selected, onPress, colors }: CardProps) {
  const scale = useRef(new Animated.Value(1)).current;

  const handlePressIn = () => {
    Animated.spring(scale, { toValue: 0.97, useNativeDriver: true, damping: 16 }).start();
  };
  const handlePressOut = () => {
    Animated.spring(scale, { toValue: 1, useNativeDriver: true, damping: 16 }).start();
  };

  return (
    <Pressable onPress={onPress} onPressIn={handlePressIn} onPressOut={handlePressOut}>
      <Animated.View
        style={[
          styles.card,
          { backgroundColor: colors.subtleBg, borderColor: colors.border },
          selected && {
            borderColor: tier.color,
            shadowColor: tier.color,
            shadowOpacity: 0.35,
            shadowRadius: 16,
            elevation: 10,
          },
          { transform: [{ scale }] },
        ]}
      >
        {selected && (
          <LinearGradient
            colors={tier.gradient}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={StyleSheet.absoluteFillObject}
          />
        )}

        <View style={styles.cardLeft}>
          <View style={[styles.iconWrap, { backgroundColor: selected ? tier.color + '22' : colors.inputBg }]}>
            <Ionicons
              name={tier.icon}
              size={22}
              color={selected ? tier.color : colors.textMuted}
            />
          </View>
          <View style={styles.cardText}>
            <Text style={[styles.cardLabel, { color: selected ? tier.color : colors.textPrimary }]}>
              {tier.label}
            </Text>
            <Text style={[styles.tagline, { color: colors.textFaint }]}>{tier.tagline}</Text>
            {selected && (
              <Text style={[styles.description, { color: colors.textSecondary }]}>{tier.description}</Text>
            )}
          </View>
        </View>

        <View style={styles.cardRight}>
          <DifficultyBars count={tier.bars} filled={selected} color={tier.color} colors={colors} />
          {selected && (
            <View style={[styles.checkDot, { backgroundColor: tier.color }]}>
              <Ionicons name="checkmark" size={12} color="#000" />
            </View>
          )}
        </View>
      </Animated.View>
    </Pressable>
  );
}

export default function BotDifficultyScreen() {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const { profile } = useAuth();
  const { startBotGame } = useGame();
  const [difficulty, setDifficulty] = useState<BotDifficulty>('medium');

  const botChar = useMemo(() => {
    const playerCharId = profile?.selectedCharacterId ?? '';
    const { char, isFallback } = pickBotCharacter(playerCharId);
    return isFallback ? { ...char, name: 'Bot Opponent' } : char;
  }, [profile?.selectedCharacterId]);

  const selectedTier = TIERS.find(t => t.difficulty === difficulty)!;

  const handleStart = () => {
    if (!profile) return;
    startBotGame(profile.selectedCharacterId, profile.name, difficulty, botChar.id);
    router.push('/match');
  };

  return (
    <View style={[styles.root, { backgroundColor: colors.background }]}>
      <View style={[styles.header, { paddingTop: insets.top + 8 }]}>
        <Pressable onPress={() => router.back()} style={styles.backBtn}>
          <Ionicons name="chevron-back" size={26} color={colors.textPrimary} />
        </Pressable>
        <Text style={[styles.headerTitle, { color: colors.textPrimary }]}>VS. Bot</Text>
        <View style={{ width: 40 }} />
      </View>

      <View style={styles.hero}>
        <LinearGradient
          colors={['rgba(74,222,128,0.08)', 'transparent']}
          style={StyleSheet.absoluteFillObject}
        />
        <View style={styles.heroGlow} />
        <BouncingCharacter character={botChar} size={120} reactionType="idle" />
        <View style={styles.heroMeta}>
          <Text style={[styles.heroName, { color: colors.textPrimary }]}>{botChar.name}</Text>
          <View style={[styles.heroPill, { backgroundColor: colors.inputBg, borderColor: colors.border }]}>
            <View style={[styles.heroDot, { backgroundColor: '#4ADE80' }]} />
            <Text style={[styles.heroPillText, { color: colors.textSecondary }]}>Ready to play</Text>
          </View>
        </View>
      </View>

      <View style={styles.body}>
        <Text style={[styles.sectionLabel, { color: colors.textMuted }]}>How challenging?</Text>

        {TIERS.map(tier => (
          <TierCard
            key={tier.difficulty}
            tier={tier}
            selected={difficulty === tier.difficulty}
            onPress={() => setDifficulty(tier.difficulty as BotDifficulty)}
            colors={colors}
          />
        ))}
      </View>

      <View style={[styles.footer, { paddingBottom: insets.bottom + 16 }]}>
        <NinoButton
          label="Start Match"
          onPress={handleStart}
          color={selectedTier.color}
          fullWidth
        />
      </View>
    </View>
  );
}

const barStyles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'flex-end', gap: 3 },
  bar: { width: 6, borderRadius: 3 },
});

const styles = StyleSheet.create({
  root: { flex: 1 },

  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingBottom: 4,
  },
  backBtn: { width: 40, height: 40, alignItems: 'center', justifyContent: 'center' },
  headerTitle: { fontSize: 17, fontFamily: 'Inter_600SemiBold' },

  hero: {
    alignItems: 'center',
    paddingVertical: 20,
    gap: 12,
    overflow: 'hidden',
  },
  heroGlow: {
    position: 'absolute',
    width: 200,
    height: 200,
    borderRadius: 100,
    backgroundColor: '#4ADE80',
    opacity: 0.04,
    top: -40,
  },
  heroMeta: { alignItems: 'center', gap: 8 },
  heroName: { fontSize: 22, fontFamily: 'Inter_700Bold' },
  heroPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 12,
    paddingVertical: 5,
    borderRadius: 20,
    borderWidth: 1,
  },
  heroDot: { width: 6, height: 6, borderRadius: 3 },
  heroPillText: { fontSize: 12, fontFamily: 'Inter_500Medium' },

  body: { flex: 1, paddingHorizontal: 20, gap: 10 },
  sectionLabel: {
    fontSize: 13,
    fontFamily: 'Inter_600SemiBold',
    letterSpacing: 0.8,
    textTransform: 'uppercase',
    marginBottom: 4,
  },

  card: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderRadius: 20,
    paddingHorizontal: 16,
    paddingVertical: 16,
    borderWidth: 1.5,
    overflow: 'hidden',
    shadowColor: 'transparent',
    shadowOpacity: 0,
    shadowRadius: 0,
    shadowOffset: { width: 0, height: 0 },
  },
  cardLeft: { flexDirection: 'row', alignItems: 'center', gap: 14, flex: 1 },
  iconWrap: {
    width: 44,
    height: 44,
    borderRadius: 13,
    alignItems: 'center',
    justifyContent: 'center',
  },
  cardText: { flex: 1, gap: 3 },
  cardLabel: {
    fontSize: 16,
    fontFamily: 'Inter_700Bold',
  },
  tagline: { fontSize: 12, fontFamily: 'Inter_400Regular' },
  description: {
    fontSize: 12,
    fontFamily: 'Inter_400Regular',
    lineHeight: 17,
    marginTop: 4,
  },
  cardRight: { alignItems: 'center', gap: 8, paddingLeft: 12 },
  checkDot: {
    width: 22,
    height: 22,
    borderRadius: 11,
    alignItems: 'center',
    justifyContent: 'center',
  },

  footer: { paddingHorizontal: 20 },
});
