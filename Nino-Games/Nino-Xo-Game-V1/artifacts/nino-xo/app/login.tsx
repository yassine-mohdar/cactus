import { router } from 'expo-router';
import React, { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Image,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import Animated, {
  useAnimatedStyle,
  useSharedValue,
  withDelay,
  withSpring,
  withTiming,
} from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import * as AppleAuthentication from 'expo-apple-authentication';
import * as WebBrowser from 'expo-web-browser';
import * as AuthSession from 'expo-auth-session';
import { Colors } from '@/constants/colors';
import { useAuth } from '@/src/context/AuthContext';
import { apiFetch } from '@/src/services/apiClient';
import { UserProfile } from '@/src/types';
import {
  GOOGLE_AVAILABLE,
  GOOGLE_CLIENT_ID_FOR_PLATFORM,
  APPLE_CLIENT_ID,
} from '@/src/config';
import { useTheme } from '@/src/context/ThemeContext';

WebBrowser.maybeCompleteAuthSession();

interface ServerAuthResponse {
  token: string;
  player: {
    id: string;
    name: string;
    email?: string;
    characterId: string;
    tokenBalance: number;
    totalMatches?: number;
    totalWins?: number;
    totalLosses?: number;
    totalDraws?: number;
    streak?: number;
    bestStreak?: number;
    earnedBadgeIds: string[];
    hasSubscription: boolean;
    isAdmin: boolean;
  };
}

function buildProfileFromServer(data: ServerAuthResponse): UserProfile {
  return {
    id: data.player.id,
    name: data.player.name,
    email: data.player.email,
    selectedCharacterId: (data.player.characterId as UserProfile['selectedCharacterId']) ?? 'nino',
    hasSubscription: data.player.hasSubscription ?? false,
    stats: {
      totalMatches: data.player.totalMatches ?? 0,
      totalWins: data.player.totalWins ?? 0,
      totalLosses: data.player.totalLosses ?? 0,
      totalDraws: data.player.totalDraws ?? 0,
      streak: data.player.streak ?? 0,
      bestStreak: data.player.bestStreak ?? 0,
      missionProgress: 0,
      missionGoal: 5,
    },
    earnedBadgeIds: data.player.earnedBadgeIds ?? [],
    tokenBalance: data.player.tokenBalance ?? 0,
  };
}

export default function LoginScreen() {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const { loginWithToken } = useAuth();
  const [loadingProvider, setLoadingProvider] = useState<'google' | 'apple' | null>(null);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const [appleAvailable, setAppleAvailable] = useState<boolean | null>(null);

  useEffect(() => {
    if (Platform.OS === 'ios') {
      AppleAuthentication.isAvailableAsync().then(setAppleAvailable).catch(() => setAppleAvailable(false));
    }
  }, []);

  const charScale = useSharedValue(0.6);
  const charOpacity = useSharedValue(0);
  const titleY = useSharedValue(24);
  const titleOpacity = useSharedValue(0);
  const formY = useSharedValue(32);
  const formOpacity = useSharedValue(0);

  useEffect(() => {
    charScale.value = withSpring(1, { damping: 10, stiffness: 80 });
    charOpacity.value = withTiming(1, { duration: 400 });
    titleY.value = withDelay(120, withSpring(0, { damping: 16 }));
    titleOpacity.value = withDelay(120, withTiming(1, { duration: 350 }));
    formY.value = withDelay(260, withSpring(0, { damping: 16 }));
    formOpacity.value = withDelay(260, withTiming(1, { duration: 350 }));
  }, []);

  const charStyle = useAnimatedStyle(() => ({
    transform: [{ scale: charScale.value }],
    opacity: charOpacity.value,
  }));
  const titleStyle = useAnimatedStyle(() => ({
    transform: [{ translateY: titleY.value }],
    opacity: titleOpacity.value,
  }));
  const formStyle = useAnimatedStyle(() => ({
    transform: [{ translateY: formY.value }],
    opacity: formOpacity.value,
  }));

  const handleSuccessfulAuth = async (data: ServerAuthResponse) => {
    const profile = buildProfileFromServer(data);
    await loginWithToken(data.token, profile);
    router.replace('/session-restore');
  };

  const handleGoogleSignIn = async () => {
    setLoadingProvider('google');
    setErrorMsg(null);
    try {
      const redirectUri = Platform.OS === 'web' && typeof window !== 'undefined'
        ? window.location.origin
        : AuthSession.makeRedirectUri();
      const discovery = await AuthSession.fetchDiscoveryAsync('https://accounts.google.com');
      const request = new AuthSession.AuthRequest({
        clientId: GOOGLE_CLIENT_ID_FOR_PLATFORM,
        scopes: ['openid', 'email', 'profile'],
        redirectUri,
      });
      const result = await request.promptAsync(discovery);

      if (result.type !== 'success' || !result.params['code']) {
        if (result.type !== 'cancel' && result.type !== 'dismiss') {
          setErrorMsg('Google sign-in failed. Please try again.');
        }
        setLoadingProvider(null);
        return;
      }

      const tokenRes = await AuthSession.exchangeCodeAsync(
        {
          code: result.params['code'],
          clientId: GOOGLE_CLIENT_ID_FOR_PLATFORM,
          redirectUri,
          extraParams: { code_verifier: request.codeVerifier ?? '' },
        },
        discovery,
      );

      const idToken = tokenRes.idToken;
      if (!idToken) {
        setErrorMsg('Google sign-in did not return an ID token.');
        setLoadingProvider(null);
        return;
      }

      const res = await apiFetch('/auth/google', {
        method: 'POST',
        body: JSON.stringify({ idToken }),
        skipAuth: true,
      });

      if (!res.ok) {
        setErrorMsg('Google sign-in failed. Please try again.');
        setLoadingProvider(null);
        return;
      }

      const data = await res.json() as ServerAuthResponse;
      await handleSuccessfulAuth(data);
    } catch {
      setErrorMsg('Google sign-in failed. Please try again.');
    } finally {
      setLoadingProvider(null);
    }
  };

  const handleAppleSignIn = async () => {
    setLoadingProvider('apple');
    setErrorMsg(null);
    try {
      const credential = await AppleAuthentication.signInAsync({
        requestedScopes: [
          AppleAuthentication.AppleAuthenticationScope.FULL_NAME,
          AppleAuthentication.AppleAuthenticationScope.EMAIL,
        ],
      });

      const identityToken = credential.identityToken;
      if (!identityToken) {
        setErrorMsg('Apple sign-in did not return an identity token.');
        setLoadingProvider(null);
        return;
      }

      const res = await apiFetch('/auth/apple', {
        method: 'POST',
        body: JSON.stringify({ identityToken }),
        skipAuth: true,
      });

      if (!res.ok) {
        setErrorMsg('Apple sign-in failed. Please try again.');
        setLoadingProvider(null);
        return;
      }

      const data = await res.json() as ServerAuthResponse;
      await handleSuccessfulAuth(data);
    } catch (err: unknown) {
      const code = (err as { code?: string })?.code;
      if (code !== 'ERR_REQUEST_CANCELED') {
        setErrorMsg('Apple sign-in failed. Please try again.');
      }
    } finally {
      setLoadingProvider(null);
    }
  };

  const handleEmailSignIn = () => {
    router.push('/auth-email');
  };

  const isLoading = !!loadingProvider;

  return (
    <ScrollView
      style={[styles.container, { backgroundColor: colors.background }]}
      contentContainerStyle={[styles.scroll, {
        paddingTop: insets.top + 20,
        paddingBottom: insets.bottom + 32,
      }]}
      keyboardShouldPersistTaps="handled"
      showsVerticalScrollIndicator={false}
    >
      <View style={[styles.blob, { top: -60, right: -80, width: 280, height: 280, backgroundColor: Colors.green, opacity: 0.12 }]} />
      <View style={[styles.blob, { bottom: 100, left: -80, width: 240, height: 240, backgroundColor: Colors.yellow, opacity: 0.08 }]} />
      <View style={[styles.blob, { top: 180, right: -60, width: 180, height: 180, backgroundColor: Colors.blue, opacity: 0.07 }]} />

      <Animated.View style={[styles.titleBlock, titleStyle]}>
        <View style={[styles.worldBadge, { backgroundColor: colors.inputBg, borderColor: colors.borderMid }]}>
          <View style={styles.worldDot} />
          <Text style={styles.worldLabel}>NINOWORLD</Text>
        </View>
        <Text style={[styles.gameTitle, { color: colors.textPrimary }]}>NINO XO</Text>
        <Text style={[styles.gameTagline, { color: colors.textFaint }]}>Character Battle · 1v1</Text>
      </Animated.View>

      <Animated.View style={[styles.charStage, charStyle]}>
        <View style={styles.charGlowLeft} />
        <View style={styles.charGlowRight} />
        <Image
          source={require('@/assets/images/char_group.png')}
          style={styles.mascot}
          resizeMode="contain"
        />
      </Animated.View>

      <Animated.View style={[styles.formBlock, formStyle]}>
        <Text style={[styles.prompt, { color: colors.textSecondary }]}>Sign in to save your progress</Text>

        {errorMsg && (
          <View style={styles.errorBox}>
            <Text style={styles.errorText}>{errorMsg}</Text>
          </View>
        )}

        {Platform.OS === 'ios' && appleAvailable === true && !!APPLE_CLIENT_ID && (
          <AppleAuthentication.AppleAuthenticationButton
            buttonType={AppleAuthentication.AppleAuthenticationButtonType.SIGN_IN}
            buttonStyle={AppleAuthentication.AppleAuthenticationButtonStyle.WHITE}
            cornerRadius={18}
            style={styles.appleBtn}
            onPress={handleAppleSignIn}
          />
        )}
        {GOOGLE_AVAILABLE && (
          <Pressable
            onPress={handleGoogleSignIn}
            disabled={isLoading}
            style={({ pressed }) => [
              styles.socialBtn,
              { opacity: isLoading ? 0.55 : pressed ? 0.88 : 1 },
            ]}
          >
            {loadingProvider === 'google' ? (
              <ActivityIndicator size="small" color={Colors.white} />
            ) : (
              <>
                <Text style={styles.socialBtnIcon}>G</Text>
                <Text style={styles.socialBtnText}>Continue with Google</Text>
              </>
            )}
          </Pressable>
        )}

        <Pressable
          onPress={handleEmailSignIn}
          disabled={isLoading}
          style={({ pressed }) => [
            styles.emailBtn,
            { opacity: isLoading ? 0.55 : pressed ? 0.88 : 1 },
          ]}
        >
          <Text style={styles.emailBtnIcon}>✉</Text>
          <Text style={styles.emailBtnText}>Continue with Email</Text>
        </Pressable>
      </Animated.View>

      <Animated.View style={[styles.premiumRow, formStyle]}>
        <View style={[styles.premiumBadge, { backgroundColor: colors.subtleBg, borderColor: colors.border }]}>
          <Text style={[styles.premiumText, { color: colors.textVeryFaint }]}>✦  NinoWorld Premium  ✦</Text>
        </View>
      </Animated.View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  scroll: {
    flexGrow: 1,
    alignItems: 'center',
    paddingHorizontal: 28,
    gap: 0,
  },

  blob: { position: 'absolute', borderRadius: 999 },

  titleBlock: { alignItems: 'center', gap: 6, marginBottom: 8 },
  worldBadge: {
    flexDirection: 'row', alignItems: 'center', gap: 6,
    paddingHorizontal: 14, paddingVertical: 5,
    borderRadius: 50,
    borderWidth: 1,
  },
  worldDot: { width: 6, height: 6, borderRadius: 3, backgroundColor: Colors.green },
  worldLabel: {
    fontSize: 11, fontFamily: 'Inter_700Bold',
    color: Colors.green, letterSpacing: 2.5,
  },
  gameTitle: {
    fontSize: 64, fontFamily: 'Inter_700Bold',
    letterSpacing: 10,
    textAlign: 'center',
    marginTop: 4,
  },
  gameTagline: {
    fontSize: 12, fontFamily: 'Inter_400Regular',
    letterSpacing: 2,
    textTransform: 'uppercase',
  },

  charStage: {
    width: 310, height: 220,
    alignItems: 'center', justifyContent: 'center',
    position: 'relative',
    marginVertical: 4,
  },
  charGlowLeft: {
    position: 'absolute',
    left: 0, bottom: 20,
    width: 140, height: 140, borderRadius: 70,
    backgroundColor: Colors.green,
    opacity: 0.13,
  },
  charGlowRight: {
    position: 'absolute',
    right: 0, bottom: 20,
    width: 120, height: 120, borderRadius: 60,
    backgroundColor: Colors.yellow,
    opacity: 0.11,
  },
  mascot: { width: 300, height: 210 },

  formBlock: { width: '100%', gap: 12, marginTop: 8 },
  prompt: {
    fontSize: 14, fontFamily: 'Inter_600SemiBold',
    textAlign: 'center',
    letterSpacing: 0.3,
    marginBottom: 4,
  },

  errorBox: {
    backgroundColor: Colors.coral + '20',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: Colors.coral + '40',
    paddingHorizontal: 16,
    paddingVertical: 10,
  },
  errorText: {
    fontSize: 13, fontFamily: 'Inter_500Medium',
    color: Colors.coral, textAlign: 'center',
  },

  appleBtn: {
    width: '100%',
    height: 56,
    borderRadius: 18,
  },

  socialBtn: {
    width: '100%',
    height: 56,
    borderRadius: 18,
    backgroundColor: '#4285F4',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
    shadowColor: '#4285F4',
    shadowOpacity: 0.35,
    shadowOffset: { width: 0, height: 6 },
    shadowRadius: 14,
    elevation: 6,
  },
  socialBtnIcon: {
    fontSize: 18, fontFamily: 'Inter_700Bold',
    color: Colors.white,
  },
  socialBtnText: {
    fontSize: 16, fontFamily: 'Inter_600SemiBold',
    color: Colors.white,
  },

  emailBtn: {
    width: '100%',
    height: 56,
    borderRadius: 18,
    backgroundColor: Colors.yellow,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
    shadowColor: Colors.yellow,
    shadowOpacity: 0.4,
    shadowOffset: { width: 0, height: 6 },
    shadowRadius: 14,
    elevation: 6,
  },
  emailBtnIcon: {
    fontSize: 17,
    color: Colors.textDark,
  },
  emailBtnText: {
    fontSize: 16, fontFamily: 'Inter_700Bold',
    color: Colors.textDark,
  },

  premiumRow: { alignItems: 'center', marginTop: 16 },
  premiumBadge: {
    paddingHorizontal: 18, paddingVertical: 8,
    borderRadius: 50,
    borderWidth: 1,
  },
  premiumText: {
    fontSize: 11, fontFamily: 'Inter_500Medium',
    letterSpacing: 1.5,
  },
});
