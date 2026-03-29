import { Ionicons } from '@expo/vector-icons';
import * as WebBrowser from 'expo-web-browser';
import { router } from 'expo-router';
import React, { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useAuth } from '@/src/context/AuthContext';
import { useSettings } from '@/src/context/SettingsContext';
import { PremiumCard } from '@/src/components/PremiumCard';
import { apiFetch } from '@/src/services/apiClient';
import { useTheme } from '@/src/context/ThemeContext';
import { useSettingsStore } from '@/src/stores/settingsStore';
import { ThemePreference } from '@/src/types';

interface AppSettings {
  privacyPolicyUrl: string;
  termsUrl: string;
  emailTheme: 'dark' | 'light';
}

type IoniconsName = React.ComponentProps<typeof Ionicons>['name'];

interface SettingRowProps {
  icon: IoniconsName;
  iconColor: string;
  iconBg: string;
  label: string;
  value?: boolean;
  onToggle?: (v: boolean) => void;
  onPress?: () => void;
  trailing?: React.ReactNode;
}

function SettingRow({ icon, iconColor, iconBg, label, value, onToggle, onPress, trailing }: SettingRowProps) {
  const { colors } = useTheme();
  return (
    <Pressable onPress={onPress} style={styles.row}>
      <View style={[styles.rowIcon, { backgroundColor: iconBg }]}>
        <Ionicons name={icon} size={18} color={iconColor} />
      </View>
      <Text style={[styles.rowLabel, { color: colors.textPrimary }]}>{label}</Text>
      <View style={styles.rowTrailing}>
        {onToggle !== undefined && value !== undefined ? (
          <Switch
            value={value}
            onValueChange={onToggle}
            trackColor={{ false: colors.borderMid, true: Colors.green }}
            thumbColor={Colors.white}
          />
        ) : trailing ?? (
          onPress && <Ionicons name="chevron-forward" size={18} color={colors.textFaint} />
        )}
      </View>
    </Pressable>
  );
}

interface DeleteModalProps {
  visible: boolean;
  loading: boolean;
  onCancel: () => void;
  onConfirm: () => void;
}

function DeleteAccountModal({ visible, loading, onCancel, onConfirm }: DeleteModalProps) {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onCancel}>
      <Pressable style={styles.modalOverlay} onPress={loading ? undefined : onCancel}>
        <Pressable
          style={[styles.modalCard, { backgroundColor: colors.card, paddingBottom: Math.max(insets.bottom, 24) }]}
          onPress={(e) => e.stopPropagation()}
        >
          <View style={styles.modalIconWrap}>
            <Ionicons name="trash-outline" size={32} color={Colors.coral} />
          </View>

          <Text style={[styles.modalTitle, { color: colors.textPrimary }]}>Delete Account</Text>
          <Text style={[styles.modalBody, { color: colors.textSecondary }]}>
            This will permanently delete your account and{' '}
            <Text style={[styles.modalBold, { color: colors.textPrimary }]}>all of your data</Text> — matches, progress,
            tokens, and everything else.{'\n\n'}
            <Text style={[styles.modalBold, { color: colors.textPrimary }]}>This action cannot be undone.</Text>
          </Text>

          <View style={[styles.modalBullets, { backgroundColor: Colors.coral + '08' }]}>
            {['Your match history', 'All earned tokens', 'Your profile & stats', 'Challenge progress'].map((item) => (
              <View key={item} style={styles.bullet}>
                <Ionicons name="close-circle" size={15} color={Colors.coral} style={{ marginTop: 1 }} />
                <Text style={[styles.bulletText, { color: colors.textSecondary }]}>{item}</Text>
              </View>
            ))}
          </View>

          <Pressable
            style={[styles.modalDeleteBtn, loading && { opacity: 0.6 }]}
            onPress={onConfirm}
            disabled={loading}
          >
            {loading ? (
              <ActivityIndicator size="small" color={Colors.white} />
            ) : (
              <>
                <Ionicons name="trash-outline" size={18} color={Colors.white} />
                <Text style={styles.modalDeleteText}>Yes, Delete My Account</Text>
              </>
            )}
          </Pressable>

          <Pressable style={styles.modalCancelBtn} onPress={onCancel} disabled={loading}>
            <Text style={[styles.modalCancelText, { color: colors.textSecondary }]}>Cancel</Text>
          </Pressable>
        </Pressable>
      </Pressable>
    </Modal>
  );
}

const THEME_OPTIONS: { value: ThemePreference; label: string; icon: IoniconsName }[] = [
  { value: 'system', label: 'System', icon: 'phone-portrait-outline' },
  { value: 'light',  label: 'Light',  icon: 'sunny-outline' },
  { value: 'dark',   label: 'Dark',   icon: 'moon-outline' },
];

export default function SettingsScreen() {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const { profile, logout, isAdmin } = useAuth();
  const { settings, toggleSound, toggleMusic } = useSettings();
  const theme = useSettingsStore(s => s.theme);
  const setTheme = useSettingsStore(s => s.setTheme);
  const [appSettings, setAppSettings] = useState<AppSettings | null>(null);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [deletingAccount, setDeletingAccount] = useState(false);

  useEffect(() => {
    apiFetch('/app-settings')
      .then((r) => r.ok ? r.json() : null)
      .then((data) => { if (data) setAppSettings(data as AppSettings); })
      .catch(() => {});
  }, []);

  const handleOpenPolicy = (url: string) => {
    if (!url) return;
    WebBrowser.openBrowserAsync(url, {
      presentationStyle: WebBrowser.WebBrowserPresentationStyle.PAGE_SHEET,
    }).catch(() => {});
  };

  const executeDeleteAccount = async () => {
    setDeletingAccount(true);
    try {
      const res = await apiFetch('/auth/account', { method: 'DELETE' });
      if (!res.ok) throw new Error('Failed');
      await logout();
      setShowDeleteModal(false);
      router.replace('/login');
    } catch {
      setDeletingAccount(false);
      setShowDeleteModal(false);
      Alert.alert('Error', 'Could not delete your account. Please try again later.');
    }
  };

  return (
    <>
      <ScrollView
        style={[styles.container, { backgroundColor: colors.background }]}
        contentContainerStyle={[styles.content, { paddingTop: insets.top + 8, paddingBottom: insets.bottom + 32 }]}
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.toolbar}>
          <Pressable onPress={() => router.back()} style={styles.backBtn}>
            <Ionicons name="chevron-back" size={26} color={colors.textPrimary} />
          </Pressable>
          <Text style={[styles.toolbarTitle, { color: colors.textPrimary }]}>Settings</Text>
          <View style={{ width: 40 }} />
        </View>

        <PremiumCard>
          <Text style={[styles.sectionTitle, { color: colors.textMuted }]}>Account</Text>
          <SettingRow
            icon="person-circle-outline"
            iconColor={Colors.blue}
            iconBg="rgba(91,141,184,0.18)"
            label={profile?.name ?? 'Not logged in'}
            trailing={<Text style={[styles.statusBadge, { color: Colors.green }]}>Active</Text>}
          />
          <View style={[styles.divider, { backgroundColor: colors.border }]} />
          <SettingRow
            icon="create-outline"
            iconColor={Colors.purple}
            iconBg="rgba(155,127,212,0.18)"
            label="Edit Profile"
            onPress={() => router.push('/edit-profile')}
          />
          <View style={[styles.divider, { backgroundColor: colors.border }]} />
          <SettingRow
            icon="diamond-outline"
            iconColor={Colors.yellow}
            iconBg="rgba(242,184,75,0.18)"
            label="Subscription"
            trailing={<Text style={[styles.statusBadge, { color: Colors.yellow }]}>Premium</Text>}
          />
        </PremiumCard>

        {/* ── DISPLAY ── */}
        <PremiumCard>
          <Text style={[styles.sectionTitle, { color: colors.textMuted }]}>Display</Text>
          <View style={styles.row}>
            <View style={[styles.rowIcon, { backgroundColor: Colors.blue + '22' }]}>
              <Ionicons name="contrast-outline" size={18} color={Colors.blue} />
            </View>
            <Text style={[styles.rowLabel, { color: colors.textPrimary }]}>Appearance</Text>
          </View>
          <View style={[styles.themeSegment, { backgroundColor: colors.inputBg }]}>
            {THEME_OPTIONS.map((opt) => {
              const active = theme === opt.value;
              return (
                <Pressable
                  key={opt.value}
                  onPress={() => setTheme(opt.value)}
                  style={[styles.themeOption, active && { backgroundColor: colors.card, borderColor: colors.borderMid, borderWidth: 1 }]}
                >
                  <Ionicons name={opt.icon} size={15} color={active ? colors.textPrimary : colors.textMuted} />
                  <Text style={[styles.themeOptionText, { color: active ? colors.textPrimary : colors.textMuted }, active && { fontFamily: 'Inter_600SemiBold' }]}>
                    {opt.label}
                  </Text>
                </Pressable>
              );
            })}
          </View>
        </PremiumCard>

        <PremiumCard>
          <Text style={[styles.sectionTitle, { color: colors.textMuted }]}>Game</Text>
          <SettingRow
            icon="volume-high-outline"
            iconColor={Colors.green}
            iconBg="rgba(92,175,122,0.18)"
            label="Sound Effects"
            value={settings.soundEnabled}
            onToggle={toggleSound}
          />
          <View style={[styles.divider, { backgroundColor: colors.border }]} />
          <SettingRow
            icon="musical-notes-outline"
            iconColor={Colors.purple}
            iconBg="rgba(155,127,212,0.18)"
            label="Music"
            value={settings.musicEnabled}
            onToggle={toggleMusic}
          />
        </PremiumCard>

        <PremiumCard>
          <Text style={[styles.sectionTitle, { color: colors.textMuted }]}>Support</Text>
          <SettingRow
            icon="help-circle-outline"
            iconColor={Colors.blue}
            iconBg="rgba(91,141,184,0.18)"
            label="Help & Support"
            onPress={() => router.push('/help-support')}
          />
          <View style={[styles.divider, { backgroundColor: colors.border }]} />
          <SettingRow
            icon="information-circle-outline"
            iconColor={colors.textSecondary}
            iconBg={colors.inputBg}
            label="About Nino XO"
            onPress={() => router.push('/about')}
          />
          <View style={[styles.divider, { backgroundColor: colors.border }]} />
          <SettingRow
            icon="notifications-outline"
            iconColor={Colors.coral}
            iconBg="rgba(240,128,96,0.18)"
            label="Notifications"
            onPress={() => router.push('/notifications')}
          />
          <View style={[styles.divider, { backgroundColor: colors.border }]} />
          <SettingRow
            icon="shield-checkmark-outline"
            iconColor={Colors.green}
            iconBg="rgba(92,175,122,0.18)"
            label="Privacy Policy"
            onPress={() => handleOpenPolicy(appSettings?.privacyPolicyUrl ?? '')}
          />
          <View style={[styles.divider, { backgroundColor: colors.border }]} />
          <SettingRow
            icon="document-text-outline"
            iconColor={Colors.blue}
            iconBg="rgba(91,141,184,0.18)"
            label="Terms of Use"
            onPress={() => handleOpenPolicy(appSettings?.termsUrl ?? '')}
          />
        </PremiumCard>

        {isAdmin && (
          <PremiumCard>
            <Text style={[styles.sectionTitle, { color: colors.textMuted }]}>Admin</Text>
            <SettingRow
              icon="people-outline"
              iconColor={Colors.purple}
              iconBg="rgba(155,127,212,0.18)"
              label="Manage Users"
              onPress={() => router.push('/admin-users')}
            />
            <View style={[styles.divider, { backgroundColor: colors.border }]} />
            <SettingRow
              icon="settings-outline"
              iconColor={Colors.yellow}
              iconBg="rgba(242,184,75,0.18)"
              label="App Settings"
              onPress={() => router.push('/admin-app-settings')}
            />
          </PremiumCard>
        )}

        <Pressable
          onPress={() => setShowDeleteModal(true)}
          style={styles.deleteBtn}
        >
          <Ionicons name="trash-outline" size={16} color={colors.textMuted} />
          <Text style={[styles.deleteText, { color: colors.textMuted }]}>Delete Account</Text>
        </Pressable>

        <Text style={[styles.footer, { color: colors.textVeryFaint }]}>Nino XO · Part of NinoWorld · v1.0.0</Text>
      </ScrollView>

      <DeleteAccountModal
        visible={showDeleteModal}
        loading={deletingAccount}
        onCancel={() => { if (!deletingAccount) setShowDeleteModal(false); }}
        onConfirm={executeDeleteAccount}
      />
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  content: { paddingHorizontal: 20, gap: 16 },
  toolbar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingBottom: 8,
  },
  backBtn: { width: 40, height: 40, alignItems: 'center', justifyContent: 'center' },
  toolbarTitle: { fontSize: 17, fontFamily: 'Inter_600SemiBold' },
  sectionTitle: { fontSize: 13, fontFamily: 'Inter_600SemiBold', letterSpacing: 0.8, textTransform: 'uppercase', marginBottom: 12 },
  row: { flexDirection: 'row', alignItems: 'center', gap: 12, paddingVertical: 6 },
  rowIcon: { width: 36, height: 36, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
  rowLabel: { flex: 1, fontSize: 15, fontFamily: 'Inter_400Regular' },
  rowTrailing: { alignItems: 'flex-end' },
  statusBadge: { fontSize: 13, fontFamily: 'Inter_600SemiBold' },
  divider: { height: 1, marginVertical: 8 },

  themeSegment: {
    flexDirection: 'row',
    borderRadius: 12,
    padding: 4,
    marginTop: 10,
    gap: 4,
  },
  themeOption: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 5,
    paddingVertical: 10,
    borderRadius: 9,
  },
  themeOptionText: {
    fontSize: 13,
    fontFamily: 'Inter_500Medium',
  },

  deleteBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    paddingVertical: 12,
  },
  deleteText: {
    fontSize: 13,
    fontFamily: 'Inter_400Regular',
    textDecorationLine: 'underline',
  },
  footer: { textAlign: 'center', fontSize: 12, fontFamily: 'Inter_400Regular', marginTop: 2 },

  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.72)',
    justifyContent: 'flex-end',
  },
  modalCard: {
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    paddingTop: 28,
    paddingHorizontal: 24,
    gap: 0,
  },
  modalIconWrap: {
    width: 64,
    height: 64,
    borderRadius: 20,
    backgroundColor: 'rgba(240,128,96,0.15)',
    alignItems: 'center',
    justifyContent: 'center',
    alignSelf: 'center',
    marginBottom: 18,
  },
  modalTitle: {
    fontSize: 20,
    fontFamily: 'Inter_700Bold',
    textAlign: 'center',
    marginBottom: 12,
  },
  modalBody: {
    fontSize: 14,
    fontFamily: 'Inter_400Regular',
    textAlign: 'center',
    lineHeight: 22,
    marginBottom: 20,
  },
  modalBold: {
    fontFamily: 'Inter_600SemiBold',
  },
  modalBullets: {
    borderRadius: 14,
    padding: 16,
    gap: 10,
    marginBottom: 24,
  },
  bullet: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 10,
  },
  bulletText: {
    fontSize: 14,
    fontFamily: 'Inter_400Regular',
    flex: 1,
  },
  modalDeleteBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: Colors.coral,
    borderRadius: 16,
    paddingVertical: 15,
    marginBottom: 12,
  },
  modalDeleteText: {
    fontSize: 16,
    fontFamily: 'Inter_600SemiBold',
    color: Colors.white,
  },
  modalCancelBtn: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 14,
    marginBottom: 4,
  },
  modalCancelText: {
    fontSize: 16,
    fontFamily: 'Inter_500Medium',
  },
});
