import { router, useLocalSearchParams } from 'expo-router';
import React, { useEffect, useRef, useState } from 'react';
import {
  ActivityIndicator,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useAuth } from '@/src/context/AuthContext';
import { useTheme } from '@/src/context/ThemeContext';
import { realtimeService, type PublicProfile } from '@/src/services/realtimeService';
import { UserProfile } from '@/src/types';
import { NinoButton } from '@/src/components/NinoButton';

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

export default function RoomDeepLinkScreen() {
  const insets = useSafeAreaInsets();
  const { profile } = useAuth();
  const { colors } = useTheme();
  const params = useLocalSearchParams<{ code: string }>();
  const roomCode = (params.code ?? '').toUpperCase();

  const [status, setStatus] = useState<'connecting' | 'joining' | 'error'>('connecting');
  const [error, setError] = useState('');
  const cleanupRef = useRef<(() => void) | null>(null);
  const joinAttemptedRef = useRef(false);

  useEffect(() => {
    return () => { cleanupRef.current?.(); };
  }, []);

  useEffect(() => {
    if (!profile || !roomCode) return;
    if (realtimeService.isConnected) {
      doJoin(profile);
      return;
    }
    setStatus('connecting');
    const pub = buildPublicProfile(profile);
    realtimeService.connect(pub);
    const off = realtimeService.onConnect(() => {
      off();
      doJoin(profile);
    });
    const timeout = setTimeout(() => {
      if (!realtimeService.isConnected) {
        setStatus('error');
        setError('Could not connect to server. Check your connection.');
      }
    }, 8000);
    cleanupRef.current = () => { off(); clearTimeout(timeout); };
  }, [profile, roomCode]);

  function doJoin(prof: UserProfile) {
    if (joinAttemptedRef.current) return;
    joinAttemptedRef.current = true;
    setStatus('joining');
    cleanupRef.current?.();
    cleanupRef.current = realtimeService.joinRoom(
      roomCode,
      prof.selectedCharacterId,
      (data) => {
        router.replace({
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
        setStatus('error');
        setError('The host left the room before you could join.');
      },
      (err) => {
        setStatus('error');
        setError(err || 'Could not join room. The code may be invalid or expired.');
      },
    );
  }

  function handleRetry() {
    joinAttemptedRef.current = false;
    setError('');
    if (!profile) return;
    if (realtimeService.isConnected) {
      doJoin(profile);
    } else {
      setStatus('connecting');
      const pub = buildPublicProfile(profile);
      realtimeService.connect(pub);
      const off = realtimeService.onConnect(() => {
        off();
        doJoin(profile);
      });
      cleanupRef.current = () => { off(); };
    }
  }

  return (
    <View style={[styles.container, { backgroundColor: colors.background, paddingTop: insets.top, paddingBottom: insets.bottom + 24 }]}>
      <View style={styles.content}>
        {status !== 'error' ? (
          <>
            <ActivityIndicator color={Colors.blue} size="large" />
            <Text style={[styles.title, { color: colors.textPrimary }]}>
              {status === 'connecting' ? 'Connecting…' : `Joining room ${roomCode}…`}
            </Text>
            <Text style={[styles.sub, { color: colors.textSecondary }]}>Hang tight, getting you into the game</Text>
          </>
        ) : (
          <>
            <Text style={[styles.errorTitle, { color: colors.textPrimary }]}>Could not join</Text>
            <Text style={[styles.errorMsg, { color: Colors.coral }]}>{error}</Text>
            <NinoButton label="Try Again" onPress={handleRetry} color={Colors.blue} fullWidth />
            <NinoButton
              label="Go Home"
              onPress={() => router.replace('/home')}
              variant="ghost"
              fullWidth
            />
          </>
        )}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, paddingHorizontal: 32 },
  content: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 18 },
  title: { fontSize: 22, fontFamily: 'Inter_700Bold', textAlign: 'center' },
  sub: { fontSize: 14, fontFamily: 'Inter_400Regular', textAlign: 'center' },
  errorTitle: { fontSize: 24, fontFamily: 'Inter_700Bold' },
  errorMsg: { fontSize: 14, fontFamily: 'Inter_400Regular', textAlign: 'center' },
});
