import { router } from 'expo-router';
import React, { useState } from 'react';
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
import { useTheme } from '@/src/context/ThemeContext';

export default function AuthEmailScreen() {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  const handleSubmit = async () => {
    const trimmed = email.trim().toLowerCase();
    if (!trimmed || !trimmed.includes('@')) {
      setErrorMsg('Please enter a valid email address.');
      return;
    }
    setLoading(true);
    setErrorMsg(null);
    try {
      const res = await apiFetch('/auth/otp/request', {
        method: 'POST',
        body: JSON.stringify({ email: trimmed }),
        skipAuth: true,
      });

      if (res.status === 429) {
        setErrorMsg('Too many requests. Please wait a few minutes and try again.');
        return;
      }
      if (!res.ok) {
        setErrorMsg('Failed to send code. Please try again.');
        return;
      }

      router.push({ pathname: '/auth-otp', params: { email: trimmed } });
    } catch {
      setErrorMsg('Network error. Please check your connection.');
    } finally {
      setLoading(false);
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
        <Text style={[styles.headerTitle, { color: colors.textPrimary }]}>Sign in with Email</Text>
      </View>

      <View style={styles.content}>
        <View style={[styles.iconWrapper, { backgroundColor: Colors.yellow + '18' }]}>
          <Ionicons name="mail-outline" size={48} color={Colors.yellow} />
        </View>
        <Text style={[styles.title, { color: colors.textPrimary }]}>Enter your email</Text>
        <Text style={[styles.subtitle, { color: colors.textSecondary }]}>
          We'll send a 6-digit code to sign you in. No password needed.
        </Text>

        {errorMsg && (
          <View style={styles.errorBox}>
            <Text style={styles.errorText}>{errorMsg}</Text>
          </View>
        )}

        <TextInput
          style={[styles.input, { backgroundColor: colors.inputBg, borderColor: colors.borderMid, color: colors.textPrimary }, errorMsg ? { borderColor: Colors.coral } : null]}
          placeholder="your@email.com"
          placeholderTextColor={colors.textFaint}
          value={email}
          onChangeText={(t) => {
            setEmail(t);
            if (errorMsg) setErrorMsg(null);
          }}
          keyboardType="email-address"
          autoCapitalize="none"
          autoCorrect={false}
          returnKeyType="go"
          onSubmitEditing={handleSubmit}
          selectionColor={Colors.yellow}
          autoFocus
        />

        <Pressable
          onPress={handleSubmit}
          disabled={loading || !email.trim()}
          style={({ pressed }) => [
            styles.btn,
            { opacity: loading || !email.trim() ? 0.5 : pressed ? 0.88 : 1 },
          ]}
        >
          {loading ? (
            <ActivityIndicator size="small" color={Colors.textDark} />
          ) : (
            <Text style={styles.btnText}>Send Code</Text>
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
    flex: 1, paddingHorizontal: 28, paddingTop: 32, gap: 16,
  },
  iconWrapper: {
    alignSelf: 'center',
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
    textAlign: 'center', lineHeight: 20,
    marginBottom: 4,
  },
  errorBox: {
    backgroundColor: Colors.coral + '20',
    borderRadius: 12, borderWidth: 1, borderColor: Colors.coral + '40',
    paddingHorizontal: 16, paddingVertical: 10,
  },
  errorText: {
    fontSize: 13, fontFamily: 'Inter_500Medium',
    color: Colors.coral, textAlign: 'center',
  },
  input: {
    width: '100%', height: 58, borderRadius: 18,
    borderWidth: 1.5,
    paddingHorizontal: 22,
    fontSize: 17, fontFamily: 'Inter_600SemiBold',
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
});
