import { Ionicons } from '@expo/vector-icons';
import { router, useLocalSearchParams } from 'expo-router';
import * as Clipboard from 'expo-clipboard';
import * as Linking from 'expo-linking';
import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  Modal,
  Pressable,
  Share,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import Animated, {
  runOnJS,
  useAnimatedStyle,
  useSharedValue,
  withRepeat,
  withSequence,
  withSpring,
  withTiming,
} from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useAuth } from '@/src/context/AuthContext';
import { useGame } from '@/src/context/GameContext';
import { getCharacter } from '@/src/data/characters';
import { CharacterId } from '@/src/types';
import { BouncingCharacter } from '@/src/components/BouncingCharacter';
import { NinoButton } from '@/src/components/NinoButton';
import { PremiumCard } from '@/src/components/PremiumCard';
import { CharacterAvatar } from '@/src/components/CharacterAvatar';
import { realtimeService } from '@/src/services/realtimeService';
import { useTheme } from '@/src/context/ThemeContext';

interface OpponentInfo {
  name: string;
  characterId: CharacterId;
  socketId: string;
}

const SHEET_HEIGHT = 280;

export default function RoomLobbyScreen() {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const { profile } = useAuth();
  const { startOnlineGame } = useGame();
  const params = useLocalSearchParams<{
    code?: string;
    timerSec?: string;
    role?: string;
    oppName?: string;
    oppCharId?: string;
    oppSocketId?: string;
  }>();

  const roomCode = params.code ?? null;
  const timerSec = params.timerSec && params.timerSec !== 'null'
    ? parseInt(params.timerSec, 10)
    : null;
  const isGuest = params.role === 'guest';

  const oppName = params.oppName ?? null;
  const oppCharId = params.oppCharId ?? null;
  const oppSocketId = params.oppSocketId ?? null;

  const guestOpponent: OpponentInfo | null = isGuest && oppName && oppCharId && oppSocketId
    ? {
        name: oppName,
        characterId: oppCharId as CharacterId,
        socketId: oppSocketId,
      }
    : null;

  const [opponent, setOpponent] = useState<OpponentInfo | null>(guestOpponent);
  const [countdown, setCountdown] = useState<number | null>(null);
  const [copiedLink, setCopiedLink] = useState(false);
  const [shareSheetVisible, setShareSheetVisible] = useState(false);
  const cleanupRef = useRef<(() => void) | null>(null);
  const countdownRef = useRef<ReturnType<typeof setInterval> | null>(null);
  const startedRef = useRef(false);
  const autoSheetOpenedRef = useRef(false);

  const character = profile ? getCharacter(profile.selectedCharacterId) : getCharacter('nino');
  const opponentChar = opponent ? getCharacter(opponent.characterId) : null;

  const pulseOpacity = useSharedValue(1);
  const countdownScale = useSharedValue(1);
  const sheetTranslateY = useSharedValue(SHEET_HEIGHT);
  const overlayOpacity = useSharedValue(0);

  useEffect(() => {
    pulseOpacity.value = withRepeat(
      withSequence(withTiming(0.35, { duration: 900 }), withTiming(1, { duration: 900 })),
      -1,
      false,
    );
  }, []);

  useEffect(() => {
    if (!isGuest && roomCode && !autoSheetOpenedRef.current) {
      autoSheetOpenedRef.current = true;
      const t = setTimeout(() => openShareSheet(), 400);
      return () => clearTimeout(t);
    }
  }, []);

  useEffect(() => {
    if (countdown !== null) {
      countdownScale.value = withSpring(1.3, { damping: 5 }, () => {
        countdownScale.value = withTiming(1, { duration: 150 });
      });
    }
  }, [countdown]);

  function openShareSheet() {
    setShareSheetVisible(true);
    sheetTranslateY.value = withSpring(0, { damping: 20, stiffness: 200 });
    overlayOpacity.value = withTiming(1, { duration: 200 });
  }

  function closeShareSheet() {
    sheetTranslateY.value = withTiming(SHEET_HEIGHT, { duration: 250 }, (finished) => {
      if (finished) runOnJS(setShareSheetVisible)(false);
    });
    overlayOpacity.value = withTiming(0, { duration: 200 });
  }

  const startCountdown = useCallback((opp: OpponentInfo) => {
    if (startedRef.current) return;
    startedRef.current = true;

    let count = 3;
    setCountdown(count);

    countdownRef.current = setInterval(() => {
      count -= 1;
      if (count > 0) {
        setCountdown(count);
      } else {
        if (countdownRef.current) clearInterval(countdownRef.current);
        setCountdown(null);

        if (!profile || !roomCode) return;
        const mySymbol = isGuest ? 'O' : 'X';
        startOnlineGame(
          profile.selectedCharacterId,
          profile.name,
          mySymbol,
          opp.characterId,
          opp.name,
          opp.socketId,
          roomCode,
          timerSec,
        );
        router.replace('/match');
      }
    }, 1000);
  }, [profile, roomCode, isGuest, timerSec, startOnlineGame]);

  useEffect(() => {
    if (isGuest && oppName && oppCharId && oppSocketId && !startedRef.current) {
      startCountdown({
        name: oppName,
        characterId: oppCharId as CharacterId,
        socketId: oppSocketId,
      });
    }
  }, [isGuest, oppName, oppCharId, oppSocketId, startCountdown]);

  useEffect(() => {
    if (isGuest || !roomCode) return;

    const unsub = realtimeService.waitForOpponent(
      (player) => {
        const opp: OpponentInfo = {
          name: player.name,
          characterId: player.characterId,
          socketId: player.socketId,
        };
        setOpponent(opp);
        startCountdown(opp);
      },
      () => {
        if (countdownRef.current) clearInterval(countdownRef.current);
        setCountdown(null);
        setOpponent(null);
        startedRef.current = false;
      },
    );

    cleanupRef.current = unsub;
    return () => {
      unsub();
      if (countdownRef.current) clearInterval(countdownRef.current);
      cleanupRef.current = null;
    };
  }, [roomCode, isGuest]);

  const pulseStyle = useAnimatedStyle(() => ({ opacity: pulseOpacity.value }));
  const cdStyle = useAnimatedStyle(() => ({ transform: [{ scale: countdownScale.value }] }));
  const sheetStyle = useAnimatedStyle(() => ({ transform: [{ translateY: sheetTranslateY.value }] }));
  const overlayStyle = useAnimatedStyle(() => ({ opacity: overlayOpacity.value }));

  function handleCancel() {
    if (roomCode) realtimeService.leaveRoom(roomCode);
    if (countdownRef.current) clearInterval(countdownRef.current);
    cleanupRef.current?.();
    router.back();
  }

  function getRoomLink() {
    return Linking.createURL(`room/${roomCode}`);
  }

  async function handleCopyLink() {
    if (!roomCode) return;
    await Clipboard.setStringAsync(getRoomLink());
    setCopiedLink(true);
    setTimeout(() => setCopiedLink(false), 2500);
    closeShareSheet();
  }

  async function handleShareWhatsApp() {
    if (!roomCode) return;
    const link = getRoomLink();
    const msg = `Let's play Nino XO! 🌵 Join my room with code *${roomCode}* or tap: ${link}`;
    const whatsappUrl = `whatsapp://send?text=${encodeURIComponent(msg)}`;
    const canOpen = await Linking.canOpenURL(whatsappUrl);
    closeShareSheet();
    setTimeout(async () => {
      if (canOpen) {
        await Linking.openURL(whatsappUrl);
      } else {
        await Share.share({ message: msg });
      }
    }, 300);
  }

  async function handleShare() {
    if (!roomCode) return;
    closeShareSheet();
    setTimeout(async () => {
      await Share.share({ message: `Join my Nino XO room! Code: ${roomCode}\n${getRoomLink()}` });
    }, 300);
  }

  return (
    <View style={[styles.container, { backgroundColor: colors.background, paddingTop: insets.top, paddingBottom: insets.bottom + 24 }]}>
      <View style={styles.toolbar}>
        <Pressable onPress={handleCancel} style={styles.closeBtn}>
          <Ionicons name="close" size={24} color={colors.textPrimary} />
        </Pressable>
        <Text style={[styles.toolbarTitle, { color: colors.textPrimary }]}>{isGuest ? 'Joining Room' : 'Room Lobby'}</Text>
        <View style={{ width: 40 }} />
      </View>

      {roomCode && (
        <PremiumCard style={styles.codeCard}>
          <Text style={[styles.codeLabel, { color: colors.textFaint }]}>Room Code</Text>
          <Text style={[styles.code, { color: colors.textPrimary }]}>{roomCode}</Text>
          {!opponent && !isGuest && (
            <Pressable
              style={({ pressed }) => [styles.inviteBtn, pressed && { opacity: 0.75 }]}
              onPress={openShareSheet}
            >
              <Ionicons name="share-social-outline" size={16} color={Colors.white} />
              <Text style={styles.inviteBtnText}>Invite Friend</Text>
            </Pressable>
          )}
          {copiedLink && (
            <View style={styles.copiedBanner}>
              <Ionicons name="checkmark-circle" size={14} color={Colors.green} />
              <Text style={styles.copiedText}>Link copied!</Text>
            </View>
          )}
        </PremiumCard>
      )}

      {timerSec ? (
        <View style={styles.rulesChip}>
          <Ionicons name="timer-outline" size={13} color={Colors.yellow} />
          <Text style={styles.rulesChipText}>Turn timer: {timerSec}s</Text>
        </View>
      ) : null}

      <View style={styles.content}>
        {countdown !== null ? (
          <View style={styles.countdownWrap}>
            <Text style={[styles.countdownLabel, { color: colors.textSecondary }]}>Match starting in</Text>
            <Animated.Text style={[styles.countdownNum, { color: colors.textPrimary }, cdStyle]}>{countdown}</Animated.Text>
          </View>
        ) : opponent ? (
          <View style={styles.bothPlayersRow}>
            <View style={styles.playerPod}>
              <BouncingCharacter character={character} size={80} reactionType="found" />
              <Text style={[styles.podName, { color: colors.textPrimary }]}>{profile?.name ?? 'You'}</Text>
              <View style={[styles.readyBadge, { backgroundColor: Colors.green }]}>
                <Text style={styles.readyBadgeText}>READY</Text>
              </View>
            </View>
            <View style={styles.podVs}>
              <Text style={[styles.podVsText, { color: colors.textSecondary }]}>VS</Text>
            </View>
            <View style={styles.playerPod}>
              {opponentChar && (
                <BouncingCharacter character={opponentChar} size={80} reactionType="found" />
              )}
              <Text style={[styles.podName, { color: opponentChar?.accentColor ?? colors.textPrimary }]}>
                {opponent.name}
              </Text>
              <View style={[styles.readyBadge, { backgroundColor: Colors.green }]}>
                <Text style={styles.readyBadgeText}>READY</Text>
              </View>
            </View>
          </View>
        ) : (
          <>
            <BouncingCharacter character={character} size={120} reactionType="search" />
            <Animated.Text style={[styles.waitingText, { color: colors.textPrimary }, pulseStyle]}>
              Waiting for friend to join…
            </Animated.Text>
            <Text style={[styles.tipText, { color: colors.textMuted }]}>Tap Invite Friend to share your code</Text>
          </>
        )}

        <PremiumCard style={styles.playersCard}>
          <View style={styles.playerRow}>
            <CharacterAvatar character={character} size={28} />
            <Text style={[styles.playerName, { color: colors.textPrimary }]}>
              {profile?.name ?? 'You'}{isGuest ? ' (Guest)' : ' (Host)'}
            </Text>
            <Ionicons name="checkmark-circle" size={18} color={Colors.green} />
          </View>
          <View style={[styles.divider, { backgroundColor: colors.border }]} />
          <View style={styles.playerRow}>
            {opponentChar ? (
              <CharacterAvatar character={opponentChar} size={28} />
            ) : (
              <View style={[styles.emptyAvatar, { backgroundColor: colors.inputBg }]} />
            )}
            <Text style={[styles.playerName, { color: opponent ? colors.textPrimary : colors.textFaint }]}>
              {opponent?.name ?? (isGuest ? 'Host' : 'Waiting for guest…')}
            </Text>
            {opponent ? (
              <Ionicons name="checkmark-circle" size={18} color={Colors.green} />
            ) : (
              <Ionicons name="ellipsis-horizontal" size={18} color={colors.textFaint} />
            )}
          </View>
        </PremiumCard>
      </View>

      {!opponent && countdown === null && (
        <NinoButton label="Cancel" onPress={handleCancel} variant="ghost" fullWidth />
      )}

      {/* ── SHARE BOTTOM SHEET ── */}
      <Modal
        transparent
        visible={shareSheetVisible}
        animationType="none"
        statusBarTranslucent
        onRequestClose={closeShareSheet}
      >
        <View style={styles.sheetContainer}>
          <Animated.View style={[styles.sheetOverlay, overlayStyle]}>
            <Pressable style={StyleSheet.absoluteFill} onPress={closeShareSheet} />
          </Animated.View>
          <Animated.View style={[styles.sheet, { backgroundColor: colors.card }, sheetStyle, { paddingBottom: insets.bottom + 16 }]}>
            <View style={[styles.sheetHandle, { backgroundColor: colors.borderMid }]} />
            <Text style={[styles.sheetTitle, { color: colors.textPrimary }]}>Invite a Friend</Text>
            <Text style={[styles.sheetCode, { color: colors.textFaint }]}>{roomCode}</Text>

            <View style={[styles.sheetOptions, { backgroundColor: colors.background }]}>
              <Pressable
                style={({ pressed }) => [styles.sheetOption, pressed && { backgroundColor: colors.inputBg }]}
                onPress={handleCopyLink}
              >
                <View style={[styles.sheetOptionIcon, { backgroundColor: 'rgba(96,165,250,0.15)' }]}>
                  <Ionicons
                    name={copiedLink ? 'checkmark' : 'copy-outline'}
                    size={22}
                    color={Colors.blue}
                  />
                </View>
                <View style={styles.sheetOptionText}>
                  <Text style={[styles.sheetOptionLabel, { color: colors.textPrimary }]}>
                    {copiedLink ? 'Copied!' : 'Copy Link'}
                  </Text>
                  <Text style={[styles.sheetOptionSub, { color: colors.textMuted }]}>Paste anywhere to invite</Text>
                </View>
                <Ionicons name="chevron-forward" size={16} color={colors.textFaint} />
              </Pressable>

              <View style={[styles.sheetDivider, { backgroundColor: colors.border }]} />

              <Pressable
                style={({ pressed }) => [styles.sheetOption, pressed && { backgroundColor: colors.inputBg }]}
                onPress={handleShareWhatsApp}
              >
                <View style={[styles.sheetOptionIcon, { backgroundColor: 'rgba(74,222,128,0.15)' }]}>
                  <Ionicons name="logo-whatsapp" size={22} color={Colors.green} />
                </View>
                <View style={styles.sheetOptionText}>
                  <Text style={[styles.sheetOptionLabel, { color: colors.textPrimary }]}>WhatsApp</Text>
                  <Text style={[styles.sheetOptionSub, { color: colors.textMuted }]}>Send invite via WhatsApp</Text>
                </View>
                <Ionicons name="chevron-forward" size={16} color={colors.textFaint} />
              </Pressable>

              <View style={[styles.sheetDivider, { backgroundColor: colors.border }]} />

              <Pressable
                style={({ pressed }) => [styles.sheetOption, pressed && { backgroundColor: colors.inputBg }]}
                onPress={handleShare}
              >
                <View style={[styles.sheetOptionIcon, { backgroundColor: colors.inputBg }]}>
                  <Ionicons name="share-outline" size={22} color={colors.textSecondary} />
                </View>
                <View style={styles.sheetOptionText}>
                  <Text style={[styles.sheetOptionLabel, { color: colors.textPrimary }]}>More Options</Text>
                  <Text style={[styles.sheetOptionSub, { color: colors.textMuted }]}>Share via any app</Text>
                </View>
                <Ionicons name="chevron-forward" size={16} color={colors.textFaint} />
              </Pressable>
            </View>
          </Animated.View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, paddingHorizontal: 24 },
  toolbar: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 16,
  },
  closeBtn: { width: 40, height: 40, alignItems: 'center', justifyContent: 'center' },
  toolbarTitle: { fontSize: 17, fontFamily: 'Inter_600SemiBold' },

  codeCard: { alignItems: 'center', gap: 10, marginBottom: 8 },
  codeLabel: {
    fontSize: 11, fontFamily: 'Inter_500Medium',
    letterSpacing: 1.5, textTransform: 'uppercase',
  },
  code: { fontSize: 40, fontFamily: 'Inter_700Bold', letterSpacing: 10 },

  inviteBtn: {
    flexDirection: 'row', alignItems: 'center', gap: 7,
    backgroundColor: Colors.coral,
    paddingHorizontal: 18, paddingVertical: 10,
    borderRadius: 22,
  },
  inviteBtnText: { fontSize: 14, fontFamily: 'Inter_700Bold', color: Colors.white },

  copiedBanner: {
    flexDirection: 'row', alignItems: 'center', gap: 5,
    backgroundColor: 'rgba(74,222,128,0.12)',
    paddingHorizontal: 12, paddingVertical: 5, borderRadius: 12,
  },
  copiedText: { fontSize: 12, fontFamily: 'Inter_600SemiBold', color: Colors.green },

  rulesChip: {
    flexDirection: 'row', alignItems: 'center', gap: 6,
    alignSelf: 'center', backgroundColor: 'rgba(242,184,75,0.1)',
    borderRadius: 20, paddingHorizontal: 12, paddingVertical: 6, marginBottom: 4,
  },
  rulesChipText: { fontSize: 12, fontFamily: 'Inter_600SemiBold', color: Colors.yellow },

  content: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 18 },

  countdownWrap: { alignItems: 'center', gap: 8 },
  countdownLabel: { fontSize: 16, fontFamily: 'Inter_500Medium' },
  countdownNum: {
    fontSize: 96, fontFamily: 'Inter_700Bold', lineHeight: 104,
  },

  bothPlayersRow: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  playerPod: { alignItems: 'center', gap: 6, flex: 1 },
  podName: {
    fontSize: 14, fontFamily: 'Inter_700Bold', textAlign: 'center',
  },
  podVs: { width: 40, alignItems: 'center' },
  podVsText: { fontSize: 16, fontFamily: 'Inter_700Bold' },
  readyBadge: { paddingHorizontal: 8, paddingVertical: 3, borderRadius: 8 },
  readyBadgeText: {
    fontSize: 9, fontFamily: 'Inter_700Bold', color: Colors.white, letterSpacing: 0.8,
  },

  waitingText: {
    fontSize: 18, fontFamily: 'Inter_600SemiBold', textAlign: 'center',
  },
  tipText: {
    fontSize: 13, fontFamily: 'Inter_400Regular',
    textAlign: 'center',
  },

  playersCard: { width: '100%', gap: 12 },
  playerRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  playerName: { flex: 1, fontSize: 14, fontFamily: 'Inter_500Medium' },
  divider: { height: 1 },
  emptyAvatar: {
    width: 28, height: 28, borderRadius: 14,
  },

  sheetContainer: { flex: 1, justifyContent: 'flex-end' },
  sheetOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.55)',
  },
  sheet: {
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    paddingHorizontal: 24,
    paddingTop: 12,
    minHeight: SHEET_HEIGHT,
  },
  sheetHandle: {
    width: 40, height: 4, borderRadius: 2,
    alignSelf: 'center', marginBottom: 16,
  },
  sheetTitle: {
    fontSize: 17, fontFamily: 'Inter_700Bold',
    textAlign: 'center', marginBottom: 2,
  },
  sheetCode: {
    fontSize: 13, fontFamily: 'Inter_500Medium',
    textAlign: 'center', letterSpacing: 3, marginBottom: 18,
  },
  sheetOptions: {
    borderRadius: 16,
    overflow: 'hidden',
  },
  sheetOption: {
    flexDirection: 'row', alignItems: 'center', gap: 14,
    paddingHorizontal: 16, paddingVertical: 14,
  },
  sheetOptionIcon: {
    width: 44, height: 44, borderRadius: 14,
    alignItems: 'center', justifyContent: 'center',
  },
  sheetOptionText: { flex: 1 },
  sheetOptionLabel: { fontSize: 15, fontFamily: 'Inter_600SemiBold' },
  sheetOptionSub: { fontSize: 12, fontFamily: 'Inter_400Regular', marginTop: 1 },
  sheetDivider: { height: 1, marginLeft: 74 },
});
