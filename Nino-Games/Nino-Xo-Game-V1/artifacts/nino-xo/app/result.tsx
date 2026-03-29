import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import React, { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import Animated, {
  useAnimatedStyle,
  useSharedValue,
  withDelay,
  withRepeat,
  withSequence,
  withSpring,
  withTiming,
  Easing,
} from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useAuth } from '@/src/context/AuthContext';
import { useGame } from '@/src/context/GameContext';
import { getCharacter } from '@/src/data/characters';
import { getBadge } from '@/src/data/badges';
import { BadgeUnlockModal } from '@/src/components/BadgeUnlockModal';
import { BouncingCharacter } from '@/src/components/BouncingCharacter';
import { NinoButton } from '@/src/components/NinoButton';
import { realtimeService } from '@/src/services/realtimeService';
import { awardWinTokens } from '@/src/services/tokenService';
import { recordChallengeMatch, startChallengeMatch } from '@/src/services/challengeService';
import { useGameStore } from '@/src/stores/gameStore';
import { UserProfile } from '@/src/types';
import { useTheme } from '@/src/context/ThemeContext';

type Insets = { top: number; bottom: number };
type Character = ReturnType<typeof getCharacter>;

function ConfettiDot({ x, y, color, delay, size }: {
  x: number; y: number; color: string; delay: number; size: number;
}) {
  const opacity = useSharedValue(0);
  const scale = useSharedValue(0);
  const translateY = useSharedValue(0);

  useEffect(() => {
    opacity.value = withDelay(delay, withSequence(
      withTiming(1, { duration: 200 }),
      withDelay(500, withTiming(0, { duration: 300 }))
    ));
    scale.value = withDelay(delay, withSequence(
      withSpring(1, { damping: 7 }),
      withDelay(500, withTiming(0, { duration: 280 }))
    ));
    translateY.value = withDelay(delay, withSequence(
      withTiming(-22, { duration: 450, easing: Easing.out(Easing.quad) }),
      withTiming(-32, { duration: 350 })
    ));
  }, []);

  const style = useAnimatedStyle(() => ({
    opacity: opacity.value,
    transform: [{ scale: scale.value }, { translateY: translateY.value }],
  }));

  return (
    <Animated.View style={[{
      position: 'absolute', left: x, top: y,
      width: size, height: size, borderRadius: size / 2, backgroundColor: color,
    }, style]} />
  );
}

function WinConfetti() {
  const dots = [
    { x: 18, y: 100, color: Colors.yellow, delay: 0, size: 14 },
    { x: 55, y: 55, color: Colors.green, delay: 120, size: 10 },
    { x: 115, y: 75, color: Colors.coral, delay: 60, size: 13 },
    { x: 195, y: 35, color: Colors.purple, delay: 200, size: 8 },
    { x: 255, y: 80, color: Colors.yellow, delay: 40, size: 16 },
    { x: 308, y: 45, color: Colors.green, delay: 160, size: 11 },
    { x: 28, y: 175, color: Colors.coral, delay: 240, size: 9 },
    { x: 335, y: 140, color: Colors.yellow, delay: 180, size: 12 },
    { x: 158, y: 22, color: Colors.blue, delay: 90, size: 10 },
    { x: 88, y: 145, color: Colors.purple, delay: 290, size: 11 },
    { x: 278, y: 160, color: Colors.green, delay: 220, size: 7 },
    { x: 340, y: 55, color: Colors.coral, delay: 70, size: 9 },
  ];
  return (
    <View style={StyleSheet.absoluteFill} pointerEvents="none">
      {dots.map((d, i) => <ConfettiDot key={i} {...d} />)}
    </View>
  );
}

function AnimTitle({ label, color }: { label: string; color: string }) {
  const scale = useSharedValue(0.4);
  const opacity = useSharedValue(0);
  useEffect(() => {
    scale.value = withSpring(1, { damping: 8, stiffness: 120 });
    opacity.value = withTiming(1, { duration: 200 });
  }, []);
  const s = useAnimatedStyle(() => ({ transform: [{ scale: scale.value }], opacity: opacity.value }));
  return (
    <Animated.View style={[styles.titleWrap, s]}>
      <Text style={[styles.titleText, { color }]}>{label}</Text>
    </Animated.View>
  );
}

function SlideUp({ delay, children }: { delay: number; children: React.ReactNode }) {
  const y = useSharedValue(28);
  const op = useSharedValue(0);
  useEffect(() => {
    y.value = withDelay(delay, withSpring(0, { damping: 18, stiffness: 160 }));
    op.value = withDelay(delay, withTiming(1, { duration: 280 }));
  }, []);
  const s = useAnimatedStyle(() => ({ transform: [{ translateY: y.value }], opacity: op.value }));
  return <Animated.View style={s}>{children}</Animated.View>;
}

function RematchBtn({ label, color, onPress }: { label: string; color: string; onPress: () => void }) {
  const scale = useSharedValue(1);
  const glow = useSharedValue(0.5);

  useEffect(() => {
    glow.value = withRepeat(
      withSequence(
        withTiming(1, { duration: 700, easing: Easing.inOut(Easing.sin) }),
        withTiming(0.5, { duration: 700, easing: Easing.inOut(Easing.sin) })
      ), -1, true
    );
  }, []);

  const glowStyle = useAnimatedStyle(() => ({
    shadowOpacity: glow.value * 0.45,
  }));
  const btnStyle = useAnimatedStyle(() => ({
    transform: [{ scale: scale.value }],
  }));

  return (
    <Pressable
      onPressIn={() => { scale.value = withSpring(0.96, { damping: 14 }); }}
      onPressOut={() => { scale.value = withSpring(1, { damping: 14 }); }}
      onPress={onPress}
    >
      <Animated.View style={[styles.rematchBtn, { backgroundColor: color, shadowColor: color }, glowStyle, btnStyle]}>
        <Text style={styles.rematchBtnLabel}>{label}</Text>
        <Ionicons name="arrow-forward" size={18} color={Colors.textDark} style={{ opacity: 0.7 }} />
      </Animated.View>
    </Pressable>
  );
}

type RematchState =
  | 'idle'
  | 'inviting'
  | 'invited'
  | 'accepted'
  | 'declined'
  | 'expired';

export default function ResultScreen() {
  const insets = useSafeAreaInsets();
  const { profile, pendingBadgeIds, clearPendingBadges, setTokenBalance } = useAuth();
  const { session, rematch, startOnlineGame, resetGame } = useGame();
  const [rematchState, setRematchState] = useState<RematchState>('idle');
  const [inviterSocketId, setInviterSocketId] = useState<string | null>(null);
  const [inviterName, setInviterName] = useState<string>('');
  const [showBadgeModal, setShowBadgeModal] = useState(false);
  const [queuedBadgeIds, setQueuedBadgeIds] = useState<string[]>([]);
  const serverMatchId = useGameStore((s) => s.serverMatchId);
  const [awardedTokens, setAwardedTokens] = useState(0);
  const badgeInitialized = React.useRef(false);
  const tokenAwardGuard = React.useRef(false);

  const isOnline = session?.mode === 'online';
  const opponentSocketId = session?.opponentSocketId ?? null;
  const opponentProfileId = session?.opponentProfileId ?? null;

  useEffect(() => {
    if (badgeInitialized.current) return;
    badgeInitialized.current = true;
    if (pendingBadgeIds.length > 0) {
      setQueuedBadgeIds([...pendingBadgeIds]);
      const t = setTimeout(() => setShowBadgeModal(true), 700);
      return () => clearTimeout(t);
    }
  }, [pendingBadgeIds]);

  useEffect(() => {
    return () => { setTimeout(clearPendingBadges, 0); };
  }, []);

  useEffect(() => {
    if (!session || !session.isGameOver || !profile) return;
    if (tokenAwardGuard.current) return;
    if (session.mode === 'friend') return;

    if (session.mode === 'challenge' && session.challengeId) {
      tokenAwardGuard.current = true;
      const myPlayer = session.players.find((p: { isBot?: boolean; id: string }) => !p.isBot && p.id !== 'bot_quick');
      const mySymbol = myPlayer?.symbol ?? 'X';
      const gameResult = session.result ?? 'DRAW';
      const draw = gameResult === 'DRAW';
      const won = !draw && gameResult === `${mySymbol}_WIN`;
      const matchToken = session.matchToken ?? '';
      recordChallengeMatch(session.challengeId, profile.id, won, draw, matchToken).catch(() => {});
      return;
    }

    if (!serverMatchId) return;
    tokenAwardGuard.current = true;
    awardWinTokens(profile.id, serverMatchId).then((result) => {
      if (result.ok) {
        if (result.tokenBalance > 0) setTokenBalance(result.tokenBalance);
        if (result.awarded > 0) setAwardedTokens(result.awarded);
      }
    });
  }, [session, profile, serverMatchId]);

  useEffect(() => {
    if (!isOnline) return;

    const offInvite = realtimeService.onRematchInvite((data) => {
      setInviterSocketId(data.fromSocketId);
      setInviterName(data.fromName);
      setRematchState('invited');
    });

    const offAccepted = realtimeService.onRematchAccepted((data) => {
      setRematchState('accepted');
      if (session && profile) {
        startOnlineGame(
          profile.selectedCharacterId,
          profile.name,
          data.yourSymbol,
          data.opponentCharacterId,
          data.opponentName,
          data.opponentSocketId,
          data.roomCode,
        );
        setTimeout(() => router.replace('/match'), 300);
      }
    });

    const offDeclined = realtimeService.onRematchDeclined(() => {
      setRematchState('declined');
    });

    const offExpired = realtimeService.onInviteExpired(() => {
      setRematchState('expired');
    });

    return () => {
      offInvite();
      offAccepted();
      offDeclined();
      offExpired();
    };
  }, [isOnline, session, profile]);

  useEffect(() => {
    if (!session || !session.isGameOver) router.replace('/home');
  }, [session]);

  if (!session || !session.isGameOver) return null;

  const isOnlineOrQuick = session.mode === 'online' || session.mode === 'quick';
  const myPlayer = session.players.find((p: { isBot?: boolean; id: string }) =>
    isOnlineOrQuick ? p.id === 'player' : !p.isBot && p.id !== 'bot_quick'
  );
  const mySymbol = myPlayer?.symbol ?? 'X';
  const isDraw = session.result === 'DRAW';
  const iWon = session.result === `${mySymbol}_WIN`;
  const iLost = !iWon && !isDraw;

  const winnerPlayer = session.result?.includes('WIN')
    ? session.players.find((p: { symbol: string }) => p.symbol === session.result?.charAt(0))
    : null;

  const displayChar = winnerPlayer
    ? getCharacter(winnerPlayer.characterId)
    : myPlayer ? getCharacter(myPlayer.characterId) : getCharacter('nino');

  const myChar = myPlayer ? getCharacter(myPlayer.characterId) : getCharacter('nino');

  const reactionText = iWon
    ? displayChar.reactions.win
    : iLost ? myChar.reactions.lose
    : displayChar.reactions.draw;

  const handleRematch = async () => {
    if (isOnline && opponentSocketId) {
      realtimeService.sendRematchInvite(opponentSocketId);
      setRematchState('inviting');
    } else {
      if (session?.mode === 'challenge' && session.challengeId && profile) {
        const newToken = await startChallengeMatch(session.challengeId, profile.id).catch(() => null);
        if (!newToken) {
          Alert.alert('Could not start rematch', 'The challenge may have ended. Return home to check your results.');
          return;
        }
        rematch();
        useGameStore.setState((s) => s.session ? { session: { ...s.session, matchToken: newToken } } : {});
      } else {
        rematch();
      }
      router.replace('/match');
    }
  };

  const handleAcceptInvite = () => {
    if (inviterSocketId) {
      realtimeService.respondRematch(inviterSocketId, true);
      setRematchState('idle');
    }
  };

  const handleDeclineInvite = () => {
    if (inviterSocketId) {
      realtimeService.respondRematch(inviterSocketId, false);
      setRematchState('idle');
    }
  };

  const handleHome = () => {
    if (isOnline && session?.roomCode) {
      realtimeService.leaveRoom(session.roomCode);
    }
    resetGame();
    router.replace('/home');
  };

  const rematchOverlay = isOnline ? (
    <RematchOverlay
      state={rematchState}
      inviterName={inviterName}
      onAccept={handleAcceptInvite}
      onDecline={handleDeclineInvite}
      onReset={() => setRematchState('idle')}
    />
  ) : null;

  const badgeModal = showBadgeModal && queuedBadgeIds.length > 0 ? (
    <BadgeUnlockModal
      badgeIds={queuedBadgeIds}
      onDismissAll={() => {
        setShowBadgeModal(false);
        setQueuedBadgeIds([]);
      }}
    />
  ) : null;

  const isOpponentBot = winnerPlayer?.isBot || session.mode === 'bot';
  const opponentProfileNavId = isOpponentBot
    ? 'bot'
    : (isOnline && opponentProfileId ? opponentProfileId : null);

  const handleViewOpponentProfile = () => {
    if (opponentProfileNavId) {
      router.push(`/public-profile?id=${opponentProfileNavId}`);
    }
  };

  if (iWon) return <>{<WinScreen char={displayChar} quote={reactionText} profile={profile} newBadgeIds={pendingBadgeIds} insets={insets} onRematch={handleRematch} onHome={handleHome} rematchOverlay={rematchOverlay} awardedTokens={awardedTokens} />}{badgeModal}</>;
  if (iLost) return <>{<LoseScreen myChar={myChar} winnerChar={displayChar} winnerName={winnerPlayer?.name ?? 'Opponent'} quote={reactionText} profile={profile} insets={insets} onRematch={handleRematch} onHome={handleHome} rematchOverlay={rematchOverlay} onViewOpponent={opponentProfileNavId ? handleViewOpponentProfile : undefined} />}{badgeModal}</>;
  return <>{<DrawScreen myChar={myChar} quote={reactionText} insets={insets} onRematch={handleRematch} onHome={handleHome} rematchOverlay={rematchOverlay} />}{badgeModal}</>;
}

function RematchOverlay({ state, inviterName, onAccept, onDecline, onReset }: {
  state: RematchState;
  inviterName: string;
  onAccept: () => void;
  onDecline: () => void;
  onReset: () => void;
}) {
  const { colors } = useTheme();
  if (state === 'idle') return null;

  return (
    <View style={styles.rematchOverlay}>
      {state === 'inviting' && (
        <View style={[styles.rematchCard, { backgroundColor: colors.card }]}>
          <ActivityIndicator color={Colors.purple} size="small" />
          <Text style={[styles.rematchStatusText, { color: colors.textSecondary }]}>Waiting for opponent...</Text>
        </View>
      )}
      {state === 'invited' && (
        <View style={[styles.rematchCard, { backgroundColor: colors.card }]}>
          <Text style={[styles.rematchTitle, { color: colors.textPrimary }]}>{inviterName} wants a rematch!</Text>
          <View style={styles.rematchRow}>
            <Pressable onPress={onAccept} style={[styles.rematchSmallBtn, { backgroundColor: Colors.green }]}>
              <Text style={styles.rematchSmallBtnText}>Accept</Text>
            </Pressable>
            <Pressable onPress={onDecline} style={[styles.rematchSmallBtn, { backgroundColor: colors.inputBg }]}>
              <Text style={[styles.rematchSmallBtnText, { color: colors.textSecondary }]}>Decline</Text>
            </Pressable>
          </View>
        </View>
      )}
      {state === 'declined' && (
        <View style={[styles.rematchCard, { backgroundColor: colors.card }]}>
          <Text style={[styles.rematchStatusText, { color: colors.textSecondary }]}>Opponent declined the rematch</Text>
          <Pressable onPress={onReset} style={[styles.rematchSmallBtn, { backgroundColor: colors.inputBg, marginTop: 8 }]}>
            <Text style={[styles.rematchSmallBtnText, { color: colors.textSecondary }]}>OK</Text>
          </Pressable>
        </View>
      )}
      {state === 'expired' && (
        <View style={[styles.rematchCard, { backgroundColor: colors.card }]}>
          <Text style={[styles.rematchStatusText, { color: colors.textSecondary }]}>Rematch invite expired</Text>
          <Pressable onPress={onReset} style={[styles.rematchSmallBtn, { backgroundColor: colors.inputBg, marginTop: 8 }]}>
            <Text style={[styles.rematchSmallBtnText, { color: colors.textSecondary }]}>OK</Text>
          </Pressable>
        </View>
      )}
      {state === 'accepted' && (
        <View style={[styles.rematchCard, { backgroundColor: colors.card }]}>
          <ActivityIndicator color={Colors.purple} size="small" />
          <Text style={[styles.rematchStatusText, { color: colors.textSecondary }]}>Starting rematch...</Text>
        </View>
      )}
    </View>
  );
}

function WinScreen({ char, quote, profile, newBadgeIds = [], insets, onRematch, onHome, rematchOverlay, awardedTokens = 0 }: {
  char: Character; quote: string; profile: UserProfile | null; newBadgeIds?: string[];
  insets: Insets; onRematch: () => void; onHome: () => void; rematchOverlay?: React.ReactNode;
  awardedTokens?: number;
}) {
  const { colors } = useTheme();
  const glow = useSharedValue(1);
  useEffect(() => {
    glow.value = withRepeat(
      withSequence(
        withTiming(1.12, { duration: 500, easing: Easing.inOut(Easing.sin) }),
        withTiming(1, { duration: 500, easing: Easing.inOut(Easing.sin) })
      ), -1, true
    );
  }, []);
  const glowAnim = useAnimatedStyle(() => ({ transform: [{ scale: glow.value }], opacity: 0.2 }));
  const ringAnim = useAnimatedStyle(() => ({ transform: [{ scale: glow.value * 0.95 }], opacity: 0.28 }));

  const newBadges = newBadgeIds.map((id) => getBadge(id)).filter(Boolean);

  return (
    <View style={[styles.screen, { backgroundColor: colors.background, paddingTop: insets.top + 12, paddingBottom: insets.bottom + 20 }]}>
      <WinConfetti />
      <View style={[styles.blob, { top: -80, right: -60, width: 300, height: 300, backgroundColor: char.accentColor, opacity: 0.16 }]} />

      <AnimTitle label="VICTORY" color={Colors.yellow} />

      <View style={[styles.stage, newBadges.length > 0 ? { height: 170 } : {}]}>
        <Animated.View style={[styles.winHalo, { backgroundColor: char.accentColor }, glowAnim]} />
        <Animated.View style={[styles.winRing, { borderColor: char.accentColor }, ringAnim]} />
        <BouncingCharacter character={char} size={newBadges.length > 0 ? 160 : 200} reactionType="win" />
      </View>

      <SlideUp delay={100}>
        <View style={[styles.quoteCard, { backgroundColor: colors.card, borderColor: colors.border }]}>
          <View style={styles.quoteTop}>
            <Text style={[styles.quoteCharName, { color: colors.textPrimary }]}>{char.name}</Text>
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
              {awardedTokens > 0 && (
                <View style={styles.tokenChip}>
                  <Text style={styles.tokenChipText}>+{awardedTokens} 🪙</Text>
                </View>
              )}
              {profile && (
                <View style={[styles.streakBadge, { backgroundColor: colors.inputBg }]}>
                  <Ionicons name="flame" size={13} color={Colors.yellow} />
                  <Text style={styles.streakNum}>{profile.stats.streak}</Text>
                  <Text style={[styles.streakLbl, { color: colors.textFaint }]}> streak</Text>
                </View>
              )}
            </View>
          </View>
          <Text style={[styles.quoteText, { color: colors.textSecondary }]}>"{quote}"</Text>
        </View>
      </SlideUp>

      {newBadges.length > 0 && (
        <SlideUp delay={180}>
          <View style={styles.newBadgesWrap}>
            <View style={styles.newBadgesTop}>
              <Ionicons name="ribbon" size={14} color={Colors.yellow} />
              <Text style={styles.newBadgesLabel}>Badge{newBadges.length > 1 ? 's' : ''} Unlocked!</Text>
            </View>
            {newBadges.map((badge) => badge && (
              <View key={badge.id} style={[styles.newBadgeRow, { backgroundColor: badge.pale, borderColor: badge.color + '40' }]}>
                <View style={[styles.newBadgeIcon, { backgroundColor: badge.color }]}>
                  <Ionicons name={badge.icon} size={16} color={Colors.white} />
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={[styles.newBadgeName, { color: colors.textPrimary }]}>{badge.name}</Text>
                  <Text style={[styles.newBadgeDesc, { color: colors.textMuted }]}>{badge.description}</Text>
                </View>
              </View>
            ))}
          </View>
        </SlideUp>
      )}

      <SlideUp delay={220}>
        <RematchBtn label="⚡  Play Again" color={Colors.yellow} onPress={onRematch} />
      </SlideUp>

      <SlideUp delay={300}>
        <Pressable onPress={onHome} style={styles.homeLink}>
          <Text style={[styles.homeLinkText, { color: colors.textFaint }]}>Back to Home</Text>
        </Pressable>
      </SlideUp>

      {rematchOverlay}
    </View>
  );
}

const LOSE_EMOJI: Record<string, string> = {
  nino: '🥺',
  blue_detective: '🤔',
  pink: '😤',
  blue_succulent: '😶',
  scientist: '🧐',
  yellow: '😭',
};

function LoseScreen({ myChar, winnerChar, winnerName, quote, profile, insets, onRematch, onHome, rematchOverlay, onViewOpponent }: {
  myChar: Character; winnerChar: Character; winnerName: string; quote: string;
  profile: UserProfile | null; insets: Insets; onRematch: () => void; onHome: () => void; rematchOverlay?: React.ReactNode; onViewOpponent?: () => void;
}) {
  const { colors } = useTheme();
  const shake = useSharedValue(0);
  useEffect(() => {
    shake.value = withDelay(150, withSequence(
      withTiming(-10, { duration: 50 }),
      withTiming(10, { duration: 50 }),
      withTiming(-8, { duration: 50 }),
      withTiming(8, { duration: 50 }),
      withTiming(-4, { duration: 50 }),
      withTiming(0, { duration: 50 })
    ));
  }, []);
  const shakeStyle = useAnimatedStyle(() => ({
    transform: [{ rotate: `${shake.value}deg` }],
  }));

  const missionPct = profile && profile.stats.missionGoal > 0
    ? profile.stats.missionProgress / profile.stats.missionGoal : 0;
  const missionDone = missionPct >= 1;
  const loseEmoji = LOSE_EMOJI[myChar.id] ?? '😅';

  return (
    <View style={[styles.screen, { backgroundColor: colors.background, paddingTop: insets.top + 12, paddingBottom: insets.bottom + 20 }]}>
      <View style={[styles.blob, { top: -50, right: -60, width: 260, height: 260, backgroundColor: Colors.coralPale }]} />
      <View style={[styles.blob, { bottom: 60, left: -80, width: 200, height: 200, backgroundColor: Colors.yellowPale }]} />

      <AnimTitle label="NOT THIS TIME!" color={Colors.coral} />

      <SlideUp delay={50}>
        <Pressable
          onPress={onViewOpponent}
          disabled={!onViewOpponent}
          style={[styles.loseWinnerChip, { backgroundColor: colors.card }, onViewOpponent && { borderWidth: 1, borderColor: colors.border }]}
        >
          <View style={[styles.loseWinnerDot, { backgroundColor: winnerChar.accentColor }]} />
          <Text style={styles.loseWinnerChipText}>
            <Text style={[styles.loseWinnerName, { color: winnerChar.accentColor }]}>{winnerName}</Text>
            <Text style={[styles.loseWinnerSuffix, { color: colors.textSecondary }]}> wins this round</Text>
          </Text>
          {onViewOpponent && (
            <Ionicons name="chevron-forward" size={14} color={colors.textFaint} />
          )}
        </Pressable>
      </SlideUp>

      <View style={styles.loseStage}>
        <View style={[styles.loseStageBlob, { backgroundColor: Colors.coralPale }]} />
        <View style={[styles.loseStageRing, { borderColor: Colors.coral }]} />
        <Animated.View style={shakeStyle}>
          <BouncingCharacter character={myChar} size={175} reactionType="lose" />
        </Animated.View>
      </View>

      <SlideUp delay={140}>
        <View style={[styles.loseModule, { backgroundColor: colors.card, shadowColor: Colors.coral }]}>
          <View style={[styles.loseModuleBar, { backgroundColor: Colors.coral }]} />
          <View style={styles.loseModuleBody}>
            <Text style={styles.loseModuleEmoji}>{loseEmoji}</Text>
            <View style={styles.loseModuleTextBlock}>
              <Text style={[styles.loseModuleCharLabel, { color: myChar.accentColor }]}>
                {myChar.name} says...
              </Text>
              <Text style={[styles.loseModuleQuote, { color: colors.textPrimary }]}>"{quote}"</Text>
            </View>
          </View>
          <View style={[styles.loseModuleDivider, { backgroundColor: colors.border }]} />
          <View style={styles.loseModuleFooter}>
            {profile && (
              <View style={styles.loseQuestChip}>
                <Ionicons name="flag" size={13} color={Colors.purple} />
                <Text style={[styles.loseQuestText, { color: Colors.purple }]}>
                  {missionDone ? 'Quest done!' : `Quest: ${profile.stats.missionProgress}/${profile.stats.missionGoal}`}
                </Text>
              </View>
            )}
            <View style={styles.loseRevengePill}>
              <Ionicons name="refresh" size={13} color={Colors.coral} />
              <Text style={[styles.loseRevengeText, { color: Colors.coral }]}>Revenge?</Text>
            </View>
          </View>
        </View>
      </SlideUp>

      <SlideUp delay={230}>
        <View style={styles.actions}>
          <NinoButton label="🔥  Try Again" onPress={onRematch} color={Colors.coral} fullWidth />
          <NinoButton label="Back to Home" onPress={onHome} variant="ghost" fullWidth />
        </View>
      </SlideUp>

      {rematchOverlay}
    </View>
  );
}

function DrawScreen({ myChar, quote, insets, onRematch, onHome, rematchOverlay }: {
  myChar: Character; quote: string;
  insets: Insets; onRematch: () => void; onHome: () => void; rematchOverlay?: React.ReactNode;
}) {
  const { colors } = useTheme();
  const sway = useSharedValue(0);
  useEffect(() => {
    sway.value = withRepeat(
      withSequence(
        withTiming(-8, { duration: 420, easing: Easing.inOut(Easing.sin) }),
        withTiming(8, { duration: 420, easing: Easing.inOut(Easing.sin) })
      ), -1, true
    );
  }, []);
  const swayAnim = useAnimatedStyle(() => ({ transform: [{ rotate: `${sway.value}deg` }] }));

  return (
    <View style={[styles.screen, { backgroundColor: colors.background, paddingTop: insets.top + 12, paddingBottom: insets.bottom + 20 }]}>
      <View style={[styles.blob, { top: -60, left: -50, width: 220, height: 220, backgroundColor: Colors.bluePale, opacity: 0.9 }]} />
      <View style={[styles.blob, { bottom: 100, right: -60, width: 200, height: 200, backgroundColor: Colors.purplePale, opacity: 0.7 }]} />

      <AnimTitle label="IT'S A TIE!" color={Colors.blue} />

      <SlideUp delay={40}>
        <Text style={[styles.drawSubtitle, { color: colors.textSecondary }]}>Perfectly matched opponents!</Text>
      </SlideUp>

      <View style={styles.stage}>
        <View style={[styles.charBg, { backgroundColor: Colors.bluePale }]} />
        <BouncingCharacter character={myChar} size={170} reactionType="idle" />
      </View>

      <SlideUp delay={120}>
        <View style={[styles.drawCard, { backgroundColor: colors.card, shadowColor: Colors.blue }]}>
          <Animated.View style={swayAnim}>
            <Ionicons name="swap-horizontal" size={28} color={Colors.blue} />
          </Animated.View>
          <Text style={styles.drawCardTitle}>Equal rivals</Text>
          <Text style={[styles.drawCardQuote, { color: colors.textSecondary }]}>"{quote}"</Text>
          <View style={[styles.drawChip, { backgroundColor: Colors.bluePale }]}>
            <Ionicons name="flash" size={13} color={Colors.blue} />
            <Text style={[styles.drawChipText, { color: Colors.blue }]}>Who breaks the stalemate?</Text>
          </View>
        </View>
      </SlideUp>

      <SlideUp delay={210}>
        <View style={styles.actions}>
          <NinoButton label="⚡  Settle It Now" onPress={onRematch} color={Colors.blue} fullWidth />
          <NinoButton label="Back to Home" onPress={onHome} variant="ghost" fullWidth />
        </View>
      </SlideUp>

      {rematchOverlay}
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, paddingHorizontal: 22, gap: 14 },

  blob: { position: 'absolute', borderRadius: 999 },

  titleWrap: { alignItems: 'center', marginTop: 4 },
  titleText: { fontSize: 44, fontFamily: 'Inter_700Bold', letterSpacing: 3, textAlign: 'center' },

  stage: { alignItems: 'center', justifyContent: 'center', height: 232, position: 'relative' },

  winHalo: { position: 'absolute', width: 220, height: 220, borderRadius: 110 },
  winRing: { position: 'absolute', width: 250, height: 250, borderRadius: 125, borderWidth: 2 },

  charBg: { position: 'absolute', width: 190, height: 190, borderRadius: 95 },

  quoteCard: {
    borderRadius: 20, padding: 16,
    gap: 8,
    borderWidth: 1,
  },
  quoteTop: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  quoteCharName: { fontSize: 15, fontFamily: 'Inter_700Bold' },
  streakBadge: {
    flexDirection: 'row', alignItems: 'center', gap: 3,
    paddingHorizontal: 10, paddingVertical: 4, borderRadius: 20,
  },
  streakNum: { fontSize: 14, fontFamily: 'Inter_700Bold', color: Colors.yellow },
  streakLbl: { fontSize: 11, fontFamily: 'Inter_400Regular' },
  quoteText: {
    fontSize: 14, fontFamily: 'Inter_400Regular',
    fontStyle: 'italic', lineHeight: 21,
  },

  rematchBtn: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
    gap: 10,
    borderRadius: 22,
    paddingVertical: 20,
    paddingHorizontal: 28,
    shadowOffset: { width: 0, height: 8 },
    shadowRadius: 20,
    elevation: 10,
  },
  rematchBtnLabel: {
    fontSize: 18, fontFamily: 'Inter_700Bold',
    color: Colors.white, letterSpacing: 0.3,
  },

  homeLink: {
    alignItems: 'center', paddingVertical: 8,
  },
  homeLinkText: {
    fontSize: 13, fontFamily: 'Inter_400Regular',
    textDecorationLine: 'underline',
  },

  loseWinnerChip: {
    flexDirection: 'row', alignItems: 'center', gap: 8,
    alignSelf: 'center',
    paddingHorizontal: 16, paddingVertical: 8,
    borderRadius: 50,
    shadowColor: '#000', shadowOpacity: 0.05,
    shadowOffset: { width: 0, height: 2 }, shadowRadius: 8, elevation: 2,
  },
  loseWinnerDot: { width: 9, height: 9, borderRadius: 4.5 },
  loseWinnerChipText: { fontSize: 13 },
  loseWinnerName: { fontFamily: 'Inter_700Bold', fontSize: 13 },
  loseWinnerSuffix: { fontFamily: 'Inter_400Regular', fontSize: 13 },

  loseStage: {
    height: 200,
    alignItems: 'center',
    justifyContent: 'center',
    position: 'relative',
  },
  loseStageBlob: {
    position: 'absolute',
    width: 185, height: 185, borderRadius: 92.5,
  },
  loseStageRing: {
    position: 'absolute',
    width: 202, height: 202, borderRadius: 101,
    borderWidth: 1.5, opacity: 0.3,
  },

  loseModule: {
    borderRadius: 20, overflow: 'hidden',
    shadowOpacity: 0.08,
    shadowOffset: { width: 0, height: 4 }, shadowRadius: 12, elevation: 4,
  },
  loseModuleBar: { height: 4 },
  loseModuleBody: {
    flexDirection: 'row', alignItems: 'center',
    gap: 14, padding: 16,
  },
  loseModuleEmoji: { fontSize: 36, lineHeight: 44 },
  loseModuleTextBlock: { flex: 1, gap: 4 },
  loseModuleCharLabel: {
    fontSize: 11, fontFamily: 'Inter_700Bold', letterSpacing: 0.5,
  },
  loseModuleQuote: {
    fontSize: 14, fontFamily: 'Inter_400Regular',
    fontStyle: 'italic', lineHeight: 21,
  },
  loseModuleDivider: {
    height: 1,
    marginHorizontal: 16,
  },
  loseModuleFooter: {
    flexDirection: 'row', alignItems: 'center',
    gap: 10, padding: 12, paddingHorizontal: 16,
  },
  loseQuestChip: {
    flexDirection: 'row', alignItems: 'center', gap: 5,
    backgroundColor: Colors.purplePale,
    paddingHorizontal: 10, paddingVertical: 5,
    borderRadius: 20, flex: 1,
  },
  loseQuestText: { fontSize: 12, fontFamily: 'Inter_600SemiBold' },
  loseRevengePill: {
    flexDirection: 'row', alignItems: 'center', gap: 5,
    backgroundColor: Colors.coralPale,
    paddingHorizontal: 12, paddingVertical: 5,
    borderRadius: 20,
  },
  loseRevengeText: { fontSize: 12, fontFamily: 'Inter_700Bold' },

  drawSubtitle: {
    fontSize: 14, fontFamily: 'Inter_400Regular', textAlign: 'center',
    marginTop: -6,
  },
  drawCard: {
    borderRadius: 20,
    padding: 20, alignItems: 'center', gap: 8,
    shadowOpacity: 0.07,
    shadowOffset: { width: 0, height: 3 }, shadowRadius: 12, elevation: 4,
  },
  drawCardTitle: { fontSize: 18, fontFamily: 'Inter_700Bold', color: Colors.blue },
  drawCardQuote: {
    fontSize: 14, fontFamily: 'Inter_400Regular',
    textAlign: 'center',
    fontStyle: 'italic', lineHeight: 21,
  },
  drawChip: {
    flexDirection: 'row', alignItems: 'center', gap: 5,
    paddingHorizontal: 12, paddingVertical: 7, borderRadius: 20,
  },
  drawChipText: { fontSize: 12, fontFamily: 'Inter_700Bold' },

  actions: { gap: 10 },

  tokenChip: {
    flexDirection: 'row', alignItems: 'center',
    backgroundColor: 'rgba(250,204,21,0.15)',
    paddingHorizontal: 8, paddingVertical: 4,
    borderRadius: 20,
    borderWidth: 1, borderColor: 'rgba(250,204,21,0.3)',
  },
  tokenChipText: { fontSize: 12, fontFamily: 'Inter_700Bold', color: Colors.yellow },

  newBadgesWrap: { gap: 8 },
  newBadgesTop: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  newBadgesLabel: { fontSize: 14, fontFamily: 'Inter_700Bold', color: Colors.yellow },
  newBadgeRow: {
    flexDirection: 'row', alignItems: 'center', gap: 12,
    padding: 12, borderRadius: 16, borderWidth: 1,
  },
  newBadgeIcon: {
    width: 36, height: 36, borderRadius: 11,
    alignItems: 'center', justifyContent: 'center',
  },
  newBadgeName: { fontSize: 13, fontFamily: 'Inter_700Bold' },
  newBadgeDesc: { fontSize: 11, fontFamily: 'Inter_400Regular', marginTop: 2 },

  rematchOverlay: {
    position: 'absolute', top: 0, left: 0, right: 0, bottom: 0,
    backgroundColor: 'rgba(22,22,42,0.85)',
    alignItems: 'center', justifyContent: 'center',
    zIndex: 100,
  },
  rematchCard: {
    borderRadius: 24, padding: 24,
    marginHorizontal: 32, gap: 14,
    alignItems: 'center', width: '80%',
    shadowColor: '#000', shadowOpacity: 0.3,
    shadowOffset: { width: 0, height: 8 }, shadowRadius: 20, elevation: 10,
  },
  rematchTitle: {
    fontSize: 18, fontFamily: 'Inter_700Bold', textAlign: 'center',
  },
  rematchStatusText: {
    fontSize: 15, fontFamily: 'Inter_400Regular',
    textAlign: 'center',
  },
  rematchRow: { flexDirection: 'row', gap: 12 },
  rematchSmallBtn: {
    paddingHorizontal: 20, paddingVertical: 12,
    borderRadius: 14, alignItems: 'center', minWidth: 100,
  },
  rematchSmallBtnText: { fontSize: 14, fontFamily: 'Inter_700Bold', color: Colors.white },
});
