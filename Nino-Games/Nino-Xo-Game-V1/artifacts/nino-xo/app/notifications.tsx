import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import React, { useState } from 'react';
import {
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useTheme } from '@/src/context/ThemeContext';
import { PremiumCard } from '@/src/components/PremiumCard';

type IoniconsName = React.ComponentProps<typeof Ionicons>['name'];

interface Notification {
  id: string;
  type: 'invite' | 'result' | 'mission' | 'system';
  title: string;
  body: string;
  time: string;
  read: boolean;
}

const SAMPLE_NOTIFICATIONS: Notification[] = [
  {
    id: '1',
    type: 'invite',
    title: 'Friend Room Invite',
    body: 'SpikeMaster invited you to a private room. Code: XP42',
    time: '2m ago',
    read: false,
  },
  {
    id: '2',
    type: 'mission',
    title: 'Mission Unlocked!',
    body: 'You completed "First Victory" — 🌵 Cactus Badge earned!',
    time: '1h ago',
    read: false,
  },
  {
    id: '3',
    type: 'result',
    title: 'Match Result',
    body: 'You won your quick match against Cactus Carl!',
    time: '3h ago',
    read: true,
  },
  {
    id: '4',
    type: 'system',
    title: 'Welcome to Nino XO!',
    body: 'Your subscription is active. Enjoy all premium features!',
    time: 'Yesterday',
    read: true,
  },
];

const TYPE_CONFIG: Record<string, { icon: IoniconsName; color: string; bg: string }> = {
  invite: { icon: 'people', color: Colors.blue, bg: Colors.bluePale },
  result: { icon: 'trophy', color: Colors.yellow, bg: Colors.yellowPale },
  mission: { icon: 'flag', color: Colors.purple, bg: Colors.purplePale },
  system: { icon: 'information-circle', color: Colors.green, bg: Colors.greenPale },
};

export default function NotificationsScreen() {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const [notifications, setNotifications] = useState(SAMPLE_NOTIFICATIONS);

  const unreadCount = notifications.filter(n => !n.read).length;

  const markAllRead = () => {
    setNotifications(ns => ns.map(n => ({ ...n, read: true })));
  };

  const markRead = (id: string) => {
    setNotifications(ns => ns.map(n => n.id === id ? { ...n, read: true } : n));
  };

  return (
    <View style={[styles.container, { paddingTop: insets.top, backgroundColor: colors.background }]}>
      <View style={styles.header}>
        <Pressable onPress={() => router.back()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={24} color={colors.textPrimary} />
        </Pressable>
        <View style={styles.titleRow}>
          <Text style={[styles.title, { color: colors.textPrimary }]}>Notifications</Text>
          {unreadCount > 0 && (
            <View style={styles.badge}>
              <Text style={styles.badgeText}>{unreadCount}</Text>
            </View>
          )}
        </View>
        {unreadCount > 0 && (
          <Pressable onPress={markAllRead} style={styles.markAllBtn}>
            <Text style={[styles.markAllText, { color: Colors.blue }]}>Mark all</Text>
          </Pressable>
        )}
        {unreadCount === 0 && <View style={{ width: 60 }} />}
      </View>

      {notifications.length === 0 ? (
        <View style={styles.emptyState}>
          <Ionicons name="notifications-outline" size={64} color={colors.textFaint} />
          <Text style={[styles.emptyTitle, { color: colors.textPrimary }]}>No notifications</Text>
          <Text style={[styles.emptyDesc, { color: colors.textSecondary }]}>You're all caught up!</Text>
        </View>
      ) : (
        <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.list}>
          {notifications.map((n) => {
            const config = TYPE_CONFIG[n.type];
            return (
              <Pressable key={n.id} onPress={() => markRead(n.id)}>
                <PremiumCard style={[styles.notifCard, !n.read && { borderLeftWidth: 3, borderLeftColor: Colors.green }]}>
                  {!n.read && <View style={styles.unreadDot} />}
                  <View style={[styles.notifIcon, { backgroundColor: config.bg }]}>
                    <Ionicons name={config.icon} size={20} color={config.color} />
                  </View>
                  <View style={styles.notifContent}>
                    <Text style={[styles.notifTitle, { color: colors.textPrimary }]}>{n.title}</Text>
                    <Text style={[styles.notifBody, { color: colors.textSecondary }]}>{n.body}</Text>
                    <Text style={[styles.notifTime, { color: colors.textFaint }]}>{n.time}</Text>
                  </View>
                </PremiumCard>
              </Pressable>
            );
          })}
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingVertical: 12,
  },
  backBtn: { width: 40, height: 40, alignItems: 'center', justifyContent: 'center' },
  titleRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  title: { fontSize: 20, fontFamily: 'Inter_700Bold' },
  badge: { backgroundColor: Colors.coral, borderRadius: 10, paddingHorizontal: 7, paddingVertical: 2 },
  badgeText: { fontSize: 12, fontFamily: 'Inter_700Bold', color: Colors.white },
  markAllBtn: {},
  markAllText: { fontSize: 13, fontFamily: 'Inter_500Medium' },
  emptyState: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 12 },
  emptyTitle: { fontSize: 20, fontFamily: 'Inter_700Bold' },
  emptyDesc: { fontSize: 14, fontFamily: 'Inter_400Regular' },
  list: { paddingHorizontal: 20, paddingBottom: 32, gap: 8 },
  notifCard: { flexDirection: 'row', alignItems: 'flex-start', gap: 12 },
  unreadDot: {
    position: 'absolute',
    top: 8, right: 8,
    width: 8, height: 8,
    borderRadius: 4,
    backgroundColor: Colors.green,
  },
  notifIcon: { width: 40, height: 40, borderRadius: 12, alignItems: 'center', justifyContent: 'center', marginTop: 2 },
  notifContent: { flex: 1, gap: 4 },
  notifTitle: { fontSize: 14, fontFamily: 'Inter_600SemiBold' },
  notifBody: { fontSize: 13, fontFamily: 'Inter_400Regular', lineHeight: 18 },
  notifTime: { fontSize: 12, fontFamily: 'Inter_400Regular' },
});
