import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import React, { useEffect, useRef, useState } from 'react';
import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useAuth } from '@/src/context/AuthContext';
import { useGame } from '@/src/context/GameContext';
import { getCharacter, CHARACTERS } from '@/src/data/characters';
import { CharacterId, UserProfile } from '@/src/types';
import { NinoButton } from '@/src/components/NinoButton';
import { CharacterAvatar } from '@/src/components/CharacterAvatar';
import { realtimeService, type PublicProfile, type RoomSettings } from '@/src/services/realtimeService';
import { useTheme } from '@/src/context/ThemeContext';

type GameMode = 'online' | 'local';
type OnlineTab = 'create' | 'join';
type ConnectState = 'idle' | 'connecting' | 'connected' | 'error';

function buildPublicProfile(profile: UserProfile): PublicProfile {
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

const TIMER_OPTIONS: { label: string; value: number | null }[] = [
  { label: 'Off', value: null },
  { label: '30s', value: 30 },
  { label: '60s', value: 60 },
];

export default function FriendRoomScreen() {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const { profile } = useAuth();
  const { startFriendGame } = useGame();

  const [gameMode, setGameMode] = useState<GameMode>('online');
  const [onlineTab, setOnlineTab] = useState<OnlineTab>('create');
  const [connectState, setConnectState] = useState<ConnectState>('idle');
  const [connectError, setConnectError] = useState('');

  const [roomName, setRoomName] = useState('');
  const [turnTimer, setTurnTimer] = useState<number | null>(null);

  const [joinCode, setJoinCode] = useState('');
  const [joinLoading, setJoinLoading] = useState(false);
  const [joinError, setJoinError] = useState('');

  const [p2Name, setP2Name] = useState('');
  const [p2CharId, setP2CharId] = useState<CharacterId>('blue_detective');
  const [p1CharId, setP1CharId] = useState<CharacterId>(
    profile?.selectedCharacterId ?? 'nino',
  );
  const [localStep, setLocalStep] = useState<1 | 2>(1);

  const cleanupRef = useRef<(() => void) | null>(null);

  const myChar = profile ? getCharacter(profile.selectedCharacterId) : getCharacter('nino');

  useEffect(() => {
    return () => { cleanupRef.current?.(); };
  }, []);

  function connectToServer() {
    if (!profile) return;
    if (realtimeService.isConnected) {
      setConnectState('connected');
      return;
    }
    setConnectState('connecting');
    setConnectError('');
    const pub = buildPublicProfile(profile);
    realtimeService.connect(pub);
    const off = realtimeService.onConnect(() => { setConnectState('connected'); });
    const timeout = setTimeout(() => {
      if (!realtimeService.isConnected) {
        setConnectState('error');
        setConnectError('Could not connect to server. Check your connection.');
      }
    }, 8000);
    cleanupRef.current = () => { off(); clearTimeout(timeout); };
  }

  function handleModeChange(mode: GameMode) {
    setGameMode(mode);
    if (mode === 'online' && connectState === 'idle') connectToServer();
  }

  useEffect(() => {
    connectToServer();
  }, []);

  function handleCreateRoom() {
    if (!profile || connectState !== 'connected') return;
    const finalName = roomName.trim() || `${profile.name}'s Room`;
    const settings: RoomSettings = { roomName: finalName, turnTimerSec: turnTimer };
    cleanupRef.current?.();
    cleanupRef.current = realtimeService.createRoom(
      settings,
      profile.selectedCharacterId,
      (code) => {
        router.push({
          pathname: '/room-lobby',
          params: {
            code,
            timerSec: turnTimer !== null ? String(turnTimer) : 'null',
            role: 'host',
          },
        });
      },
      (err) => { setConnectError(err); },
    );
  }

  function handleJoinRoom(code = joinCode) {
    const trimmedCode = code.trim().toUpperCase();
    if (!profile || !trimmedCode || connectState !== 'connected') return;
    setJoinLoading(true);
    setJoinError('');
    cleanupRef.current?.();
    cleanupRef.current = realtimeService.joinRoom(
      trimmedCode,
      profile.selectedCharacterId,
      (data) => {
        setJoinLoading(false);
        router.push({
          pathname: '/room-lobby',
          params: {
            code: data.roomCode,
            timerSec: data.turnTimerSec !== null ? String(data.turnTimerSec) : 'null',
            role: 'guest',
            oppName: data.hostName,
            oppCharId: data.hostCharacterId,
            oppSocketId: data.hostSocketId,
          },
        });
      },
      () => {
        setJoinLoading(false);
        setJoinError('Host left the room. Please try again.');
      },
      (err) => {
        setJoinLoading(false);
        setJoinError(err);
      },
    );
  }

  function handleLocalStart() {
    if (!profile) return;
    const name2 = p2Name.trim() || 'Player 2';
    startFriendGame(p1CharId, profile.name, p2CharId, name2);
    router.push('/match');
  }

  return (
    <KeyboardAvoidingView
      style={{ flex: 1 }}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <View style={[styles.container, { backgroundColor: colors.background, paddingTop: insets.top }]}>
        <View style={styles.toolbar}>
          <Pressable onPress={() => router.back()} style={styles.backBtn}>
            <Ionicons name="chevron-back" size={26} color={colors.textPrimary} />
          </Pressable>
          <Text style={[styles.toolbarTitle, { color: colors.textPrimary }]}>Play with Friend</Text>
          <View style={{ width: 40 }} />
        </View>

        <View style={[styles.modeSelector, { backgroundColor: colors.inputBg }]}>
          <Pressable
            onPress={() => handleModeChange('online')}
            style={[styles.modeBtn, gameMode === 'online' && styles.modeBtnActive]}
          >
            <Ionicons
              name="wifi"
              size={14}
              color={gameMode === 'online' ? Colors.white : colors.textMuted}
            />
            <Text style={[styles.modeBtnText, { color: colors.textMuted }, gameMode === 'online' && styles.modeBtnTextActive]}>
              Play Remotely
            </Text>
          </Pressable>
          <Pressable
            onPress={() => handleModeChange('local')}
            style={[styles.modeBtn, gameMode === 'local' && styles.modeBtnActive]}
          >
            <Ionicons
              name="phone-portrait-outline"
              size={14}
              color={gameMode === 'local' ? Colors.white : colors.textMuted}
            />
            <Text style={[styles.modeBtnText, { color: colors.textMuted }, gameMode === 'local' && styles.modeBtnTextActive]}>
              Same Device
            </Text>
          </Pressable>
        </View>

        {gameMode === 'online' ? (
          <>
            {connectState !== 'connected' ? (
              <View style={styles.connectingBox}>
                {connectState === 'connecting' && (
                  <>
                    <ActivityIndicator color={Colors.blue} size="small" />
                    <Text style={[styles.connectingText, { color: colors.textSecondary }]}>Connecting to server…</Text>
                  </>
                )}
                {connectState === 'error' && (
                  <>
                    <Ionicons name="cloud-offline-outline" size={32} color={colors.textFaint} />
                    <Text style={styles.errorText}>{connectError}</Text>
                    <NinoButton label="Retry" onPress={connectToServer} color={Colors.blue} />
                  </>
                )}
                {connectState === 'idle' && (
                  <NinoButton label="Connect" onPress={connectToServer} color={Colors.blue} />
                )}
              </View>
            ) : (
              <>
                <View style={[styles.tabs, { backgroundColor: colors.inputBg }]}>
                  <Pressable
                    onPress={() => setOnlineTab('create')}
                    style={[styles.tabBtn, onlineTab === 'create' && { backgroundColor: colors.card }]}
                  >
                    <Text style={[styles.tabText, { color: colors.textMuted }, onlineTab === 'create' && { color: colors.textPrimary, fontFamily: 'Inter_600SemiBold' }]}>
                      Create Room
                    </Text>
                  </Pressable>
                  <Pressable
                    onPress={() => setOnlineTab('join')}
                    style={[styles.tabBtn, onlineTab === 'join' && { backgroundColor: colors.card }]}
                  >
                    <Text style={[styles.tabText, { color: colors.textMuted }, onlineTab === 'join' && { color: colors.textPrimary, fontFamily: 'Inter_600SemiBold' }]}>
                      Join Room
                    </Text>
                  </Pressable>
                </View>

                <ScrollView
                  contentContainerStyle={[styles.scroll, { paddingBottom: insets.bottom + 24 }]}
                  keyboardShouldPersistTaps="handled"
                >
                  {onlineTab === 'create' ? (
                    <CreateRoomPanel
                      myChar={myChar}
                      profileName={profile?.name ?? 'You'}
                      roomName={roomName}
                      onRoomNameChange={setRoomName}
                      turnTimer={turnTimer}
                      onTimerChange={setTurnTimer}
                      connectError={connectError}
                      onCreateRoom={handleCreateRoom}
                      colors={colors}
                    />
                  ) : (
                    <JoinRoomPanel
                      joinCode={joinCode}
                      onCodeChange={(t) => {
                        setJoinCode(t.toUpperCase().slice(0, 6));
                        setJoinError('');
                      }}
                      joinLoading={joinLoading}
                      joinError={joinError}
                      onJoinRoom={() => handleJoinRoom()}
                      colors={colors}
                    />
                  )}
                </ScrollView>
              </>
            )}
          </>
        ) : (
          <LocalModePanel
            profile={profile}
            localStep={localStep}
            setLocalStep={setLocalStep}
            p1CharId={p1CharId}
            setP1CharId={setP1CharId}
            p2Name={p2Name}
            setP2Name={setP2Name}
            p2CharId={p2CharId}
            setP2CharId={setP2CharId}
            onStart={handleLocalStart}
            insets={insets}
            colors={colors}
          />
        )}
      </View>
    </KeyboardAvoidingView>
  );
}

function CreateRoomPanel({
  myChar, profileName, roomName, onRoomNameChange, turnTimer, onTimerChange,
  connectError, onCreateRoom, colors,
}: {
  myChar: ReturnType<typeof getCharacter>;
  profileName: string;
  roomName: string;
  onRoomNameChange: (v: string) => void;
  turnTimer: number | null;
  onTimerChange: (v: number | null) => void;
  connectError: string;
  onCreateRoom: () => void;
  colors: ReturnType<typeof useTheme>['colors'];
}) {
  return (
    <>
      <View style={styles.vsRow}>
        <View style={styles.playerSide}>
          <CharacterAvatar character={myChar} size={56} showRing />
          <Text style={[styles.playerName, { color: colors.textPrimary }]}>{profileName}</Text>
          <Text style={[styles.playerChar, { color: myChar.accentColor }]}>{myChar.name}</Text>
        </View>
        <Text style={[styles.vsText, { color: colors.textSecondary }]}>VS</Text>
        <View style={styles.playerSide}>
          <View style={[styles.unknownAvatar, { backgroundColor: colors.inputBg, borderColor: colors.border }]}>
            <Ionicons name="person" size={28} color={colors.textVeryFaint} />
          </View>
          <Text style={[styles.playerName, { color: colors.textPrimary }]}>Your Friend</Text>
          <Text style={[styles.playerChar, { color: colors.textMuted }]}>Waiting…</Text>
        </View>
      </View>

      <Text style={[styles.fieldLabel, { color: colors.textPrimary }]}>Room name (optional)</Text>
      <TextInput
        style={[styles.input, { backgroundColor: colors.card, color: colors.textPrimary }]}
        placeholder={`${profileName}'s Room`}
        placeholderTextColor={colors.textVeryFaint}
        value={roomName}
        onChangeText={onRoomNameChange}
        maxLength={32}
      />

      <Text style={[styles.fieldLabel, { color: colors.textPrimary }]}>Turn timer</Text>
      <View style={styles.timerRow}>
        {TIMER_OPTIONS.map((opt) => (
          <Pressable
            key={String(opt.value)}
            onPress={() => onTimerChange(opt.value)}
            style={[
              styles.timerBtn,
              { backgroundColor: colors.card },
              turnTimer === opt.value && { borderColor: Colors.blue, backgroundColor: 'rgba(91,141,184,0.12)' },
            ]}
          >
            <Text style={[
              styles.timerBtnText,
              { color: colors.textMuted },
              turnTimer === opt.value && { color: Colors.blue },
            ]}>
              {opt.label}
            </Text>
          </Pressable>
        ))}
      </View>

      {connectError ? <Text style={styles.errorText}>{connectError}</Text> : null}

      <NinoButton label="Create Room" onPress={onCreateRoom} color={Colors.blue} fullWidth />
    </>
  );
}

function JoinRoomPanel({
  joinCode, onCodeChange, joinLoading, joinError, onJoinRoom, colors,
}: {
  joinCode: string;
  onCodeChange: (v: string) => void;
  joinLoading: boolean;
  joinError: string;
  onJoinRoom: () => void;
  colors: ReturnType<typeof useTheme>['colors'];
}) {
  return (
    <>
      <View style={styles.joinHero}>
        <Ionicons name="key-outline" size={48} color={Colors.green} />
        <Text style={[styles.joinHeroTitle, { color: colors.textPrimary }]}>Enter Room Code</Text>
        <Text style={[styles.joinHeroSub, { color: colors.textMuted }]}>Ask your friend for their 6-character code</Text>
      </View>
      <TextInput
        style={[styles.input, styles.codeInput, { backgroundColor: colors.card, color: colors.textPrimary }]}
        placeholder="ABCDEF"
        placeholderTextColor={colors.textVeryFaint}
        value={joinCode}
        onChangeText={onCodeChange}
        maxLength={6}
        autoCapitalize="characters"
        autoCorrect={false}
        autoFocus
        editable={!joinLoading}
      />
      {joinError ? <Text style={styles.errorText}>{joinError}</Text> : null}
      {joinLoading ? (
        <View style={styles.joiningBox}>
          <ActivityIndicator color={Colors.blue} size="small" />
          <Text style={[styles.connectingText, { color: colors.textSecondary }]}>Joining room…</Text>
        </View>
      ) : (
        <NinoButton
          label="Join Room"
          onPress={onJoinRoom}
          color={Colors.green}
          fullWidth
          disabled={joinCode.length < 6}
        />
      )}
    </>
  );
}

function LocalModePanel({
  profile, localStep, setLocalStep, p1CharId, setP1CharId,
  p2Name, setP2Name, p2CharId, setP2CharId, onStart, insets, colors,
}: {
  profile: UserProfile | null;
  localStep: 1 | 2;
  setLocalStep: (s: 1 | 2) => void;
  p1CharId: CharacterId;
  setP1CharId: (id: CharacterId) => void;
  p2Name: string;
  setP2Name: (s: string) => void;
  p2CharId: CharacterId;
  setP2CharId: (id: CharacterId) => void;
  onStart: () => void;
  insets: { bottom: number };
  colors: ReturnType<typeof useTheme>['colors'];
}) {
  const p1CharObj = getCharacter(p1CharId);
  const p2Char = getCharacter(p2CharId);

  return (
    <ScrollView
      contentContainerStyle={[styles.scroll, { paddingBottom: insets.bottom + 24 }]}
      keyboardShouldPersistTaps="handled"
    >
      <View style={styles.localStepRow}>
        <View style={[styles.localStepDot, { backgroundColor: colors.inputBg }, localStep >= 1 && { backgroundColor: Colors.blue }]} />
        <View style={[styles.localStepLine, { backgroundColor: colors.border }, localStep >= 2 && { backgroundColor: Colors.blue }]} />
        <View style={[styles.localStepDot, { backgroundColor: colors.inputBg }, localStep >= 2 && { backgroundColor: Colors.blue }]} />
      </View>

      <View style={styles.vsRow}>
        <View style={styles.playerSide}>
          <CharacterAvatar character={p1CharObj} size={56} showRing />
          <Text style={[styles.playerName, { color: colors.textPrimary }]}>{profile?.name ?? 'Player 1'}</Text>
          <Text style={[styles.playerChar, { color: p1CharObj.accentColor }]}>{p1CharObj.name}</Text>
        </View>
        <View style={styles.passPhoneBox}>
          <Ionicons name="swap-horizontal" size={22} color={colors.textFaint} />
          <Text style={[styles.passPhoneText, { color: colors.textFaint }]}>pass phone</Text>
        </View>
        <View style={styles.playerSide}>
          <CharacterAvatar character={p2Char} size={56} showRing />
          <Text style={[styles.playerName, { color: colors.textPrimary }]}>{p2Name || 'Player 2'}</Text>
          <Text style={[styles.playerChar, { color: p2Char.accentColor }]}>{p2Char.name}</Text>
        </View>
      </View>

      {localStep === 1 ? (
        <>
          <Text style={[styles.localStepTitle, { color: colors.textPrimary }]}>Player 1 — Pick your character</Text>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.charScroll}>
            <View style={styles.charRow}>
              {CHARACTERS.map((c) => (
                <Pressable key={c.id} onPress={() => setP1CharId(c.id)}>
                  <View
                    style={[
                      styles.charChip,
                      { backgroundColor: colors.card },
                      p1CharId === c.id && { borderColor: c.accentColor, backgroundColor: c.accentPale },
                    ]}
                  >
                    <CharacterAvatar character={c} size={40} />
                    <Text
                      style={[
                        styles.charChipName,
                        { color: colors.textSecondary },
                        p1CharId === c.id && { color: c.accentColor },
                      ]}
                    >
                      {c.name}
                    </Text>
                  </View>
                </Pressable>
              ))}
            </View>
          </ScrollView>
          <NinoButton
            label="Next — Player 2's turn"
            onPress={() => setLocalStep(2)}
            color={Colors.blue}
            fullWidth
          />
        </>
      ) : (
        <>
          <Text style={[styles.localStepTitle, { color: colors.textPrimary }]}>Player 2 — Enter your name & character</Text>
          <Text style={[styles.fieldLabel, { color: colors.textPrimary }]}>Name</Text>
          <TextInput
            style={[styles.input, { backgroundColor: colors.card, color: colors.textPrimary }]}
            placeholder="Player 2"
            placeholderTextColor={colors.textVeryFaint}
            value={p2Name}
            onChangeText={setP2Name}
            maxLength={20}
            autoFocus
          />
          <Text style={[styles.fieldLabel, { color: colors.textPrimary }]}>Character</Text>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.charScroll}>
            <View style={styles.charRow}>
              {CHARACTERS.map((c) => (
                <Pressable key={c.id} onPress={() => setP2CharId(c.id)}>
                  <View
                    style={[
                      styles.charChip,
                      { backgroundColor: colors.card },
                      p2CharId === c.id && { borderColor: c.accentColor, backgroundColor: c.accentPale },
                    ]}
                  >
                    <CharacterAvatar character={c} size={40} />
                    <Text
                      style={[
                        styles.charChipName,
                        { color: colors.textSecondary },
                        p2CharId === c.id && { color: c.accentColor },
                      ]}
                    >
                      {c.name}
                    </Text>
                  </View>
                </Pressable>
              ))}
            </View>
          </ScrollView>
          <View style={{ flexDirection: 'row', gap: 10 }}>
            <View style={{ flex: 1 }}>
              <NinoButton label="Back" onPress={() => setLocalStep(1)} variant="ghost" fullWidth />
            </View>
            <View style={{ flex: 2 }}>
              <NinoButton label="Start Game!" onPress={onStart} color={Colors.blue} fullWidth />
            </View>
          </View>
        </>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  toolbar: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: 16, paddingVertical: 12,
  },
  backBtn: { width: 40, height: 40, alignItems: 'center', justifyContent: 'center' },
  toolbarTitle: { fontSize: 17, fontFamily: 'Inter_600SemiBold' },

  modeSelector: {
    flexDirection: 'row', marginHorizontal: 20, marginBottom: 12,
    borderRadius: 14, padding: 4,
  },
  modeBtn: {
    flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
    paddingVertical: 10, borderRadius: 10, gap: 6,
  },
  modeBtnActive: { backgroundColor: Colors.blue },
  modeBtnText: { fontSize: 13, fontFamily: 'Inter_500Medium' },
  modeBtnTextActive: { color: Colors.white, fontFamily: 'Inter_600SemiBold' },

  connectingBox: {
    flex: 1, alignItems: 'center', justifyContent: 'center', gap: 16, paddingHorizontal: 24,
  },
  connectingText: { fontSize: 15, fontFamily: 'Inter_500Medium' },
  errorText: { fontSize: 14, fontFamily: 'Inter_400Regular', color: Colors.coral, textAlign: 'center' },

  tabs: {
    flexDirection: 'row', marginHorizontal: 20,
    borderRadius: 14,
    padding: 4, marginBottom: 8,
  },
  tabBtn: { flex: 1, paddingVertical: 10, borderRadius: 10, alignItems: 'center' },
  tabText: { fontSize: 14, fontFamily: 'Inter_500Medium' },

  scroll: { paddingHorizontal: 20, gap: 14 },

  vsRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-around' },
  playerSide: { alignItems: 'center', gap: 6 },
  playerName: { fontSize: 14, fontFamily: 'Inter_600SemiBold' },
  playerChar: { fontSize: 12, fontFamily: 'Inter_400Regular' },
  vsText: { fontSize: 24, fontFamily: 'Inter_700Bold' },
  unknownAvatar: {
    width: 56, height: 56, borderRadius: 28,
    borderWidth: 2,
    alignItems: 'center', justifyContent: 'center',
  },
  passPhoneBox: { alignItems: 'center', gap: 4 },
  passPhoneText: { fontSize: 10, fontFamily: 'Inter_400Regular' },

  fieldLabel: { fontSize: 14, fontFamily: 'Inter_600SemiBold' },
  input: {
    height: 52, borderRadius: 14,
    paddingHorizontal: 18, fontSize: 16, fontFamily: 'Inter_400Regular',
  },
  codeInput: { textAlign: 'center', fontSize: 32, fontFamily: 'Inter_700Bold', letterSpacing: 10, height: 80 },

  timerRow: { flexDirection: 'row', gap: 10 },
  timerBtn: {
    flex: 1, paddingVertical: 12, borderRadius: 12,
    alignItems: 'center',
    borderWidth: 1.5, borderColor: 'transparent',
  },
  timerBtnText: { fontSize: 14, fontFamily: 'Inter_600SemiBold' },

  joinHero: { alignItems: 'center', gap: 8, paddingVertical: 12 },
  joinHeroTitle: { fontSize: 22, fontFamily: 'Inter_700Bold' },
  joinHeroSub: {
    fontSize: 14, fontFamily: 'Inter_400Regular',
    textAlign: 'center',
  },
  joiningBox: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 12, paddingVertical: 12,
  },

  localStepRow: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 40 },
  localStepDot: { width: 12, height: 12, borderRadius: 6 },
  localStepLine: { flex: 1, height: 2 },
  localStepTitle: {
    fontSize: 16, fontFamily: 'Inter_600SemiBold', textAlign: 'center',
  },

  charScroll: { marginHorizontal: -20 },
  charRow: { flexDirection: 'row', gap: 10, paddingHorizontal: 20 },
  charChip: {
    alignItems: 'center', padding: 10, borderRadius: 16,
    gap: 4,
    borderWidth: 2, borderColor: 'transparent', width: 80,
  },
  charChipName: {
    fontSize: 11, fontFamily: 'Inter_500Medium', textAlign: 'center',
  },
});
