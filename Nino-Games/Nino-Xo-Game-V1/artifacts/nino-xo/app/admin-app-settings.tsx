import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import React, { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  TextInput,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useAuth } from '@/src/context/AuthContext';
import { useTheme } from '@/src/context/ThemeContext';
import { apiFetch } from '@/src/services/apiClient';

interface AppSettings {
  privacyPolicyUrl: string;
  termsUrl: string;
  emailTheme: 'dark' | 'light';
}

export default function AdminAppSettingsScreen() {
  const insets = useSafeAreaInsets();
  const { isAdmin } = useAuth();
  const { colors } = useTheme();

  const [privacyPolicyUrl, setPrivacyPolicyUrl] = useState('');
  const [termsUrl, setTermsUrl] = useState('');
  const [emailTheme, setEmailTheme] = useState<'dark' | 'light'>('dark');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (!isAdmin) router.replace('/(tabs)/me');
  }, [isAdmin]);

  useEffect(() => {
    if (!isAdmin) return;
    const load = async () => {
      try {
        const res = await apiFetch('/app-settings');
        if (!res.ok) throw new Error('Failed to load');
        const data = await res.json() as AppSettings;
        setPrivacyPolicyUrl(data.privacyPolicyUrl ?? '');
        setTermsUrl(data.termsUrl ?? '');
        setEmailTheme(data.emailTheme === 'light' ? 'light' : 'dark');
      } catch {
        Alert.alert('Error', 'Could not load settings');
      } finally {
        setLoading(false);
      }
    };
    load();
  }, [isAdmin]);

  const handleSave = async () => {
    setSaving(true);
    try {
      const res = await apiFetch('/admin/app-settings', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ privacyPolicyUrl, termsUrl, emailTheme }),
      });
      if (!res.ok) {
        const err = await res.json().catch(() => ({})) as { error?: string };
        Alert.alert('Error', err.error ?? 'Failed to save');
        return;
      }
      Alert.alert('Saved', 'App settings updated successfully.');
    } catch {
      Alert.alert('Error', 'Request failed');
    } finally {
      setSaving(false);
    }
  };

  if (!isAdmin) {
    return (
      <View style={[styles.container, styles.center, { backgroundColor: colors.background }]}>
        <Ionicons name="lock-closed-outline" size={48} color={colors.textFaint} />
        <Text style={[styles.accessDenied, { color: colors.textMuted }]}>Access denied</Text>
      </View>
    );
  }

  if (loading) {
    return (
      <View style={[styles.container, styles.center, { backgroundColor: colors.background }]}>
        <ActivityIndicator color={Colors.purple} size="large" />
      </View>
    );
  }

  return (
    <View style={[styles.container, { backgroundColor: colors.background, paddingTop: insets.top }]}>
      <View style={[styles.header, { borderBottomColor: colors.border }]}>
        <Pressable onPress={() => router.back()} style={[styles.backBtn, { backgroundColor: colors.inputBg }]}>
          <Ionicons name="arrow-back" size={22} color={colors.textPrimary} />
        </Pressable>
        <View style={{ flex: 1 }}>
          <Text style={[styles.headerTitle, { color: colors.textPrimary }]}>Admin: App Settings</Text>
          <Text style={[styles.headerSub, { color: colors.textMuted }]}>Policy URLs & email theme</Text>
        </View>
      </View>

      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={[styles.content, { paddingBottom: insets.bottom + 32 }]}
      >
        <View style={[styles.card, { backgroundColor: colors.card, borderColor: colors.border }]}>
          <Text style={[styles.sectionTitle, { color: colors.textMuted }]}>Legal Pages</Text>
          <Text style={[styles.fieldLabel, { color: colors.textSecondary }]}>Privacy Policy URL</Text>
          <TextInput
            style={[styles.input, { backgroundColor: colors.inputBg, borderColor: colors.border, color: colors.textPrimary }]}
            value={privacyPolicyUrl}
            onChangeText={setPrivacyPolicyUrl}
            placeholder="https://yoursite.com/privacy"
            placeholderTextColor={colors.textFaint}
            autoCapitalize="none"
            keyboardType="url"
          />
          <Text style={[styles.fieldLabel, { marginTop: 12, color: colors.textSecondary }]}>Terms of Use URL</Text>
          <TextInput
            style={[styles.input, { backgroundColor: colors.inputBg, borderColor: colors.border, color: colors.textPrimary }]}
            value={termsUrl}
            onChangeText={setTermsUrl}
            placeholder="https://yoursite.com/terms"
            placeholderTextColor={colors.textFaint}
            autoCapitalize="none"
            keyboardType="url"
          />
          <Text style={[styles.hint, { color: colors.textFaint }]}>
            Leave blank to hide the corresponding row in the Settings screen.
          </Text>
        </View>

        <View style={[styles.card, { backgroundColor: colors.card, borderColor: colors.border }]}>
          <Text style={[styles.sectionTitle, { color: colors.textMuted }]}>Email Theme</Text>
          <View style={styles.themeRow}>
            <View style={{ flex: 1 }}>
              <Text style={[styles.themeLabel, { color: colors.textPrimary }]}>
                {emailTheme === 'dark' ? 'Dark Mode' : 'Light Mode'}
              </Text>
              <Text style={[styles.themeDesc, { color: colors.textMuted }]}>
                {emailTheme === 'dark'
                  ? 'Deep navy background with gold code (current default)'
                  : 'White card with purple accents'}
              </Text>
            </View>
            <Switch
              value={emailTheme === 'light'}
              onValueChange={(v) => setEmailTheme(v ? 'light' : 'dark')}
              trackColor={{ false: Colors.purple + '50', true: Colors.purple }}
              thumbColor={Colors.white}
            />
          </View>
          <View style={styles.themePreviews}>
            <View style={[styles.themeChip, emailTheme === 'dark' && styles.themeChipActive, { backgroundColor: '#16162a' }]}>
              <Text style={[styles.themeChipLabel, { color: '#ffffff' }]}>Dark</Text>
            </View>
            <View style={[styles.themeChip, emailTheme === 'light' && styles.themeChipActive, { backgroundColor: '#f0f0f8' }]}>
              <Text style={[styles.themeChipLabel, { color: '#1a0060' }]}>Light</Text>
            </View>
          </View>
        </View>

        <Pressable
          onPress={handleSave}
          style={[styles.saveBtn, saving && { opacity: 0.7 }]}
          disabled={saving}
        >
          {saving ? (
            <ActivityIndicator size="small" color={Colors.white} />
          ) : (
            <>
              <Ionicons name="checkmark-circle-outline" size={18} color={Colors.white} />
              <Text style={styles.saveBtnText}>Save Settings</Text>
            </>
          )}
        </Pressable>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 12 },
  accessDenied: { fontSize: 16, fontFamily: 'Inter_500Medium' },

  header: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderBottomWidth: 1,
  },
  backBtn: {
    width: 36, height: 36,
    alignItems: 'center', justifyContent: 'center',
    borderRadius: 10,
  },
  headerTitle: { fontSize: 16, fontFamily: 'Inter_700Bold' },
  headerSub: { fontSize: 12, fontFamily: 'Inter_400Regular' },

  content: { paddingHorizontal: 16, paddingTop: 16, gap: 16 },

  card: {
    borderRadius: 18,
    borderWidth: 1,
    padding: 16,
  },
  sectionTitle: {
    fontSize: 12,
    fontFamily: 'Inter_600SemiBold',
    textTransform: 'uppercase',
    letterSpacing: 0.8,
    marginBottom: 14,
  },

  fieldLabel: {
    fontSize: 13,
    fontFamily: 'Inter_500Medium',
    marginBottom: 6,
  },
  input: {
    borderRadius: 12,
    borderWidth: 1,
    paddingHorizontal: 14,
    paddingVertical: 11,
    fontSize: 14,
    fontFamily: 'Inter_400Regular',
  },
  hint: {
    fontSize: 11,
    fontFamily: 'Inter_400Regular',
    marginTop: 10,
  },

  themeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 14,
  },
  themeLabel: { fontSize: 15, fontFamily: 'Inter_600SemiBold' },
  themeDesc: { fontSize: 12, fontFamily: 'Inter_400Regular', marginTop: 2 },

  themePreviews: { flexDirection: 'row', gap: 10 },
  themeChip: {
    flex: 1,
    paddingVertical: 20,
    alignItems: 'center',
    borderRadius: 12,
    borderWidth: 2,
    borderColor: 'transparent',
  },
  themeChipActive: { borderColor: Colors.purple },
  themeChipLabel: { fontSize: 13, fontFamily: 'Inter_600SemiBold' },

  saveBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: Colors.purple,
    borderRadius: 18,
    paddingVertical: 16,
  },
  saveBtnText: { fontSize: 16, fontFamily: 'Inter_600SemiBold', color: Colors.white },
});
