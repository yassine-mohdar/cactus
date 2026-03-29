import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  Modal,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useAuth } from '@/src/context/AuthContext';
import { useTheme } from '@/src/context/ThemeContext';
import { apiFetch } from '@/src/services/apiClient';

interface AdminUser {
  id: string;
  name: string;
  email: string | null;
  isAdmin: boolean;
  isBlocked: boolean;
  totalMatches: number;
  totalWins: number;
  hasSubscription: boolean;
  createdAt: string;
  lastSeenAt: string;
}

type AdminAction = 'block' | 'unblock' | 'set-admin' | 'remove-admin';

function relativeDate(iso: string): string {
  const d = new Date(iso);
  const diffDays = Math.floor((Date.now() - d.getTime()) / 86400000);
  if (diffDays === 0) return 'Today';
  if (diffDays === 1) return '1d ago';
  if (diffDays < 30) return `${diffDays}d ago`;
  return d.toLocaleDateString(undefined, { month: 'short', year: 'numeric' });
}

function UserActionSheet({
  user,
  onClose,
  onAction,
  colors,
}: {
  user: AdminUser;
  onClose: () => void;
  onAction: (action: AdminAction) => void;
  colors: ReturnType<typeof useTheme>['colors'];
}) {
  return (
    <Modal transparent animationType="fade" onRequestClose={onClose}>
      <Pressable style={sheet.backdrop} onPress={onClose}>
        <Pressable style={[sheet.container, { backgroundColor: colors.card }]} onPress={(e) => e.stopPropagation()}>
          <View style={[sheet.handle, { backgroundColor: colors.borderMid }]} />

          <View style={sheet.titleRow}>
            <View style={[sheet.avatar, { backgroundColor: Colors.purple + '25', borderColor: Colors.purple + '50' }]}>
              <Text style={[sheet.avatarInitial, { color: Colors.purple }]}>
                {user.name.trim().charAt(0).toUpperCase() || '?'}
              </Text>
            </View>
            <View style={{ flex: 1 }}>
              <Text style={[sheet.userName, { color: colors.textPrimary }]} numberOfLines={1}>{user.name}</Text>
              {user.email ? (
                <Text style={[sheet.userEmail, { color: colors.textMuted }]} numberOfLines={1}>{user.email}</Text>
              ) : null}
            </View>
          </View>

          <View style={[sheet.divider, { backgroundColor: colors.border }]} />

          <Pressable
            style={({ pressed }) => [sheet.action, pressed && [sheet.actionPressed, { backgroundColor: colors.subtleBg }]]}
            onPress={() => onAction(user.isBlocked ? 'unblock' : 'block')}
          >
            <Ionicons
              name={user.isBlocked ? 'lock-open-outline' : 'ban-outline'}
              size={20}
              color={user.isBlocked ? Colors.green : Colors.coral}
            />
            <Text style={[sheet.actionText, { color: user.isBlocked ? Colors.green : Colors.coral }]}>
              {user.isBlocked ? 'Unblock User' : 'Block User'}
            </Text>
          </Pressable>

          <Pressable
            style={({ pressed }) => [sheet.action, pressed && [sheet.actionPressed, { backgroundColor: colors.subtleBg }]]}
            onPress={() => onAction(user.isAdmin ? 'remove-admin' : 'set-admin')}
          >
            <Ionicons
              name={user.isAdmin ? 'shield-outline' : 'shield-checkmark-outline'}
              size={20}
              color={Colors.purple}
            />
            <Text style={[sheet.actionText, { color: Colors.purple }]}>
              {user.isAdmin ? 'Remove Admin' : 'Make Admin'}
            </Text>
          </Pressable>

          <View style={[sheet.divider, { backgroundColor: colors.border }]} />

          <Pressable
            style={({ pressed }) => [sheet.action, pressed && [sheet.actionPressed, { backgroundColor: colors.subtleBg }]]}
            onPress={onClose}
          >
            <Text style={[sheet.cancelText, { color: colors.textMuted }]}>Cancel</Text>
          </Pressable>
        </Pressable>
      </Pressable>
    </Modal>
  );
}

function UserRow({
  user,
  onSelect,
  colors,
}: {
  user: AdminUser;
  onSelect: (user: AdminUser) => void;
  colors: ReturnType<typeof useTheme>['colors'];
}) {
  const initial = user.name.trim().charAt(0).toUpperCase() || '?';
  return (
    <Pressable
      style={({ pressed }) => [styles.userRow, { backgroundColor: colors.subtleBg, borderColor: colors.border }, pressed && { opacity: 0.75 }]}
      onPress={() => onSelect(user)}
    >
      <View style={[styles.userAvatar, { backgroundColor: Colors.purple + '20', borderColor: Colors.purple + '50' }]}>
        <Text style={[styles.userInitial, { color: Colors.purple }]}>{initial}</Text>
      </View>
      <View style={styles.userBody}>
        <View style={styles.userTopRow}>
          <Text style={[styles.userName, { color: colors.textPrimary }]} numberOfLines={1}>{user.name}</Text>
          <View style={styles.badges}>
            {user.isAdmin && (
              <View style={[styles.badge, { backgroundColor: Colors.purple + '20', borderColor: Colors.purple + '55' }]}>
                <Text style={[styles.badgeText, { color: Colors.purple }]}>Admin</Text>
              </View>
            )}
            {user.isBlocked && (
              <View style={[styles.badge, { backgroundColor: Colors.coral + '20', borderColor: Colors.coral + '55' }]}>
                <Text style={[styles.badgeText, { color: Colors.coral }]}>Blocked</Text>
              </View>
            )}
            {user.hasSubscription && (
              <View style={[styles.badge, { backgroundColor: Colors.yellow + '20', borderColor: Colors.yellow + '55' }]}>
                <Text style={[styles.badgeText, { color: Colors.yellow }]}>Pro</Text>
              </View>
            )}
          </View>
        </View>
        {user.email && (
          <Text style={[styles.userEmail, { color: colors.textMuted }]} numberOfLines={1}>{user.email}</Text>
        )}
        <Text style={[styles.userMeta, { color: colors.textFaint }]}>
          {user.totalMatches} matches · {user.totalWins}W · Joined {relativeDate(user.createdAt)}
        </Text>
      </View>
      <View style={[styles.moreBtn, { backgroundColor: colors.inputBg }]}>
        <Ionicons name="ellipsis-vertical" size={18} color={colors.textMuted} />
      </View>
    </Pressable>
  );
}

export default function AdminUsersScreen() {
  const insets = useSafeAreaInsets();
  const { isAdmin } = useAuth();
  const { colors } = useTheme();
  const [users, setUsers] = useState<AdminUser[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [selectedUser, setSelectedUser] = useState<AdminUser | null>(null);
  const searchRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    if (!isAdmin) router.replace('/(tabs)/me');
  }, [isAdmin]);

  const fetchUsers = useCallback(async (q = '') => {
    setLoading(true);
    try {
      const params = new URLSearchParams();
      if (q.trim()) params.set('search', q.trim());
      const res = await apiFetch(`/admin/users?${params.toString()}`);
      if (!res.ok) throw new Error('Failed to load');
      const data = await res.json() as { users: AdminUser[] };
      setUsers(data.users ?? []);
    } catch {
      Alert.alert('Error', 'Could not load users');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (isAdmin) fetchUsers();
  }, [isAdmin, fetchUsers]);

  const handleSearchChange = (text: string) => {
    setSearch(text);
    if (searchRef.current) clearTimeout(searchRef.current);
    searchRef.current = setTimeout(() => fetchUsers(text), 400);
  };

  const handleAction = async (action: AdminAction) => {
    const user = selectedUser;
    if (!user) return;

    const actionLabels: Record<AdminAction, string> = {
      block: 'Block',
      unblock: 'Unblock',
      'set-admin': 'Make Admin',
      'remove-admin': 'Remove Admin',
    };
    const confirmMessages: Record<AdminAction, string> = {
      block: `Block "${user.name}"? They will not be able to log in.`,
      unblock: `Unblock "${user.name}"? They will regain access.`,
      'set-admin': `Grant admin access to "${user.name}"?`,
      'remove-admin': `Remove admin access from "${user.name}"?`,
    };

    setSelectedUser(null);

    Alert.alert(actionLabels[action], confirmMessages[action], [
      { text: 'Cancel', style: 'cancel' },
      {
        text: actionLabels[action],
        style: action === 'block' ? 'destructive' : 'default',
        onPress: async () => {
          try {
            const res = await apiFetch(`/admin/users/${user.id}/${action}`, { method: 'POST' });
            if (!res.ok) {
              const err = await res.json().catch(() => ({})) as { error?: string };
              Alert.alert('Error', err.error ?? 'Action failed');
              return;
            }
            fetchUsers(search);
          } catch {
            Alert.alert('Error', 'Request failed');
          }
        },
      },
    ]);
  };

  if (!isAdmin) {
    return (
      <View style={[styles.container, styles.center, { backgroundColor: colors.background }]}>
        <Ionicons name="lock-closed-outline" size={48} color={colors.textFaint} />
        <Text style={[styles.accessDenied, { color: colors.textMuted }]}>Access denied</Text>
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
          <Text style={[styles.headerTitle, { color: colors.textPrimary }]}>Admin: Users</Text>
          <Text style={[styles.headerSub, { color: colors.textMuted }]}>Search and manage players</Text>
        </View>
        <Pressable onPress={() => fetchUsers(search)} style={[styles.refreshBtn, { backgroundColor: Colors.purple + '18' }]}>
          <Ionicons name="refresh" size={18} color={Colors.purple} />
        </Pressable>
      </View>

      <View style={[styles.searchRow, { backgroundColor: colors.inputBg, borderColor: colors.border }]}>
        <Ionicons name="search-outline" size={16} color={colors.textFaint} style={{ marginLeft: 14 }} />
        <TextInput
          style={[styles.searchInput, { color: colors.textPrimary }]}
          value={search}
          onChangeText={handleSearchChange}
          placeholder="Search by name or email…"
          placeholderTextColor={colors.textFaint}
          autoCapitalize="none"
          returnKeyType="search"
          onSubmitEditing={() => fetchUsers(search)}
        />
        {search.length > 0 && (
          <Pressable onPress={() => { setSearch(''); fetchUsers(''); }} style={{ paddingRight: 14 }}>
            <Ionicons name="close-circle" size={16} color={colors.textMuted} />
          </Pressable>
        )}
      </View>

      {loading ? (
        <View style={styles.center}>
          <ActivityIndicator color={Colors.purple} size="large" />
        </View>
      ) : (
        <FlatList
          data={users}
          keyExtractor={(u) => u.id}
          contentContainerStyle={[styles.list, { paddingBottom: insets.bottom + 20 }]}
          showsVerticalScrollIndicator={false}
          ListEmptyComponent={
            <View style={styles.empty}>
              <Ionicons name="people-outline" size={48} color={colors.textFaint} />
              <Text style={[styles.emptyText, { color: colors.textMuted }]}>
                {search ? 'No users match your search.' : 'No users found.'}
              </Text>
            </View>
          }
          renderItem={({ item }) => (
            <UserRow user={item} onSelect={setSelectedUser} colors={colors} />
          )}
        />
      )}

      {selectedUser && (
        <UserActionSheet
          user={selectedUser}
          onClose={() => setSelectedUser(null)}
          onAction={handleAction}
          colors={colors}
        />
      )}
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
  refreshBtn: {
    width: 36, height: 36,
    alignItems: 'center', justifyContent: 'center',
    borderRadius: 10,
  },

  searchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    margin: 12,
    borderRadius: 14,
    borderWidth: 1,
    gap: 8,
  },
  searchInput: {
    flex: 1,
    paddingVertical: 11,
    fontSize: 14,
    fontFamily: 'Inter_400Regular',
  },

  list: { paddingHorizontal: 12, gap: 8 },

  userRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    borderRadius: 16,
    borderWidth: 1,
    padding: 12,
  },
  userAvatar: {
    width: 44, height: 44,
    borderRadius: 14,
    borderWidth: 1.5,
    alignItems: 'center', justifyContent: 'center',
    flexShrink: 0,
  },
  userInitial: { fontSize: 18, fontFamily: 'Inter_700Bold' },
  userBody: { flex: 1, gap: 3 },
  userTopRow: { flexDirection: 'row', alignItems: 'center', gap: 6, flexWrap: 'wrap' },
  userName: { fontSize: 15, fontFamily: 'Inter_600SemiBold', flexShrink: 1 },
  badges: { flexDirection: 'row', gap: 4, flexWrap: 'wrap' },
  badge: { paddingHorizontal: 7, paddingVertical: 2, borderRadius: 6, borderWidth: 1 },
  badgeText: { fontSize: 10, fontFamily: 'Inter_600SemiBold' },
  userEmail: { fontSize: 12, fontFamily: 'Inter_400Regular' },
  userMeta: { fontSize: 11, fontFamily: 'Inter_400Regular' },
  moreBtn: {
    width: 32, height: 32,
    alignItems: 'center', justifyContent: 'center',
    borderRadius: 10,
  },

  empty: { alignItems: 'center', justifyContent: 'center', gap: 10, paddingTop: 60 },
  emptyText: { fontSize: 14, fontFamily: 'Inter_400Regular', textAlign: 'center' },
});

const sheet = StyleSheet.create({
  backdrop: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.6)',
    justifyContent: 'flex-end',
  },
  container: {
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    paddingBottom: 32,
    paddingTop: 12,
    paddingHorizontal: 16,
  },
  handle: {
    width: 36, height: 4,
    borderRadius: 2,
    alignSelf: 'center',
    marginBottom: 16,
  },
  titleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 16,
  },
  avatar: {
    width: 42, height: 42,
    borderRadius: 13,
    borderWidth: 1.5,
    alignItems: 'center', justifyContent: 'center',
  },
  avatarInitial: { fontSize: 17, fontFamily: 'Inter_700Bold' },
  userName: { fontSize: 15, fontFamily: 'Inter_600SemiBold' },
  userEmail: { fontSize: 12, fontFamily: 'Inter_400Regular', marginTop: 1 },
  divider: { height: 1, marginVertical: 8 },
  action: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
    paddingVertical: 14,
    paddingHorizontal: 4,
    borderRadius: 12,
  },
  actionPressed: {},
  actionText: { fontSize: 15, fontFamily: 'Inter_500Medium' },
  cancelText: {
    fontSize: 15,
    fontFamily: 'Inter_500Medium',
    textAlign: 'center',
    flex: 1,
  },
});
