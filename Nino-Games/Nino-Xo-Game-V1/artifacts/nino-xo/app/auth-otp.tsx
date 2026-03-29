import { router, useLocalSearchParams } from 'expo-router';
import React, { useEffect, useRef, useState } from 'react';
import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { Colors } from '@/constants/colors';
import { apiFetch } from '@/src/services/apiClient';
import { useAuth } from '@/src/context/AuthContext';
import { UserProfile } from '@/src/types';
import { useTheme } from '@/src/context/ThemeContext';

const CODE_LENGTH = 6;

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

export default function AuthOtpScreen() {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const { email } = useLocalSearchParams<{ email: string }>();
  const { loginWithToken } = useAuth();
  const [digits, setDigits] = useState<string[]>(Array(CODE_LENGTH).fill(''));
  const [loading, setLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const [resending, setResending] = useState(false);
  const [resendCooldown, setResendCooldown] = useState(60);
  const inputRefs = useRef<(TextInput | null)[]>(Array(CODE_LENGTH).fill(null));
  const cooldownRef = useRef<ReturnType<typeof setInterval> | null>(null);
  const verifyInFlightRef = useRef(false);

  useEffect(() => {
    startCooldown();
    return () => {
      if (cooldownRef.current) clearInterval(cooldownRef.current);
    };
  }, []);

  useEffect(() => {
    const code = digits.join('');
    if (code.length === CODE_LENGTH && !digits.includes('')) {
      handleVerify(code);
    }
  }, [digits]);

  function startCooldown() {
    setResendCooldown(60);
    if (cooldownRef.current) clearInterval(cooldownRef.current);
    cooldownRef.current = setInterval(() => {
      setResendCooldown((c) => {
        if (c <= 1) {
          if (cooldownRef.current) clearInterval(cooldownRef.current);
          return 0;
        }
        return c - 1;
      });
    }, 1000);
  }

  const handleDigitChange = (value: string, index: number) => {
    const cleaned = value.replace(/[^0-9]/g, '');

    if (cleaned.length > 1) {
      const pasted = cleaned.slice(0, CODE_LENGTH);
      const newDigits = [...digits];
      for (let i = 0; i < pasted.length; i++) {
        if (index + i < CODE_LENGTH) newDigits[index + i] = pasted[i];
      }
      setDigits(newDigits);
      const nextIndex = Math.min(index + pasted.length, CODE_LENGTH - 1);
      inputRefs.current[nextIndex]?.focus();
      return;
    }

    const newDigits = [...digits];
    newDigits[index] = cleaned;
    setDigits(newDigits);

    if (cleaned && index < CODE_LENGTH - 1) {
      inputRefs.current[index + 1]?.focus();
    }
  };

  const handleKeyPress = (key: string, index: number) => {
    if (key === 'Backspace' && !digits[index] && index > 0) {
      const newDigits = [...digits];
      newDigits[index - 1] = '';
      setDigits(newDigits);
      inputRefs.current[index - 1]?.focus();
    }
  };

  const handleVerify = async (code: string) => {
    if (verifyInFlightRef.current) return;
    verifyInFlightRef.current = true;
    setLoading(true);
    setErrorMsg(null);
    let succeeded = false;
    try {
      const res = await apiFetch('/auth/otp/verify', {
        method: 'POST',
        body: JSON.stringify({ email, code }),
        skipAuth: true,
      });

      if (res.status === 429) {
        setErrorMsg('Too many failed attempts. Please request a new code.');
        setDigits(Array(CODE_LENGTH).fill(''));
        inputRefs.current[0]?.focus();
        return;
      }
      if (res.status === 401 || res.status === 400) {
        setErrorMsg('Invalid or expired code. Please check and try again.');
        setDigits(Array(CODE_LENGTH).fill(''));
        inputRefs.current[0]?.focus();
        return;
      }
      if (!res.ok) {
        setErrorMsg('Verification failed. Please try again.');
        setDigits(Array(CODE_LENGTH).fill(''));
        inputRefs.current[0]?.focus();
        return;
      }

      const data = await res.json() as ServerAuthResponse;
      const profile = buildProfileFromServer(data);
      await loginWithToken(data.token, profile);
      succeeded = true;
      router.replace('/session-restore');
    } catch {
      setErrorMsg('Network error. Please check your connection.');
      setDigits(Array(CODE_LENGTH).fill(''));
      inputRefs.current[0]?.focus();
    } finally {
      if (!succeeded) {
        verifyInFlightRef.current = false;
        setLoading(false);
      }
    }
  };

  const handleResend = async () => {
    if (resendCooldown > 0 || resending) return;
    setResending(true);
    setErrorMsg(null);
    try {
      const res = await apiFetch('/auth/otp/request', {
        method: 'POST',
        body: JSON.stringify({ email }),
        skipAuth: true,
      });
      if (res.status === 429) {
        setErrorMsg('Too many requests. Please wait before resending.');
      } else if (!res.ok) {
        setErrorMsg('Failed to resend code. Please try again.');
      } else {
        setDigits(Array(CODE_LENGTH).fill(''));
        inputRefs.current[0]?.focus();
        startCooldown();
      }
    } catch {
      setErrorMsg('Network error. Please check your connection.');
    } finally {
      setResending(false);
    }
  };

  return (
    <KeyboardAvoidingView
      style={[styles.container, { paddingTop: insets.top, backgroundColor: colors.background }]}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <View style={styles.header}>
        <Pressable onPress={() => router.back()} style={[styles.backBtn, { backgroundColor: colors.inputBg }]}>
          <Ionicons name="arrow-back" size={22} color={colors.textPrimary} />
        </Pressable>
        <Text style={[styles.headerTitle, { color: colors.textPrimary }]}>Check your email</Text>
      </View>

      <View style={styles.content}>
        <View style={[styles.iconWrapper, { backgroundColor: Colors.green + '18' }]}>
          <Ionicons name="shield-checkmark-outline" size={48} color={Colors.green} />
        </View>
        <Text style={[styles.title, { color: colors.textPrimary }]}>Enter the code</Text>
        <Text style={[styles.subtitle, { color: colors.textSecondary }]}>
          We sent a 6-digit code to{'\n'}
          <Text style={[styles.emailHighlight, { color: colors.textPrimary }]}>{email}</Text>
        </Text>

        {errorMsg && (
          <View style={styles.errorBox}>
            <Text style={styles.errorText}>{errorMsg}</Text>
          </View>
        )}

        <View style={styles.codeRow}>
          {Array.from({ length: CODE_LENGTH }).map((_, i) => (
            <TextInput
              key={i}
              ref={(el) => { inputRefs.current[i] = el; }}
              style={[
                styles.digitBox,
                { backgroundColor: colors.inputBg, borderColor: colors.borderMid, color: colors.textPrimary },
                digits[i] ? { borderColor: Colors.yellow, backgroundColor: Colors.yellow + '15' } : null,
                errorMsg ? { borderColor: Colors.coral } : null,
              ]}
              value={digits[i]}
              onChangeText={(v) => handleDigitChange(v, i)}
              onKeyPress={({ nativeEvent }) => handleKeyPress(nativeEvent.key, i)}
              keyboardType="number-pad"
              maxLength={CODE_LENGTH}
              selectTextOnFocus
              textAlign="center"
              selectionColor={Colors.yellow}
              editable={!loading}
              autoFocus={i === 0}
            />
          ))}
        </View>

        <Pressable
          onPress={() => handleVerify(digits.join(''))}
          disabled={loading || digits.join('').length < CODE_LENGTH}
          style={({ pressed }) => [
            styles.btn,
            { opacity: loading || digits.join('').length < CODE_LENGTH ? 0.5 : pressed ? 0.88 : 1 },
          ]}
        >
          {loading ? (
            <ActivityIndicator size="small" color={Colors.textDark} />
          ) : (
            <Text style={styles.btnText}>Verify Code</Text>
          )}
        </Pressable>

        <Pressable
          onPress={handleResend}
          disabled={resendCooldown > 0 || resending}
          style={styles.resendBtn}
        >
          {resending ? (
            <ActivityIndicator size="small" color={Colors.purple} />
          ) : (
            <Text style={[styles.resendText, resendCooldown > 0 && { color: colors.textFaint }]}>
              {resendCooldown > 0
                ? `Resend code in ${resendCooldown}s`
                : 'Resend code'}
            </Text>
          )}
        </Pressable>
      </View>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  header: {
    flexDirection: 'row', alignItems: 'center', gap: 14,
    paddingHorizontal: 16, paddingVertical: 14,
  },
  backBtn: {
    width: 38, height: 38, borderRadius: 12,
    alignItems: 'center', justifyContent: 'center',
  },
  headerTitle: {
    fontSize: 17, fontFamily: 'Inter_600SemiBold',
  },
  content: {
    flex: 1, paddingHorizontal: 28, paddingTop: 32, gap: 18, alignItems: 'center',
  },
  iconWrapper: {
    width: 88, height: 88, borderRadius: 24,
    alignItems: 'center', justifyContent: 'center',
    marginBottom: 4,
  },
  title: {
    fontSize: 24, fontFamily: 'Inter_700Bold',
    textAlign: 'center',
  },
  subtitle: {
    fontSize: 14, fontFamily: 'Inter_400Regular',
    textAlign: 'center', lineHeight: 22,
    marginBottom: 4,
  },
  emailHighlight: {
    fontFamily: 'Inter_600SemiBold',
  },
  errorBox: {
    backgroundColor: Colors.coral + '20',
    borderRadius: 12, borderWidth: 1, borderColor: Colors.coral + '40',
    paddingHorizontal: 16, paddingVertical: 10, width: '100%',
  },
  errorText: {
    fontSize: 13, fontFamily: 'Inter_500Medium',
    color: Colors.coral, textAlign: 'center',
  },
  codeRow: {
    flexDirection: 'row', gap: 10, marginVertical: 4,
  },
  digitBox: {
    width: 46, height: 56, borderRadius: 14,
    borderWidth: 1.5,
    fontSize: 22, fontFamily: 'Inter_700Bold',
  },
  btn: {
    width: '100%', backgroundColor: Colors.yellow,
    borderRadius: 18, paddingVertical: 18,
    alignItems: 'center', justifyContent: 'center',
    shadowColor: Colors.yellow, shadowOpacity: 0.35,
    shadowOffset: { width: 0, height: 6 }, shadowRadius: 14,
    elevation: 6,
  },
  btnText: {
    fontSize: 17, fontFamily: 'Inter_700Bold',
    color: Colors.textDark, letterSpacing: 0.5,
  },
  resendBtn: { paddingVertical: 10 },
  resendText: {
    fontSize: 14, fontFamily: 'Inter_500Medium',
    color: Colors.purple, textAlign: 'center',
  },
});
