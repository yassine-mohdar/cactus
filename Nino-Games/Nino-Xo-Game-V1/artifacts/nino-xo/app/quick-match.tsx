import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import React, { useEffect, useRef, useState } from 'react';
import {
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import Animated, {
  useAnimatedStyle,
  useSharedValue,
  withRepeat,
  withSequence,
  withTiming,
} from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useAuth } from '@/src/context/AuthContext';
import { useGame } from '@/src/context/GameContext';
import { getCharacter } from '@/src/data/characters';
import { realtimeService, type PublicProfile, type MatchmakingResult } from '@/src/services/realtimeService';
import { BouncingCharacter } from '@/src/components/BouncingCharacter';
import { NinoButton } from '@/src/components/NinoButton';
import { useTheme } from '@/src/context/ThemeContext';

const SEARCHING_TEXTS = [
  'Finding a friend to play with...',
  'Nino is looking for an opponent...',
  'Scanning the cactus universe...',
  'Almost there, keep your spines up!',
  'Connecting to NinoWorld servers...',
];

const MATCHMAKING_TIMEOUT_MS = 30_000;

function buildPublicProfile(profile: NonNullable<ReturnType<typeof useAuth>['profile']>): PublicProfile {
  return {
    profileId: profile.id,
    name: profile.name,
    characterId: profile.selectedCharacterId,
    hasSubscription: profile.hasSubscription,
    earnedBadgeIds: profile.earnedBadgeIds ?? [],
    stats: {
      totalMatches: profile.stats.totalMatches,
      totalWins: profile.stats.totalWins,
      totalLosses: profile.stats.totalLosses ?? 0,
      totalDraws: profile.stats.totalDraws ?? 0,
      streak: profile.stats.streak,
      bestStreak: profile.stats.bestStreak,
    },
  };
}

export default function QuickMatchScreen() {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const { profile } = useAuth();
  const { startQuickGame, startOnlineGame } = useGame();
  const [textIdx, setTextIdx] = useState(0);
  const [status, setStatus] = useState<'searching' | 'found' | 'error' | 'no_match'>('searching');
  const [opponent, setOpponent] = useState<MatchmakingResult | null>(null);
  const cancelRef = useRef<(() => void) | null>(null);
  const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const character = profile ? getCharacter(profile.selectedCharacterId) : getCharacter('nino');
  const dotScale1 = useSharedValue(1);
  const dotScale2 = useSharedValue(1);
  const dotScale3 = useSharedValue(1);

  useEffect(() => {
    dotScale1.value = withRepeat(
      withSequence(withTiming(1.5, { duration: 400 }), withTiming(1, { duration: 400 })),
      -1,
      false,
    );
    setTimeout(() => {
      dotScale2.value = withRepeat(
        withSequence(withTiming(1.5, { duration: 400 }), withTiming(1, { duration: 400 })),
        -1,
        false,
      );
    }, 133);
    setTimeout(() => {
      dotScale3.value = withRepeat(
        withSequence(withTiming(1.5, { duration: 400 }), withTiming(1, { duration: 400 })),
        -1,
        false,
      );
    }, 266);

    const textTimer = setInterval(() => setTextIdx((i) => (i + 1) % SEARCHING_TEXTS.length), 2200);

    const cleanupTimers: ReturnType<typeof setTimeout>[] = [];

    if (profile) {
      const pub = buildPublicProfile(profile);

      function startOnlineMatchmaking() {
        if (!profile) return;
        cancelRef.current = realtimeService.startMatchmaking(
          profile.selectedCharacterId,
          (s, result) => {
            if (s === 'found' && result) {
              if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
                timeoutRef.current = null;
              }
              setStatus('found');
              setOpponent(result);
              if (result.opponentSocketId) {
                startOnlineGame(
                  profile.selectedCharacterId,
                  profile.name,
                  result.yourSymbol,
                  result.opponentCharacterId,
                  result.opponentName,
                  result.opponentSocketId,
                  result.roomId,
                  undefined,
                  result.opponentProfileId,
                );
              } else {
                startQuickGame(
                  profile.selectedCharacterId,
                  profile.name,
                  result.opponentName,
                  result.opponentCharacterId,
                );
              }
              setTimeout(() => router.replace('/match'), 1200);
            } else if (s === 'error') {
              if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
                timeoutRef.current = null;
              }
              setStatus('error');
            }
          },
        );

        timeoutRef.current = setTimeout(() => {
          cancelRef.current?.();
          cancelRef.current = null;
          setStatus('no_match');
        }, MATCHMAKING_TIMEOUT_MS);
      }

      if (realtimeService.isConnected) {
        startOnlineMatchmaking();
      } else {
        realtimeService.connect(pub);
        const connectTimeout = setTimeout(() => {
          if (realtimeService.isConnected) {
            startOnlineMatchmaking();
          } else {
            setStatus('error');
          }
        }, 5000);
        cleanupTimers.push(connectTimeout);
      }
    }

    return () => {
      clearInterval(textTimer);
      cleanupTimers.forEach(clearTimeout);
      if (timeoutRef.current) clearTimeout(timeoutRef.current);
      cancelRef.current?.();
    };
  }, []);

  const dot1Style = useAnimatedStyle(() => ({ transform: [{ scale: dotScale1.value }] }));
  const dot2Style = useAnimatedStyle(() => ({ transform: [{ scale: dotScale2.value }] }));
  const dot3Style = useAnimatedStyle(() => ({ transform: [{ scale: dotScale3.value }] }));

  const handleCancel = () => {
    if (timeoutRef.current) clearTimeout(timeoutRef.current);
    cancelRef.current?.();
    router.replace('/home');
  };

  const handleRetry = () => {
    setStatus('searching');
    setOpponent(null);
    if (!profile) return;
    const pub = buildPublicProfile(profile);
    function doStartMatchmaking() {
      if (!profile) return;
      cancelRef.current = realtimeService.startMatchmaking(
        profile.selectedCharacterId,
        (s, result) => {
          if (s === 'found' && result) {
            if (timeoutRef.current) { clearTimeout(timeoutRef.current); timeoutRef.current = null; }
            setStatus('found');
            setOpponent(result);
            if (result.opponentSocketId) {
              startOnlineGame(profile.selectedCharacterId, profile.name, result.yourSymbol, result.opponentCharacterId, result.opponentName, result.opponentSocketId, result.roomId, undefined, result.opponentProfileId);
            } else {
              startQuickGame(profile.selectedCharacterId, profile.name, result.opponentName, result.opponentCharacterId);
            }
            setTimeout(() => router.replace('/match'), 1200);
          } else if (s === 'error') {
            if (timeoutRef.current) { clearTimeout(timeoutRef.current); timeoutRef.current = null; }
            setStatus('error');
          }
        },
      );
      timeoutRef.current = setTimeout(() => {
        cancelRef.current?.(); cancelRef.current = null; setStatus('no_match');
      }, MATCHMAKING_TIMEOUT_MS);
    }
    if (realtimeService.isConnected) {
      doStartMatchmaking();
    } else {
      realtimeService.connect(pub);
      const t = setTimeout(() => {
        if (realtimeService.isConnected) doStartMatchmaking();
        else setStatus('error');
      }, 5000);
      timeoutRef.current = t;
    }
  };

  const handleBotFallback = () => {
    if (!profile) return;
    startQuickGame(profile.selectedCharacterId, profile.name);
    router.replace('/match');
  };

  return (
    <View
      style={[styles.container, { backgroundColor: colors.background, paddingTop: insets.top, paddingBottom: insets.bottom + 24 }]}
    >
      <Pressable onPress={handleCancel} style={styles.cancelBtn}>
        <Ionicons name="close" size={24} color={colors.textMuted} />
      </Pressable>

      <View style={styles.content}>
        <BouncingCharacter
          character={character}
          size={160}
          reactionType={status === 'found' ? 'found' : 'search'}
        />

        {status === 'found' && opponent ? (
          <>
            <Text style={[styles.foundTitle, { color: colors.textPrimary }]}>Match Found!</Text>
            <Text style={[styles.foundSub, { color: character.accentColor }]}>
              vs {opponent.opponentName}
            </Text>
            <Text style={[styles.charReaction, { color: character.accentColor }]}>
              {character.reactions.matchFound}
            </Text>
          </>
        ) : status === 'no_match' ? (
          <>
            <Text style={[styles.errorTitle]}>No players found</Text>
            <Text style={[styles.charReaction, { color: colors.textMuted }]}>Nobody joined within 30 seconds.</Text>
            <NinoButton
              label="Search Again"
              onPress={handleRetry}
              color={character.accentColor}
            />
            <NinoButton
              label="Play vs Bot"
              onPress={handleBotFallback}
              color={Colors.blue}
            />
          </>
        ) : status === 'error' ? (
          <>
            <Text style={styles.errorTitle}>Connection issue</Text>
            <NinoButton
              label="Try Again"
              onPress={() => router.replace('/home')}
              color={Colors.coral}
            />
          </>
        ) : (
          <>
            <View style={styles.dotsRow}>
              <Animated.View
                style={[styles.dot, { backgroundColor: character.accentColor }, dot1Style]}
              />
              <Animated.View
                style={[styles.dot, { backgroundColor: character.accentColor }, dot2Style]}
              />
              <Animated.View
                style={[styles.dot, { backgroundColor: character.accentColor }, dot3Style]}
              />
            </View>
            <Text style={[styles.searchText, { color: colors.textPrimary }]}>{SEARCHING_TEXTS[textIdx]}</Text>
            <Text style={[styles.charReaction, { color: colors.textMuted }]}>"{character.reactions.search}"</Text>
          </>
        )}
      </View>

      {(status === 'searching' || status === 'no_match') && (
        <NinoButton label={status === 'no_match' ? 'Back to Home' : 'Cancel'} onPress={handleCancel} variant="ghost" />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 32,
  },
  cancelBtn: {
    alignSelf: 'flex-end',
    width: 44,
    height: 44,
    alignItems: 'center',
    justifyContent: 'center',
  },
  content: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 20 },
  dotsRow: { flexDirection: 'row', gap: 10, alignItems: 'center' },
  dot: { width: 10, height: 10, borderRadius: 5 },
  searchText: {
    fontSize: 18,
    fontFamily: 'Inter_600SemiBold',
    textAlign: 'center',
    lineHeight: 26,
  },
  charReaction: {
    fontSize: 15,
    fontFamily: 'Inter_400Regular',
    textAlign: 'center',
    fontStyle: 'italic',
  },
  foundTitle: { fontSize: 30, fontFamily: 'Inter_700Bold' },
  foundSub: { fontSize: 18, fontFamily: 'Inter_600SemiBold', textAlign: 'center' },
  errorTitle: { fontSize: 22, fontFamily: 'Inter_700Bold', color: Colors.coral },
});
