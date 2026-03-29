import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import * as Localization from 'expo-localization';
import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
  Animated,
  FlatList,
  KeyboardAvoidingView,
  Modal,
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
import { NinoButton } from '@/src/components/NinoButton';
import { PremiumCard } from '@/src/components/PremiumCard';
import { COUNTRIES, getFlag, getCountryByIso, getCountryByRegion, type Country } from '@/src/data/countries';
import { useTheme } from '@/src/context/ThemeContext';

export default function EditProfileScreen() {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const { profile, updateName, updateEmail, updatePassword, updatePhoneNumber, updateCountry } = useAuth();

  const [name, setName] = useState(profile?.name ?? '');
  const [email, setEmail] = useState(profile?.email ?? '');
  const [currentPassword, setCurrentPassword] = useState(profile?.password ?? '');
  const [newPassword, setNewPassword] = useState('');
  const [showCurrentPwd, setShowCurrentPwd] = useState(false);
  const [showNewPwd, setShowNewPwd] = useState(false);
  const [saving, setSaving] = useState(false);
  const [nameError, setNameError] = useState<string | null>(null);
  const [emailError, setEmailError] = useState<string | null>(null);

  const [toast, setToast] = useState<{ message: string; type: 'success' | 'error' } | null>(null);
  const toastAnim = useRef(new Animated.Value(0)).current;
  const toastTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  const showToastMsg = (type: 'success' | 'error', message: string) => {
    if (toastTimer.current) clearTimeout(toastTimer.current);
    setToast({ type, message });
    toastAnim.setValue(0);
    Animated.sequence([
      Animated.timing(toastAnim, { toValue: 1, duration: 260, useNativeDriver: true }),
      Animated.delay(2400),
      Animated.timing(toastAnim, { toValue: 0, duration: 300, useNativeDriver: true }),
    ]).start(() => {
      setToast(null);
    });
    toastTimer.current = setTimeout(() => { toastTimer.current = null; }, 3200);
  };

  const defaultCountry = useMemo(() => {
    if (profile?.country) {
      return getCountryByIso(profile.country) ?? getCountryByRegion(Localization.getLocales()[0]?.regionCode);
    }
    return getCountryByRegion(Localization.getLocales()[0]?.regionCode);
  }, [profile?.country]);

  const [selectedCountry, setSelectedCountry] = useState<Country>(defaultCountry);
  const [phoneDialCountry, setPhoneDialCountry] = useState<Country>(defaultCountry);
  const [phoneLocal, setPhoneLocal] = useState<string>(() => {
    if (profile?.phoneNumber) {
      const dial = defaultCountry.dial;
      if (profile.phoneNumber.startsWith(dial)) {
        return profile.phoneNumber.slice(dial.length).trim();
      }
    }
    return '';
  });

  const [countryPickerVisible, setCountryPickerVisible] = useState(false);
  const [dialPickerVisible, setDialPickerVisible] = useState(false);
  const [countrySearch, setCountrySearch] = useState('');
  const [dialSearch, setDialSearch] = useState('');

  const filteredCountriesMain = useMemo(
    () => COUNTRIES.filter((c) =>
      c.name.toLowerCase().includes(countrySearch.toLowerCase()) ||
      c.iso.toLowerCase().includes(countrySearch.toLowerCase())
    ),
    [countrySearch],
  );

  const filteredCountriesDial = useMemo(
    () => COUNTRIES.filter((c) =>
      c.name.toLowerCase().includes(dialSearch.toLowerCase()) ||
      c.iso.toLowerCase().includes(dialSearch.toLowerCase()) ||
      c.dial.includes(dialSearch)
    ),
    [dialSearch],
  );

  useEffect(() => {
    if (!profile) router.replace('/login');
  }, [profile]);

  if (!profile) return null;

  const handleSave = async () => {
    if (!name.trim()) {
      showToastMsg('error', 'Display name cannot be empty.');
      return;
    }

    if (newPassword) {
      if (newPassword.length < 6) {
        showToastMsg('error', 'New password must be at least 6 characters.');
        return;
      }
      if (profile.password && currentPassword !== profile.password) {
        showToastMsg('error', 'Current password is incorrect.');
        return;
      }
    }

    setSaving(true);
    setNameError(null);
    setEmailError(null);

    if (name.trim() !== profile.name) {
      const result = await updateName(name.trim());
      if (!result.ok) {
        setSaving(false);
        const msg = result.error === 'name_taken'
          ? 'That name is already taken.'
          : 'Failed to update name. Try again.';
        setNameError(msg);
        showToastMsg('error', msg);
        return;
      }
    }

    if (email.trim() && email.trim() !== profile.email) {
      const result = await updateEmail(email.trim());
      if (!result.ok) {
        setSaving(false);
        const msg = result.error === 'email_taken'
          ? 'That email is already in use.'
          : 'Failed to update email. Try again.';
        setEmailError(msg);
        showToastMsg('error', msg);
        return;
      }
    }

    if (newPassword) {
      updatePassword(newPassword);
    }

    const fullPhone = phoneLocal.trim()
      ? `${phoneDialCountry.dial}${phoneLocal.trim()}`
      : '';
    if (fullPhone !== (profile.phoneNumber ?? '')) {
      updatePhoneNumber(fullPhone);
    }

    if (selectedCountry.iso !== (profile.country ?? '')) {
      updateCountry(selectedCountry.iso);
    }

    setSaving(false);
    showToastMsg('success', 'Profile saved successfully!');
    setTimeout(() => router.back(), 1800);
  };

  return (
    <KeyboardAvoidingView
      style={{ flex: 1, backgroundColor: colors.background }}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView
        style={[styles.container, { backgroundColor: colors.background }]}
        contentContainerStyle={[
          styles.content,
          { paddingTop: insets.top + 8, paddingBottom: insets.bottom + 40 },
        ]}
        showsVerticalScrollIndicator={false}
        keyboardShouldPersistTaps="handled"
      >
        <View style={styles.toolbar}>
          <Pressable onPress={() => router.back()} style={styles.backBtn}>
            <Ionicons name="chevron-back" size={26} color={colors.textPrimary} />
          </Pressable>
          <Text style={[styles.toolbarTitle, { color: colors.textPrimary }]}>Edit Profile</Text>
          <View style={{ width: 40 }} />
        </View>

        {/* ── DISPLAY INFO ── */}
        <PremiumCard>
          <Text style={[styles.sectionTitle, { color: colors.textMuted }]}>Display Info</Text>

          <Text style={[styles.label, { color: colors.textMuted }]}>Display Name</Text>
          <View style={[
            styles.inputRow,
            { backgroundColor: colors.inputBg, borderColor: colors.border },
            nameError ? { borderColor: Colors.coral + '80' } : null,
          ]}>
            <Ionicons name="person-outline" size={18} color={colors.textFaint} />
            <TextInput
              style={[styles.input, { color: colors.textPrimary }]}
              value={name}
              onChangeText={(t) => { setName(t); setNameError(null); }}
              placeholder="Your name"
              placeholderTextColor={colors.textVeryFaint}
              autoCapitalize="words"
              autoCorrect={false}
            />
          </View>
          {nameError && <Text style={styles.fieldError}>{nameError}</Text>}

          <View style={[styles.divider, { backgroundColor: colors.border }]} />

          <Text style={[styles.label, { color: colors.textMuted }]}>Email Address</Text>
          <View style={[
            styles.inputRow,
            { backgroundColor: colors.inputBg, borderColor: colors.border },
            emailError ? { borderColor: Colors.coral + '80' } : null,
          ]}>
            <Ionicons name="mail-outline" size={18} color={colors.textFaint} />
            <TextInput
              style={[styles.input, { color: colors.textPrimary }]}
              value={email}
              onChangeText={(t) => { setEmail(t); setEmailError(null); }}
              placeholder="you@example.com"
              placeholderTextColor={colors.textVeryFaint}
              keyboardType="email-address"
              autoCapitalize="none"
              autoCorrect={false}
            />
          </View>
          {emailError && <Text style={styles.fieldError}>{emailError}</Text>}
        </PremiumCard>

        {/* ── LOCATION ── */}
        <PremiumCard>
          <Text style={[styles.sectionTitle, { color: colors.textMuted }]}>Location</Text>

          <Text style={[styles.label, { color: colors.textMuted }]}>Country</Text>
          <Pressable
            style={[styles.inputRow, { backgroundColor: colors.inputBg, borderColor: colors.border }]}
            onPress={() => { setCountrySearch(''); setCountryPickerVisible(true); }}
          >
            <Text style={styles.flagText}>{getFlag(selectedCountry.iso)}</Text>
            <Text style={[styles.pickerValue, { color: colors.textPrimary }]}>{selectedCountry.name}</Text>
            <Ionicons name="chevron-down" size={16} color={colors.textFaint} />
          </Pressable>

          <View style={[styles.divider, { backgroundColor: colors.border }]} />

          <Text style={[styles.label, { color: colors.textMuted }]}>Phone Number</Text>
          <View style={[styles.inputRow, { backgroundColor: colors.inputBg, borderColor: colors.border }]}>
            <Pressable
              style={styles.dialBtn}
              onPress={() => { setDialSearch(''); setDialPickerVisible(true); }}
            >
              <Text style={styles.flagText}>{getFlag(phoneDialCountry.iso)}</Text>
              <Text style={[styles.dialCode, { color: colors.textPrimary }]}>{phoneDialCountry.dial}</Text>
              <Ionicons name="chevron-down" size={13} color={colors.textFaint} />
            </Pressable>
            <View style={[styles.dialDivider, { backgroundColor: colors.borderMid }]} />
            <TextInput
              style={[styles.input, { flex: 1, color: colors.textPrimary }]}
              value={phoneLocal}
              onChangeText={setPhoneLocal}
              placeholder="Phone number"
              placeholderTextColor={colors.textVeryFaint}
              keyboardType="phone-pad"
              autoCapitalize="none"
              autoCorrect={false}
            />
          </View>
          <Text style={[styles.hint, { color: colors.textVeryFaint }]}>
            International format · e.g. 7911 123456
          </Text>
        </PremiumCard>

        {/* ── CHANGE PASSWORD ── */}
        <PremiumCard>
          <Text style={[styles.sectionTitle, { color: colors.textMuted }]}>Change Password</Text>
          <Text style={[styles.hint, { color: colors.textVeryFaint }]}>Leave blank to keep your current password.</Text>

          <Text style={[styles.label, { color: colors.textMuted }]}>Current Password</Text>
          <View style={[styles.inputRow, { backgroundColor: colors.inputBg, borderColor: colors.border }]}>
            <Ionicons name="lock-closed-outline" size={18} color={colors.textFaint} />
            <TextInput
              style={[styles.input, { flex: 1, color: colors.textPrimary }]}
              value={currentPassword}
              onChangeText={setCurrentPassword}
              placeholder="Current password"
              placeholderTextColor={colors.textVeryFaint}
              secureTextEntry={!showCurrentPwd}
              autoCapitalize="none"
            />
            <Pressable onPress={() => setShowCurrentPwd(v => !v)} hitSlop={8}>
              <Ionicons
                name={showCurrentPwd ? 'eye-off-outline' : 'eye-outline'}
                size={18}
                color={colors.textFaint}
              />
            </Pressable>
          </View>

          <View style={[styles.divider, { backgroundColor: colors.border }]} />

          <Text style={[styles.label, { color: colors.textMuted }]}>New Password</Text>
          <View style={[styles.inputRow, { backgroundColor: colors.inputBg, borderColor: colors.border }]}>
            <Ionicons name="lock-open-outline" size={18} color={colors.textFaint} />
            <TextInput
              style={[styles.input, { flex: 1, color: colors.textPrimary }]}
              value={newPassword}
              onChangeText={setNewPassword}
              placeholder="New password (min 6 chars)"
              placeholderTextColor={colors.textVeryFaint}
              secureTextEntry={!showNewPwd}
              autoCapitalize="none"
            />
            <Pressable onPress={() => setShowNewPwd(v => !v)} hitSlop={8}>
              <Ionicons
                name={showNewPwd ? 'eye-off-outline' : 'eye-outline'}
                size={18}
                color={colors.textFaint}
              />
            </Pressable>
          </View>
        </PremiumCard>

        <NinoButton
          label={saving ? 'Saving…' : 'Save Changes'}
          onPress={handleSave}
          color={Colors.green}
          fullWidth
        />
      </ScrollView>

      {/* ── COUNTRY PICKER MODAL ── */}
      <CountryPickerModal
        visible={countryPickerVisible}
        search={countrySearch}
        onSearchChange={setCountrySearch}
        countries={filteredCountriesMain}
        onSelect={(c) => { setSelectedCountry(c); setCountryPickerVisible(false); }}
        onClose={() => setCountryPickerVisible(false)}
        title="Select Country"
        colors={colors}
      />

      {/* ── DIAL CODE PICKER MODAL ── */}
      <CountryPickerModal
        visible={dialPickerVisible}
        search={dialSearch}
        onSearchChange={setDialSearch}
        countries={filteredCountriesDial}
        onSelect={(c) => { setPhoneDialCountry(c); setDialPickerVisible(false); }}
        onClose={() => setDialPickerVisible(false)}
        title="Select Country Code"
        showDial
        colors={colors}
      />

      {/* ── TOAST BANNER ── */}
      {toast && (
        <Animated.View
          pointerEvents="none"
          style={[
            styles.toastBanner,
            {
              top: insets.top + 12,
              backgroundColor: toast.type === 'success' ? '#1A3D2B' : '#3D1A1A',
              borderColor: toast.type === 'success' ? Colors.green : Colors.coral,
              opacity: toastAnim,
              transform: [{ translateY: toastAnim.interpolate({ inputRange: [0, 1], outputRange: [-20, 0] }) }],
            },
          ]}
        >
          <Ionicons
            name={toast.type === 'success' ? 'checkmark-circle' : 'alert-circle'}
            size={20}
            color={toast.type === 'success' ? Colors.green : Colors.coral}
          />
          <Text style={[styles.toastText, { color: toast.type === 'success' ? Colors.green : Colors.coral }]}>
            {toast.message}
          </Text>
        </Animated.View>
      )}
    </KeyboardAvoidingView>
  );
}

interface CountryPickerModalProps {
  visible: boolean;
  search: string;
  onSearchChange: (v: string) => void;
  countries: Country[];
  onSelect: (c: Country) => void;
  onClose: () => void;
  title: string;
  showDial?: boolean;
  colors: ReturnType<typeof useTheme>['colors'];
}

function CountryPickerModal({
  visible, search, onSearchChange, countries, onSelect, onClose, title, showDial, colors,
}: CountryPickerModalProps) {
  const insets = useSafeAreaInsets();
  return (
    <Modal visible={visible} animationType="slide" transparent onRequestClose={onClose}>
      <View style={pickerStyles.backdrop}>
        <Pressable style={{ flex: 1 }} onPress={onClose} />
        <View style={[pickerStyles.sheet, { backgroundColor: colors.card, paddingBottom: insets.bottom + 16 }]}>
          <View style={[pickerStyles.handle, { backgroundColor: colors.borderMid }]} />
          <View style={pickerStyles.header}>
            <Text style={[pickerStyles.title, { color: colors.textPrimary }]}>{title}</Text>
            <Pressable onPress={onClose} hitSlop={12}>
              <Ionicons name="close" size={22} color={colors.textMuted} />
            </Pressable>
          </View>
          <View style={[pickerStyles.searchRow, { backgroundColor: colors.inputBg }]}>
            <Ionicons name="search" size={16} color={colors.textFaint} />
            <TextInput
              style={[pickerStyles.searchInput, { color: colors.textPrimary }]}
              value={search}
              onChangeText={onSearchChange}
              placeholder="Search…"
              placeholderTextColor={colors.textVeryFaint}
              autoCorrect={false}
            />
            {search.length > 0 && (
              <Pressable onPress={() => onSearchChange('')} hitSlop={8}>
                <Ionicons name="close-circle" size={16} color={colors.textFaint} />
              </Pressable>
            )}
          </View>
          <FlatList
            data={countries}
            keyExtractor={(item) => item.iso}
            keyboardShouldPersistTaps="handled"
            style={pickerStyles.list}
            renderItem={({ item }) => (
              <Pressable
                style={({ pressed }) => [
                  pickerStyles.row,
                  { borderBottomColor: colors.border },
                  pressed && { backgroundColor: colors.inputBg },
                ]}
                onPress={() => onSelect(item)}
              >
                <Text style={pickerStyles.flag}>{getFlag(item.iso)}</Text>
                <Text style={[pickerStyles.countryName, { color: colors.textPrimary }]}>{item.name}</Text>
                {showDial && (
                  <Text style={[pickerStyles.dialCode, { color: colors.textSecondary }]}>{item.dial}</Text>
                )}
              </Pressable>
            )}
            ListEmptyComponent={
              <Text style={[pickerStyles.empty, { color: colors.textFaint }]}>No countries found</Text>
            }
          />
        </View>
      </View>
    </Modal>
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
  sectionTitle: {
    fontSize: 13,
    fontFamily: 'Inter_600SemiBold',
    letterSpacing: 0.8,
    textTransform: 'uppercase',
    marginBottom: 16,
  },
  label: {
    fontSize: 12,
    fontFamily: 'Inter_500Medium',
    marginBottom: 8,
    marginTop: 4,
  },
  hint: {
    fontSize: 12,
    fontFamily: 'Inter_400Regular',
    marginBottom: 8,
    marginTop: 4,
  },
  inputRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderRadius: 14,
    paddingHorizontal: 14,
    paddingVertical: 12,
    borderWidth: 1,
  },
  input: {
    flex: 1,
    fontSize: 15,
    fontFamily: 'Inter_400Regular',
    padding: 0,
  },
  fieldError: {
    fontSize: 12,
    fontFamily: 'Inter_400Regular',
    color: Colors.coral,
    marginTop: 6,
  },
  divider: { height: 1, marginVertical: 14 },
  flagText: { fontSize: 22 },
  pickerValue: {
    flex: 1,
    fontSize: 15,
    fontFamily: 'Inter_400Regular',
  },
  dialBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  dialCode: {
    fontSize: 14,
    fontFamily: 'Inter_500Medium',
  },
  dialDivider: {
    width: 1,
    height: 20,
    marginHorizontal: 4,
  },
  toastBanner: {
    position: 'absolute',
    left: 20,
    right: 20,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    paddingHorizontal: 16,
    paddingVertical: 13,
    borderRadius: 16,
    borderWidth: 1,
    zIndex: 999,
    shadowColor: '#000',
    shadowOpacity: 0.3,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: 4 },
    elevation: 8,
  },
  toastText: {
    flex: 1,
    fontSize: 14,
    fontFamily: 'Inter_500Medium',
    lineHeight: 19,
  },
});

const pickerStyles = StyleSheet.create({
  backdrop: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.6)',
    justifyContent: 'flex-end',
  },
  sheet: {
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    maxHeight: '75%',
    paddingTop: 12,
    paddingHorizontal: 20,
  },
  handle: {
    width: 40,
    height: 4,
    borderRadius: 2,
    alignSelf: 'center',
    marginBottom: 16,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 14,
  },
  title: {
    fontSize: 17,
    fontFamily: 'Inter_600SemiBold',
  },
  searchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 10,
    marginBottom: 10,
  },
  searchInput: {
    flex: 1,
    fontSize: 14,
    fontFamily: 'Inter_400Regular',
    padding: 0,
  },
  list: { flexGrow: 0 },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingVertical: 13,
    paddingHorizontal: 4,
    borderBottomWidth: 1,
  },
  flag: { fontSize: 22, width: 30, textAlign: 'center' },
  countryName: {
    flex: 1,
    fontSize: 15,
    fontFamily: 'Inter_400Regular',
  },
  dialCode: {
    fontSize: 14,
    fontFamily: 'Inter_500Medium',
  },
  empty: {
    fontSize: 14,
    fontFamily: 'Inter_400Regular',
    textAlign: 'center',
    paddingVertical: 24,
  },
});
