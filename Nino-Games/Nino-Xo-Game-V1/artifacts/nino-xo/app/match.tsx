import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import React, { useEffect, useRef, useState } from 'react';
import {
  Modal,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import Animated, {
  useAnimatedStyle,
  useSharedValue,
  withTiming,
} from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useAuth } from '@/src/context/AuthContext';
import { useGame } from '@/src/context/GameContext';
import { useGameStore } from '@/src/stores/gameStore';
import { getCharacter } from '@/src/data/characters';
import { CharacterAvatar } from '@/src/components/CharacterAvatar';
import { GameBoard } from '@/src/components/GameBoard';
import { audioService } from '@/src/services/audioService';
import { realtimeService } from '@/src/services/realtimeService';
import { startMatch, recordMatchResult } from '@/src/services/tokenService';
import { Player } from '@/src/types';
import { useTheme } from '@/src/context/ThemeContext';

type Character = ReturnType<typeof getCharacter>;

type ThemeColors = ReturnType<typeof useTheme>['colors'];

function DuelCard({ player, char, isActive, isRight = false, isWinner = false, isOver, colors }: {
  player: Player;
  char: Character;
  isActive: boolean;
  isRight?: boolean;
  isWinner?: boolean;
  isOver: boolean;
  colors: ThemeColors;
}) {
  const cardOpacity = useSharedValue(1);

  useEffect(() => {
    if (isOver) {
      cardOpacity.value = withTiming(isWinner ? 1 : 0.35, { duration: 350 });
      return;
    }
    cardOpacity.value = withTiming(isActive ? 1 : 0.65, { duration: 180 });
  }, [isActive, isOver, isWinner]);

  const cardStyle = useAnimatedStyle(() => ({ opacity: cardOpacity.value }));

  const ringColor = isOver
    ? isWinner ? Colors.yellow : colors.border
    : isActive ? char.accentColor : colors.border;

  return (
    <Animated.View style={[styles.duelCard, isRight && styles.duelCardRight, cardStyle]}>
      <View style={styles.duelAvatarArea}>
        {isActive && !isOver && (
          <View style={[styles.duelActivePing, { backgroundColor: char.accentColor }]} />
        )}
        <View style={[styles.duelRing, { borderColor: ringColor, backgroundColor: colors.inputBg }]}>
          <CharacterAvatar character={char} size={44} />
        </View>
        {isWinner && isOver && (
          <View style={[styles.crownBadge, { borderColor: colors.card }]}>
            <Ionicons name="trophy" size={10} color={Colors.textDark} />
          </View>
        )}
      </View>
      <Text style={[styles.duelName, { color: colors.textPrimary }]} numberOfLines={1}>{player.name}</Text>
      <Text style={[styles.duelCharName, { color: char.accentColor }]}>{char.name}</Text>
      {isWinner && isOver && (
        <View style={[styles.winnerBadge, { backgroundColor: Colors.yellow }]}>
          <Text style={styles.winnerBadgeText}>WINNER</Text>
        </View>
      )}
    </Animated.View>
  );
}

function TurnPill({ text, color }: { text: string; color: string }) {
  return (
    <View style={[styles.turnPill, { backgroundColor: color + '22' }]}>
      <View style={[styles.turnDot, { backgroundColor: color }]} />
      <Text style={[styles.turnPillText, { color }]} numberOfLines={1}>{text}</Text>
    </View>
  );
}

export default function MatchScreen() {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const { profile, updateStats } = useAuth();
  const { session, makeMove, forfeitGame, resetGame } = useGame();
  const { setServerMatchId } = useGameStore();
  const statsUpdatedRef = useRef(false);
  const matchRegisteredRef = useRef(false);
  const isOnline = session?.mode === 'online';
  const [disconnectModal, setDisconnectModal] = useState(false);
  const [waitCountdown, setWaitCountdown] = useState<number | null>(null);
  const [passPhoneModal, setPassPhoneModal] = useState(false);
  const [turnTimeLeft, setTurnTimeLeft] = useState<number | null>(null);
  const waitTimerRef = useRef<ReturnType<typeof setInterval> | null>(null);
  const turnTimerRef = useRef<ReturnType<typeof setInterval> | null>(null);
  const prevSymbolRef = useRef<string | null>(null);

  useEffect(() => {
    if (!profile || !session || matchRegisteredRef.current) return;
    if (session.mode === 'friend') return;
    if (session.mode === 'challenge') return;
    const myPlayer = session.players.find((p: Player) => p.id === 'player');
    const opponent = session.players.find((p: Player) => p.id !== 'player');
    const mySymbol = (myPlayer?.symbol ?? 'X') as 'X' | 'O';
    matchRegisteredRef.current = true;
    startMatch(
      profile.id,
      session.mode,
      mySymbol,
      opponent?.name,
      opponent?.characterId,
    ).then((matchId) => {
      if (matchId) setServerMatchId(matchId);
    });
  }, [profile?.id, session?.mode]);

  useEffect(() => {
    if (!isOnline || !session?.roomCode) return;
    const unsub = realtimeService.onOpponentMove((index) => {
      makeMove(index);
    });
    const unsubLeft = realtimeService.onOpponentLeft(() => {
      if (!session?.isGameOver) {
        setDisconnectModal(true);
        let secs = 30;
        setWaitCountdown(secs);
        waitTimerRef.current = setInterval(() => {
          secs -= 1;
          if (secs <= 0) {
            if (waitTimerRef.current) clearInterval(waitTimerRef.current);
            setDisconnectModal(false);
            setWaitCountdown(null);
            resetGame();
            router.replace('/home');
          } else {
            setWaitCountdown(secs);
          }
        }, 1000);
      }
    });
    const unsubTimeout = realtimeService.onOpponentTurnTimeout(({ symbol }) => {
      const { session: current } = useGameStore.getState();
      if (current && !current.isGameOver) {
        forfeitGame(symbol as 'X' | 'O');
      }
    });
    return () => {
      unsub();
      unsubLeft();
      unsubTimeout();
      if (waitTimerRef.current) clearInterval(waitTimerRef.current);
    };
  }, [isOnline, session?.roomCode]);

  useEffect(() => {
    if (session?.isGameOver && !statsUpdatedRef.current) {
      statsUpdatedRef.current = true;
      const localPlayer = session.players.find((p: Player) => p.id === 'player');
      if (localPlayer) {
        const won = session.result === `${localPlayer.symbol}_WIN`;
        const isDraw = session.result === 'DRAW';
        updateStats(won, isDraw);
      }
      if (session.result === 'DRAW') {
        audioService.playDraw();
      } else {
        const localPlayer = session.players.find((p: Player) => p.id === 'player');
        const playerWon = localPlayer
          ? session.result === `${localPlayer.symbol}_WIN`
          : false;
        if (playerWon) {
          audioService.playWin();
        } else {
          audioService.playLose();
        }
      }
      const { serverMatchId, session: currentSession } = useGameStore.getState();
      if (serverMatchId && profile && currentSession && currentSession.mode !== 'friend' && currentSession.mode !== 'challenge') {
        recordMatchResult(serverMatchId, profile.id, currentSession.board as ('X' | 'O' | null)[]);
      }
      const timer = setTimeout(() => {
        router.replace('/result');
      }, 900);
      return () => clearTimeout(timer);
    }
  }, [session?.isGameOver]);

  useEffect(() => {
    if (!session || session.mode !== 'friend' || session.isGameOver) return;
    if (prevSymbolRef.current !== null && prevSymbolRef.current !== session.currentSymbol) {
      setPassPhoneModal(true);
    }
    prevSymbolRef.current = session.currentSymbol;
  }, [session?.currentSymbol, session?.mode, session?.isGameOver]);

  useEffect(() => {
    if (!session || session.isGameOver) {
      if (turnTimerRef.current) clearInterval(turnTimerRef.current);
      setTurnTimeLeft(null);
      return;
    }
    const timerSec = session.turnTimerSec ?? null;
    if (!timerSec) {
      setTurnTimeLeft(null);
      return;
    }

    if (turnTimerRef.current) clearInterval(turnTimerRef.current);
    setTurnTimeLeft(timerSec);

    let remaining = timerSec;
    turnTimerRef.current = setInterval(() => {
      remaining -= 1;
      if (remaining <= 0) {
        if (turnTimerRef.current) clearInterval(turnTimerRef.current);
        setTurnTimeLeft(null);
        const { session: current } = useGameStore.getState();
        if (current && !current.isGameOver) {
          const myPlayer = current.players.find((p: Player) => p.id === 'player');
          const isMyTurnNow = myPlayer?.symbol === current.currentSymbol;
          if (!isOnline || isMyTurnNow) {
            if (isOnline && current.roomCode) {
              realtimeService.sendTurnTimeout(current.roomCode, current.currentSymbol);
            }
            forfeitGame(current.currentSymbol);
          }
        }
      } else {
        setTurnTimeLeft(remaining);
      }
    }, 1000);

    return () => {
      if (turnTimerRef.current) clearInterval(turnTimerRef.current);
    };
  }, [session?.currentSymbol, session?.isGameOver, session?.turnTimerSec, isOnline]);

  useEffect(() => {
    if (!session) router.replace('/home');
  }, [session]);

  if (!session) return null;

  function handleLocalMove(index: number) {
    if (!session) return;
    const localPlayer = session.players.find((p: Player) => p.id === 'player');
    if (!localPlayer || localPlayer.symbol !== session.currentSymbol) return;
    makeMove(index);
    if (isOnline && session.roomCode) {
      realtimeService.sendMove(session.roomCode, index);
    }
  }

  const [p1, p2] = session.players;
  const p1Char = getCharacter(p1.characterId);
  const p2Char = getCharacter(p2.characterId);
  const myPlayer = session.players.find((p: Player) => p.id === 'player');
  const isMyTurn = myPlayer?.symbol === session.currentSymbol;
  const winnerSymbol = session.result?.includes('WIN')
    ? (session.result.charAt(0) as 'X' | 'O') : null;
  const isDraw = session.result === 'DRAW';
  const isOver = !!session.isGameOver;

  const currentPlayer = !isOver
    ? session.players.find((p: Player) => p.symbol === session.currentSymbol)
    : null;
  const currentChar = currentPlayer ? getCharacter(currentPlayer.characterId) : null;

  const boardDisabled =
    session.isGameOver ||
    (session.mode === 'bot' && !isMyTurn) ||
    (session.mode === 'online' && !isMyTurn);

  const turnText = isOver
    ? isDraw
      ? 'Draw!'
      : `${winnerSymbol === p1.symbol ? p1.name : p2.name} wins!`
    : !isMyTurn && (session.mode === 'bot' || session.mode === 'online')
    ? 'Opponent thinking…'
    : `${session.players.find((p: Player) => p.symbol === session.currentSymbol)?.name}'s turn`;

  const turnAccent = isOver
    ? isDraw
      ? Colors.blue
      : winnerSymbol === p1.symbol
      ? p1Char.accentColor
      : p2Char.accentColor
    : currentChar?.accentColor ?? Colors.textMid;

  const timerLabel = session.turnTimerSec ? ` · ${session.turnTimerSec}s` : '';
  const modeLabel =
    session.mode === 'bot'
      ? `vs Bot · ${session.botDifficulty}`
      : session.mode === 'quick'
      ? 'Quick Match'
      : session.mode === 'online'
      ? `Online Match${timerLabel}`
      : session.mode === 'challenge'
      ? '⚡ Challenge Match'
      : 'vs Friend';

  function handleDisconnectGoHome() {
    if (waitTimerRef.current) clearInterval(waitTimerRef.current);
    setDisconnectModal(false);
    setWaitCountdown(null);
    if (session?.roomCode) realtimeService.leaveRoom(session.roomCode);
    resetGame();
    router.replace('/home');
  }

  const currentTurnPlayer = session
    ? session.players.find((p: Player) => p.symbol === session.currentSymbol)
    : null;

  return (
    <View style={[styles.screen, { backgroundColor: colors.background, paddingTop: insets.top }]}>
      <Modal
        transparent
        visible={disconnectModal}
        animationType="fade"
        statusBarTranslucent
      >
        <View style={styles.modalOverlay}>
          <View style={[styles.modalCard, { backgroundColor: colors.card, borderColor: colors.border }]}>
            <Ionicons name="wifi-outline" size={40} color={Colors.coral} style={{ marginBottom: 8 }} />
            <Text style={[styles.modalTitle, { color: colors.textPrimary }]}>Opponent Disconnected</Text>
            <Text style={[styles.modalSub, { color: colors.textSecondary }]}>
              {waitCountdown !== null
                ? `Returning home in ${waitCountdown}s…`
                : 'Your opponent left the match.'}
            </Text>
            <Pressable style={styles.modalBtn} onPress={handleDisconnectGoHome}>
              <Text style={styles.modalBtnText}>Leave Now</Text>
            </Pressable>
          </View>
        </View>
      </Modal>

      <Modal
        transparent
        visible={passPhoneModal}
        animationType="fade"
        statusBarTranslucent
      >
        <View style={styles.modalOverlay}>
          <View style={[styles.modalCard, { backgroundColor: colors.card, borderColor: colors.border }]}>
            <Ionicons name="swap-horizontal" size={40} color={Colors.blue} style={{ marginBottom: 8 }} />
            <Text style={[styles.modalTitle, { color: colors.textPrimary }]}>Pass the Phone</Text>
            <Text style={[styles.modalSub, { color: colors.textSecondary }]}>
              {currentTurnPlayer ? `${currentTurnPlayer.name}'s turn!` : "It's the next player's turn!"}
            </Text>
            <Pressable style={styles.modalBtn} onPress={() => setPassPhoneModal(false)}>
              <Text style={styles.modalBtnText}>Ready!</Text>
            </Pressable>
          </View>
        </View>
      </Modal>

      {/* ── ARENA HEADER ── */}
      <View style={[styles.arenaHeader, { backgroundColor: colors.card }]}>
        <View style={styles.toolbar}>
          <Pressable
            onPress={() => {
              if (isOnline && session.roomCode) realtimeService.leaveRoom(session.roomCode);
              resetGame();
              router.replace('/home');
            }}
            style={[styles.exitBtn, { backgroundColor: colors.inputBg }]}
          >
            <Ionicons name="close" size={20} color={colors.textMuted} />
          </Pressable>
          <Text style={[styles.modeLabel, { color: colors.textFaint }]}>{modeLabel}</Text>
          <View style={[styles.moveCount, { backgroundColor: colors.inputBg }]}>
            <Text style={[styles.moveCountText, { color: colors.textFaint }]}>{session.moveCount}/9</Text>
          </View>
        </View>

        <View style={styles.duelRow}>
          <DuelCard
            player={p1}
            char={p1Char}
            isActive={!isOver && session.currentSymbol === p1.symbol}
            isWinner={winnerSymbol === p1.symbol}
            isOver={isOver}
            colors={colors}
          />
          <View style={styles.centerCol}>
            <View style={[styles.vsBadge, { backgroundColor: colors.inputBg, borderColor: colors.border }]}>
              <Text style={[styles.vsText, { color: colors.textMuted }]}>VS</Text>
            </View>
            <TurnPill text={turnText} color={turnAccent} />
            {turnTimeLeft !== null && !isOver && (
              <View style={[
                styles.timerPill,
                { backgroundColor: turnTimeLeft <= 5 ? Colors.coral + '33' : colors.inputBg },
              ]}>
                <Ionicons
                  name="timer-outline"
                  size={11}
                  color={turnTimeLeft <= 5 ? Colors.coral : colors.textSecondary}
                />
                <Text style={[
                  styles.timerPillText,
                  { color: turnTimeLeft <= 5 ? Colors.coral : colors.textSecondary },
                ]}>
                  {turnTimeLeft}s
                </Text>
              </View>
            )}
          </View>
          <DuelCard
            player={p2}
            char={p2Char}
            isActive={!isOver && session.currentSymbol === p2.symbol}
            isRight
            isWinner={winnerSymbol === p2.symbol}
            isOver={isOver}
            colors={colors}
          />
        </View>
      </View>

      {/* ── BOARD ARENA ── */}
      <View style={styles.boardArea}>
        <View style={[styles.boardCard, { backgroundColor: colors.card, shadowColor: turnAccent }]}>
          <GameBoard
            board={session.board}
            players={session.players}
            onMove={isOnline ? handleLocalMove : makeMove}
            disabled={boardDisabled}
            winLine={session.winLine}
            activeColor={turnAccent}
          />
        </View>
      </View>

      <View style={{ height: insets.bottom + 12 }} />
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1 },

  arenaHeader: {
    borderBottomLeftRadius: 28,
    borderBottomRightRadius: 28,
    paddingBottom: 14,
    overflow: 'hidden',
  },

  toolbar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingVertical: 8,
  },
  exitBtn: {
    width: 34, height: 34,
    alignItems: 'center', justifyContent: 'center',
    borderRadius: 10,
  },
  modeLabel: {
    fontSize: 12, fontFamily: 'Inter_600SemiBold',
    letterSpacing: 0.5,
  },
  moveCount: {
    width: 34, alignItems: 'center', justifyContent: 'center',
    paddingVertical: 5, borderRadius: 10,
  },
  moveCountText: {
    fontSize: 11, fontFamily: 'Inter_700Bold',
  },

  duelRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingTop: 2,
    paddingBottom: 0,
    gap: 8,
  },

  duelCard: {
    flex: 1,
    alignItems: 'flex-start',
    gap: 3,
  },
  duelCardRight: {
    alignItems: 'flex-end',
  },
  duelAvatarArea: {
    position: 'relative',
    alignItems: 'center',
    justifyContent: 'center',
    width: 58, height: 58,
  },
  duelActivePing: {
    position: 'absolute',
    width: 58, height: 58, borderRadius: 29,
    opacity: 0.25,
  },
  duelRing: {
    width: 54, height: 54, borderRadius: 27,
    borderWidth: 2,
    alignItems: 'center', justifyContent: 'center',
    overflow: 'hidden',
  },
  crownBadge: {
    position: 'absolute',
    bottom: -1, right: -1,
    width: 18, height: 18, borderRadius: 9,
    backgroundColor: Colors.yellow,
    alignItems: 'center', justifyContent: 'center',
    borderWidth: 1.5,
  },
  duelName: {
    fontSize: 13, fontFamily: 'Inter_700Bold',
    maxWidth: 100,
  },
  duelCharName: {
    fontSize: 10, fontFamily: 'Inter_600SemiBold',
  },
  winnerBadge: {
    paddingHorizontal: 7, paddingVertical: 3,
    borderRadius: 6,
  },
  winnerBadgeText: {
    fontSize: 9, fontFamily: 'Inter_700Bold',
    color: Colors.white, letterSpacing: 0.8,
  },

  centerCol: {
    alignItems: 'center',
    gap: 6,
    flex: 0,
    width: 90,
  },
  vsBadge: {
    width: 36, height: 36, borderRadius: 18,
    borderWidth: 1.5,
    alignItems: 'center', justifyContent: 'center',
  },
  vsText: {
    fontSize: 11, fontFamily: 'Inter_700Bold',
    letterSpacing: 1,
  },

  turnPill: {
    flexDirection: 'row', alignItems: 'center', gap: 5,
    paddingHorizontal: 10, paddingVertical: 6,
    borderRadius: 50,
    maxWidth: 90,
  },
  turnDot: { width: 6, height: 6, borderRadius: 3, flexShrink: 0 },
  turnPillText: {
    fontSize: 10, fontFamily: 'Inter_700Bold',
    letterSpacing: 0.2, flexShrink: 1,
  },

  boardArea: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingTop: 8,
  },
  boardCard: {
    borderRadius: 28,
    padding: 10,
    shadowOpacity: 0.16,
    shadowOffset: { width: 0, height: 8 },
    shadowRadius: 24,
    elevation: 10,
  },

  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.72)',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 32,
  },
  modalCard: {
    borderRadius: 24,
    padding: 28,
    alignItems: 'center',
    gap: 10,
    width: '100%',
    borderWidth: 1,
  },
  modalTitle: {
    fontSize: 20,
    fontFamily: 'Inter_700Bold',
    textAlign: 'center',
  },
  modalSub: {
    fontSize: 14,
    fontFamily: 'Inter_400Regular',
    textAlign: 'center',
  },
  modalBtn: {
    marginTop: 8,
    backgroundColor: Colors.blue,
    paddingVertical: 14,
    paddingHorizontal: 32,
    borderRadius: 14,
    width: '100%',
    alignItems: 'center',
  },
  modalBtnText: {
    fontSize: 16,
    fontFamily: 'Inter_700Bold',
    color: Colors.white,
  },
  timerPill: {
    flexDirection: 'row', alignItems: 'center', gap: 3,
    paddingHorizontal: 7, paddingVertical: 3, borderRadius: 10, marginTop: 4,
  },
  timerPillText: { fontSize: 11, fontFamily: 'Inter_700Bold' },
});
