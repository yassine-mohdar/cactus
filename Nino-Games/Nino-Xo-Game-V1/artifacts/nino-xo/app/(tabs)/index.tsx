import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import React, { useEffect } from 'react';
import { Image, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import Animated, {
  useAnimatedStyle, useSharedValue,
  withDelay, withSpring, withTiming,
} from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useAuth } from '@/src/context/AuthContext';
import { useEntitlement } from '@/src/hooks/useEntitlement';
import { getCharacter, CHARACTERS } from '@/src/data/characters';
import { useTheme } from '@/src/context/ThemeContext';

const S = { xs: 8, sm: 12, md: 16, lg: 24, xl: 32 } as const;

type IoniconsName = React.ComponentProps<typeof Ionicons>['name'];

function Tap({ onPress, children, style }: { onPress: () => void; children: React.ReactNode; style?: object }) {
  const scale = useSharedValue(1);
  const anim = useAnimatedStyle(() => ({ transform: [{ scale: scale.value }] }));
  return (
    <Pressable
      onPressIn={() => { scale.value = withSpring(0.95, { damping: 14 }); }}
      onPressOut={() => { scale.value = withSpring(1, { damping: 14 }); }}
      onPress={onPress} style={style}
    >
      <Animated.View style={anim}>{children}</Animated.View>
    </Pressable>
  );
}

function ModeCard({ icon, label, desc, color, onPress, colors }: {
  icon: IoniconsName; label: string; desc: string; color: string; onPress: () => void;
  colors: ReturnType<typeof useTheme>['colors'];
}) {
  const scale = useSharedValue(1);
  const anim = useAnimatedStyle(() => ({ transform: [{ scale: scale.value }] }));
  return (
    <Pressable
      onPressIn={() => { scale.value = withSpring(0.94, { damping: 14 }); }}
      onPressOut={() => { scale.value = withSpring(1, { damping: 14 }); }}
      onPress={onPress} style={{ flex: 1 }}
    >
      <Animated.View style={[styles.modeCard, { backgroundColor: colors.card, borderColor: colors.border }, anim]}>
        <View style={[styles.modeIcon, { backgroundColor: color + '22' }]}>
          <Ionicons name={icon} size={22} color={color} />
        </View>
        <Text style={[styles.modeLabel, { color }]}>{label}</Text>
        <Text style={[styles.modeDesc, { color: colors.textFaint }]}>{desc}</Text>
        <View style={[styles.modeLine, { backgroundColor: color }]} />
      </Animated.View>
    </Pressable>
  );
}

export default function PlayTab() {
  const insets = useSafeAreaInsets();
  const { profile } = useAuth();
  const { hasAccess } = useEntitlement();
  const { colors } = useTheme();
  const character = profile ? getCharacter(profile.selectedCharacterId) : getCharacter('nino');

  const heroOpacity = useSharedValue(0);
  const heroY = useSharedValue(18);
  const sectionOpacity = useSharedValue(0);
  const sectionY = useSharedValue(20);

  useEffect(() => {
    heroOpacity.value = withTiming(1, { duration: 380 });
    heroY.value = withSpring(0, { damping: 16 });
    sectionOpacity.value = withDelay(180, withTiming(1, { duration: 340 }));
    sectionY.value = withDelay(180, withSpring(0, { damping: 16 }));
  }, []);

  const heroStyle = useAnimatedStyle(() => ({ opacity: heroOpacity.value, transform: [{ translateY: heroY.value }] }));
  const secStyle = useAnimatedStyle(() => ({ opacity: sectionOpacity.value, transform: [{ translateY: sectionY.value }] }));

  useEffect(() => {
    if (!profile) router.replace('/login');
  }, [profile]);

  if (!profile) return null;

  const guardedNav = (path: string) => {
    if (!hasAccess) { router.push('/locked'); return; }
    router.push(path as Parameters<typeof router.push>[0]);
  };

  const handleQuickMatch = () => {
    if (!hasAccess) { router.push('/locked'); return; }
    if (profile.selectedCharacterId) {
      router.push('/quick-match');
    } else {
      router.push('/characters?mode=quick');
    }
  };

  const handleBotMode = () => {
    if (!hasAccess) { router.push('/locked'); return; }
    if (profile.selectedCharacterId) {
      router.push('/bot-difficulty');
    } else {
      router.push('/characters?mode=bot');
    }
  };

  const winRate = profile.stats.totalMatches > 0
    ? Math.round((profile.stats.totalWins / profile.stats.totalMatches) * 100)
    : 0;

  return (
    <ScrollView
      style={[styles.container, { backgroundColor: colors.background }]}
      contentContainerStyle={[styles.content, { paddingTop: insets.top + 14, paddingBottom: S.xl }]}
      showsVerticalScrollIndicator={false}
    >
      <View style={[styles.blob, { top: -60, right: -80, width: 260, height: 260, backgroundColor: character.accentColor, opacity: 0.07 }]} />
      <View style={[styles.blob, { top: 300, left: -100, width: 260, height: 260, backgroundColor: Colors.blue, opacity: 0.05 }]} />

      {/* ── HEADER ── */}
      <View style={styles.header}>
        <View>
          <Text style={[styles.greeting, { color: colors.textPrimary }]}>Hey, {profile.name} 👋</Text>
          <View style={styles.streakRow}>
            <Ionicons name="flame" size={13} color={Colors.yellow} />
            <Text style={[styles.streakText, { color: colors.textMuted }]}>{profile.stats.streak} streak</Text>
            <View style={[styles.dot, { backgroundColor: colors.textVeryFaint }]} />
            <Ionicons name="trophy" size={12} color={Colors.yellow} />
            <Text style={[styles.streakText, { color: colors.textMuted }]}>{profile.stats.totalWins} wins</Text>
          </View>
        </View>
        <View style={styles.headerRight}>
          <Pressable onPress={() => router.push('/notifications')} style={styles.iconBtn}>
            <Ionicons name="notifications-outline" size={22} color={colors.textMuted} />
          </Pressable>
          <Pressable onPress={() => router.push('/characters')} style={[styles.avatarBtn, { backgroundColor: colors.inputBg, borderColor: colors.borderMid }]}>
            <Image source={character.image} style={styles.avatarImg} resizeMode="contain" />
          </Pressable>
        </View>
      </View>

      {/* ── FIGHTER HERO CARD ── */}
      <Animated.View style={heroStyle}>
        <View style={[styles.heroCard, { backgroundColor: colors.card, borderColor: character.accentColor + '28' }]}>
          <View style={[styles.heroBlob, { top: -50, right: -40, backgroundColor: character.accentColor, width: 200, height: 200, opacity: 0.14 }]} />
          <View style={[styles.heroBlob, { bottom: -30, left: -20, backgroundColor: character.accentColor, width: 120, height: 120, opacity: 0.07 }]} />

          <View style={styles.heroBody}>
            <View style={styles.heroLeft}>
              <View style={styles.fighterBadge}>
                <View style={[styles.fighterDot, { backgroundColor: character.accentColor }]} />
                <Text style={[styles.fighterBadgeText, { color: character.accentColor }]}>YOUR FIGHTER</Text>
              </View>
              <Text style={[styles.fighterName, { color: colors.textPrimary }]}>{character.name}</Text>
              <Text style={[styles.fighterPersonality, { color: character.accentColor }]}>{character.personality}</Text>

              <Tap onPress={handleQuickMatch} style={styles.playBtnWrap}>
                <View style={[styles.playBtn, { shadowColor: Colors.yellow }]}>
                  <Ionicons name="flash" size={16} color={Colors.textDark} />
                  <Text style={styles.playBtnText}>PLAY NOW</Text>
                </View>
              </Tap>
            </View>

            <Pressable onPress={() => router.push('/characters')} style={styles.heroRight}>
              <View style={[styles.heroRing, { borderColor: character.accentColor + '50' }]} />
              <View style={[styles.heroFill, { backgroundColor: character.accentColor }]} />
              <Image source={character.image} style={styles.heroChar} resizeMode="contain" />
            </Pressable>
          </View>

          <Pressable onPress={() => router.push('/characters')} style={[styles.heroFooter, { backgroundColor: colors.subtleBg, borderTopColor: colors.border }]}>
            <View style={styles.statRow}>
              <View style={styles.stat}>
                <Text style={[styles.statNum, { color: colors.textPrimary }]}>{profile.stats.totalWins}</Text>
                <Text style={[styles.statLabel, { color: colors.textFaint }]}>Wins</Text>
              </View>
              <View style={[styles.statDivider, { backgroundColor: colors.border }]} />
              <View style={styles.stat}>
                <Text style={[styles.statNum, { color: colors.textPrimary }]}>{winRate}%</Text>
                <Text style={[styles.statLabel, { color: colors.textFaint }]}>Win Rate</Text>
              </View>
              <View style={[styles.statDivider, { backgroundColor: colors.border }]} />
              <View style={styles.stat}>
                <Text style={[styles.statNum, { color: colors.textPrimary }]}>{profile.stats.streak}</Text>
                <Text style={[styles.statLabel, { color: colors.textFaint }]}>Streak</Text>
              </View>
            </View>
            <View style={styles.changeRow}>
              <Text style={[styles.changeText, { color: colors.textFaint }]}>Change Fighter</Text>
              <Ionicons name="chevron-forward" size={12} color={colors.textFaint} />
            </View>
          </Pressable>
        </View>
      </Animated.View>

      {/* ── BATTLE MODES ── */}
      <Animated.View style={[styles.sections, secStyle]}>
        {/* Quick Match */}
        <Tap onPress={handleQuickMatch}>
          <View style={[styles.quickCard, { backgroundColor: colors.card, borderColor: Colors.yellow + '35' }]}>
            <View style={styles.quickLeft}>
              <View style={styles.quickIcon}>
                <Ionicons name="flash" size={28} color={Colors.yellow} />
              </View>
              <View style={styles.quickMeta}>
                <View style={styles.quickTitleRow}>
                  <Text style={[styles.quickTitle, { color: colors.textPrimary }]}>Quick Match</Text>
                  <View style={styles.hotChip}>
                    <Text style={styles.hotText}>🔥 HOT</Text>
                  </View>
                </View>
                <Text style={[styles.quickDesc, { color: colors.textMuted }]}>Jump into a battle instantly</Text>
              </View>
            </View>
            <View style={styles.quickArrow}>
              <Ionicons name="arrow-forward" size={18} color={Colors.yellow} />
            </View>
          </View>
        </Tap>

        {/* Friend + Bot */}
        <View style={styles.modesRow}>
          <ModeCard icon="people" label="Friend" desc="Private room" color={Colors.blue} onPress={() => guardedNav('/friend-room')} colors={colors} />
          <ModeCard icon="hardware-chip" label="vs Bot" desc="Practice AI" color={Colors.green} onPress={handleBotMode} colors={colors} />
        </View>

        {/* ── CHALLENGES ── */}
        <View style={styles.sectionHeader}>
          <Text style={[styles.sectionTitle, { color: colors.textPrimary }]}>Challenges</Text>
          <Pressable onPress={() => router.push('/challenges')}>
            <Text style={[styles.seeAll, { color: colors.textMuted }]}>See all →</Text>
          </Pressable>
        </View>
        <Tap onPress={() => router.push('/challenges')}>
          <View style={[styles.challengeCard, { backgroundColor: colors.card, borderColor: Colors.purple + '35' }]}>
            <View style={styles.challengeLeft}>
              <View style={styles.challengeIconWrap}>
                <Ionicons name="trophy" size={26} color={Colors.purple} />
              </View>
              <View style={styles.challengeMeta}>
                <Text style={[styles.challengeTitle, { color: colors.textPrimary }]}>Tournaments & Prizes</Text>
                <Text style={[styles.challengeDesc, { color: colors.textMuted }]}>Join challenges, climb leaderboards, win tokens</Text>
              </View>
            </View>
            <View style={styles.challengeArrow}>
              <Ionicons name="arrow-forward" size={18} color={Colors.purple} />
            </View>
          </View>
        </Tap>

        {/* ── CHARACTER ROSTER ── */}
        <View style={styles.sectionHeader}>
          <Text style={[styles.sectionTitle, { color: colors.textPrimary }]}>Character Roster</Text>
          <Pressable onPress={() => router.push('/characters')}>
            <Text style={[styles.seeAll, { color: colors.textMuted }]}>See all →</Text>
          </Pressable>
        </View>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.rosterScroll} contentContainerStyle={styles.rosterContent}>
          {CHARACTERS.map((c) => {
            const isSelected = c.id === profile.selectedCharacterId;
            return (
              <Pressable
                key={c.id}
                onPress={() => router.push('/characters')}
                style={[styles.rosterCard, { backgroundColor: colors.card, borderColor: isSelected ? c.accentColor + '80' : colors.border }]}
              >
                <View style={[styles.rosterGlow, { backgroundColor: c.accentColor }]} />
                <Image source={c.image} style={styles.rosterImg} resizeMode="contain" />
                <Text style={[styles.rosterName, { color: c.accentColor }]}>{c.name}</Text>
                <Text style={[styles.rosterPersonality, { color: colors.textFaint }]}>{c.personality.split(' ')[0]}</Text>
                {isSelected && (
                  <View style={[styles.rosterActiveBadge, { backgroundColor: c.accentColor }]}>
                    <Text style={styles.rosterActiveBadgeText}>ACTIVE</Text>
                  </View>
                )}
              </Pressable>
            );
          })}
        </ScrollView>
      </Animated.View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  content: { paddingHorizontal: 18, gap: 12 },
  blob: { position: 'absolute', borderRadius: 999 },

  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 2 },
  greeting: { fontSize: 22, fontFamily: 'Inter_700Bold' },
  streakRow: { flexDirection: 'row', alignItems: 'center', gap: 5, marginTop: 3 },
  streakText: { fontSize: 12, fontFamily: 'Inter_600SemiBold' },
  dot: { width: 3, height: 3, borderRadius: 1.5 },
  headerRight: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  iconBtn: { padding: 6 },
  avatarBtn: {
    width: 42, height: 42, borderRadius: 21,
    borderWidth: 1.5,
    overflow: 'hidden', alignItems: 'center', justifyContent: 'center',
  },
  avatarImg: { width: 38, height: 38 },

  heroCard: {
    borderRadius: 28,
    overflow: 'hidden', borderWidth: 1,
  },
  heroBlob: { position: 'absolute', borderRadius: 999 },
  heroBody: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', padding: 22, paddingBottom: 18 },
  heroLeft: { flex: 1, gap: 4 },
  fighterBadge: { flexDirection: 'row', alignItems: 'center', gap: 6, marginBottom: 2 },
  fighterDot: { width: 6, height: 6, borderRadius: 3 },
  fighterBadgeText: { fontSize: 10, fontFamily: 'Inter_700Bold', letterSpacing: 2 },
  fighterName: { fontSize: 28, fontFamily: 'Inter_700Bold', lineHeight: 32 },
  fighterPersonality: { fontSize: 12, fontFamily: 'Inter_600SemiBold', letterSpacing: 0.5 },
  playBtnWrap: { alignSelf: 'flex-start', marginTop: 16 },
  playBtn: {
    flexDirection: 'row', alignItems: 'center', gap: 8,
    backgroundColor: Colors.yellow,
    paddingHorizontal: 20, paddingVertical: 12, borderRadius: 50,
    shadowOpacity: 0.45, shadowOffset: { width: 0, height: 6 }, shadowRadius: 14, elevation: 8,
  },
  playBtnText: { fontSize: 14, fontFamily: 'Inter_700Bold', color: Colors.textDark, letterSpacing: 1.2 },
  heroRight: { width: 140, height: 140, alignItems: 'center', justifyContent: 'center', marginLeft: 8 },
  heroRing: { position: 'absolute', width: 136, height: 136, borderRadius: 68, borderWidth: 1.5 },
  heroFill: { position: 'absolute', width: 110, height: 110, borderRadius: 55, opacity: 0.15 },
  heroChar: { width: '100%', height: '100%' },

  heroFooter: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: 22, paddingVertical: 14,
    borderTopWidth: 1,
  },
  statRow: { flexDirection: 'row', alignItems: 'center' },
  stat: { alignItems: 'center', paddingHorizontal: 14 },
  statNum: { fontSize: 18, fontFamily: 'Inter_700Bold' },
  statLabel: { fontSize: 10, fontFamily: 'Inter_400Regular', marginTop: 1 },
  statDivider: { width: 1, height: 28 },
  changeRow: { flexDirection: 'row', alignItems: 'center', gap: 3 },
  changeText: { fontSize: 11, fontFamily: 'Inter_600SemiBold' },

  quickCard: {
    borderRadius: 20, padding: 18,
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    borderWidth: 1,
    shadowColor: Colors.yellow, shadowOpacity: 0.18,
    shadowOffset: { width: 0, height: 6 }, shadowRadius: 16, elevation: 6,
  },
  quickLeft: { flexDirection: 'row', alignItems: 'center', gap: 14, flex: 1 },
  quickIcon: {
    width: 54, height: 54, borderRadius: 18,
    backgroundColor: Colors.yellow + '18', borderWidth: 1, borderColor: Colors.yellow + '30',
    alignItems: 'center', justifyContent: 'center',
  },
  quickMeta: { flex: 1 },
  quickTitleRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  quickTitle: { fontSize: 18, fontFamily: 'Inter_700Bold' },
  hotChip: {
    backgroundColor: Colors.yellow + '22', paddingHorizontal: 8, paddingVertical: 3,
    borderRadius: 8, borderWidth: 1, borderColor: Colors.yellow + '40',
  },
  hotText: { fontSize: 9, fontFamily: 'Inter_700Bold', color: Colors.yellow, letterSpacing: 0.8 },
  quickDesc: { fontSize: 12, fontFamily: 'Inter_400Regular', marginTop: 3 },
  quickArrow: {
    width: 42, height: 42, borderRadius: 14,
    backgroundColor: Colors.yellow + '18', alignItems: 'center', justifyContent: 'center',
    borderWidth: 1, borderColor: Colors.yellow + '25',
  },

  modesRow: { flexDirection: 'row', gap: 10 },
  modeCard: {
    flex: 1, borderRadius: 20, padding: 18,
    borderWidth: 1,
  },
  modeIcon: { width: 46, height: 46, borderRadius: 14, alignItems: 'center', justifyContent: 'center', marginBottom: 12 },
  modeLabel: { fontSize: 16, fontFamily: 'Inter_700Bold', marginBottom: 3 },
  modeDesc: { fontSize: 12, fontFamily: 'Inter_400Regular' },
  modeLine: { height: 2, borderRadius: 1, marginTop: 14, width: 28, opacity: 0.6 },

  sections: { gap: S.sm },
  sectionHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: S.md, marginBottom: S.xs },
  sectionTitle: { fontSize: 16, fontFamily: 'Inter_700Bold' },
  seeAll: { fontSize: 13, fontFamily: 'Inter_600SemiBold' },

  challengeCard: {
    borderRadius: 20, padding: 18,
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    borderWidth: 1,
    shadowColor: Colors.purple, shadowOpacity: 0.12,
    shadowOffset: { width: 0, height: 4 }, shadowRadius: 12, elevation: 4,
  },
  challengeLeft: { flexDirection: 'row', alignItems: 'center', gap: 14, flex: 1 },
  challengeIconWrap: {
    width: 54, height: 54, borderRadius: 18,
    backgroundColor: Colors.purple + '18', borderWidth: 1, borderColor: Colors.purple + '30',
    alignItems: 'center', justifyContent: 'center',
  },
  challengeMeta: { flex: 1, gap: 3 },
  challengeTitle: { fontSize: 16, fontFamily: 'Inter_700Bold' },
  challengeDesc: { fontSize: 12, fontFamily: 'Inter_400Regular' },
  challengeArrow: {
    width: 40, height: 40, borderRadius: 12,
    backgroundColor: Colors.purple + '18', alignItems: 'center', justifyContent: 'center',
    borderWidth: 1, borderColor: Colors.purple + '25',
  },

  rosterScroll: { marginHorizontal: -18 },
  rosterContent: { paddingHorizontal: 18, gap: 10, paddingBottom: S.md },
  rosterCard: {
    width: 110, borderRadius: 20, padding: 14,
    alignItems: 'center', gap: 6,
    borderWidth: 1,
    overflow: 'hidden',
  },
  rosterGlow: { position: 'absolute', top: -20, width: 80, height: 80, borderRadius: 40, opacity: 0.12 },
  rosterImg: { width: 72, height: 72 },
  rosterName: { fontSize: 13, fontFamily: 'Inter_700Bold' },
  rosterPersonality: { fontSize: 10, fontFamily: 'Inter_400Regular' },
  rosterActiveBadge: {
    paddingHorizontal: 8, paddingVertical: 3, borderRadius: 6, marginTop: 2,
  },
  rosterActiveBadgeText: { fontSize: 9, fontFamily: 'Inter_700Bold', color: Colors.textDark, letterSpacing: 0.8 },
});
