import { Ionicons } from '@expo/vector-icons';
import { router, Tabs } from 'expo-router';
import React, { useEffect, useRef, useState } from 'react';
import {
  Animated as RNAnimated,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useAuth } from '@/src/context/AuthContext';
import { useGame } from '@/src/context/GameContext';
import { useAudioManager } from '@/src/hooks/useAudioManager';
import { realtimeService, PublicProfile } from '@/src/services/realtimeService';
import { useTheme } from '@/src/context/ThemeContext';

type IoniconsName = React.ComponentProps<typeof Ionicons>['name'];

interface TabItem {
  name: string;
  icon: IoniconsName;
  iconFilled: IoniconsName;
  label: string;
  color: string;
}

const TABS: TabItem[] = [
  { name: 'index',    icon: 'flash-outline',  iconFilled: 'flash',  label: 'Play',    color: Colors.yellow },
  { name: 'rankings', icon: 'trophy-outline', iconFilled: 'trophy', label: 'Rank',    color: '#FFD700' },
  { name: 'missions', icon: 'flag-outline',   iconFilled: 'flag',   label: 'Quests',  color: Colors.purple },
  { name: 'me',       icon: 'person-outline', iconFilled: 'person', label: 'Profile', color: Colors.blue },
];

function CustomTabBar({ state, navigation }: any) {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();

  return (
    <View style={[styles.bar, { paddingBottom: Math.max(insets.bottom, 12), backgroundColor: colors.tabBar, borderTopColor: colors.tabBarBorder }]}>
      {state.routes.map((route: any, index: number) => {
        const tab = TABS[index];
        const focused = state.index === index;
        const inactiveColor = colors.textFaint;

        return (
          <Pressable
            key={route.key}
            style={styles.tabItem}
            onPress={() => {
              if (!focused) {
                navigation.navigate(route.name);
              }
            }}
          >
            <View style={[styles.tabPill, focused && { backgroundColor: tab.color + '22' }]}>
              <Ionicons
                name={focused ? tab.iconFilled : tab.icon}
                size={22}
                color={focused ? tab.color : inactiveColor}
              />
            </View>
            <Text style={[styles.tabLabel, { color: focused ? tab.color : inactiveColor }]}>
              {tab.label}
            </Text>
          </Pressable>
        );
      })}
    </View>
  );
}

interface RematchInvite {
  fromSocketId: string;
  fromName: string;
}

function GlobalRematchSheet() {
  const { profile } = useAuth();
  const { startOnlineGame } = useGame();
  const { colors } = useTheme();
  const insets = useSafeAreaInsets();
  const [invite, setInvite] = useState<RematchInvite | null>(null);
  const slideAnim = useRef(new RNAnimated.Value(200)).current;

  const dismiss = () => {
    RNAnimated.timing(slideAnim, {
      toValue: 200,
      duration: 220,
      useNativeDriver: true,
    }).start(() => setInvite(null));
  };

  useEffect(() => {
    const off = realtimeService.onRematchInvite((data) => {
      setInvite({ fromSocketId: data.fromSocketId, fromName: data.fromName });
      RNAnimated.spring(slideAnim, {
        toValue: 0,
        useNativeDriver: true,
        damping: 18,
        stiffness: 200,
      }).start();
    });

    const offExpired = realtimeService.onInviteExpired(() => {
      dismiss();
    });

    return () => {
      off();
      offExpired();
    };
  }, []);


  const handleAccept = () => {
    if (!invite || !profile) return;
    const fromSocketId = invite.fromSocketId;

    const offAccepted = realtimeService.onRematchAccepted((data) => {
      startOnlineGame(
        profile.selectedCharacterId,
        profile.name,
        data.yourSymbol,
        data.opponentCharacterId,
        data.opponentName,
        data.opponentSocketId,
        data.roomCode,
        undefined,
        data.opponentProfileId,
      );
      setTimeout(() => router.replace('/match'), 300);
    });

    realtimeService.respondRematch(fromSocketId, true);
    dismiss();

    setTimeout(() => offAccepted(), 10_000);
  };

  const handleDecline = () => {
    if (!invite) return;
    realtimeService.respondRematch(invite.fromSocketId, false);
    dismiss();
  };

  if (!invite) return null;

  return (
    <RNAnimated.View
      style={[
        styles.sheetContainer,
        { paddingBottom: Math.max(insets.bottom, 16) + 80, transform: [{ translateY: slideAnim }], backgroundColor: colors.card, borderColor: colors.border },
      ]}
    >
      <View style={[styles.sheetHandle, { backgroundColor: colors.borderMid }]} />
      <View style={styles.sheetContent}>
        <View style={[styles.sheetIcon, { backgroundColor: Colors.purple + '22' }]}>
          <Ionicons name="flash" size={22} color={Colors.purple} />
        </View>
        <View style={styles.sheetTextBlock}>
          <Text style={[styles.sheetTitle, { color: colors.textPrimary }]}>{invite.fromName} wants to play with you!</Text>
          <Text style={[styles.sheetSub, { color: colors.textSecondary }]}>Accept to start a new game immediately</Text>
        </View>
      </View>
      <View style={styles.sheetButtons}>
        <Pressable style={[styles.sheetBtn, styles.sheetBtnAccept]} onPress={handleAccept}>
          <Text style={styles.sheetBtnText}>Accept</Text>
        </Pressable>
        <Pressable style={[styles.sheetBtn, { backgroundColor: colors.inputBg, borderColor: colors.borderMid, borderWidth: 1 }]} onPress={handleDecline}>
          <Text style={[styles.sheetBtnText, { color: colors.textSecondary }]}>Decline</Text>
        </Pressable>
      </View>
    </RNAnimated.View>
  );
}

function FollowNotification() {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const [followerName, setFollowerName] = useState<string | null>(null);
  const slideAnim = useRef(new RNAnimated.Value(-120)).current;
  const dismissTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  const dismiss = () => {
    if (dismissTimer.current) clearTimeout(dismissTimer.current);
    RNAnimated.timing(slideAnim, {
      toValue: -120,
      duration: 280,
      useNativeDriver: true,
    }).start(() => setFollowerName(null));
  };

  const show = (name: string) => {
    if (dismissTimer.current) clearTimeout(dismissTimer.current);
    setFollowerName(name);
    RNAnimated.spring(slideAnim, {
      toValue: 0,
      useNativeDriver: true,
      damping: 16,
      stiffness: 180,
    }).start();
    dismissTimer.current = setTimeout(dismiss, 3000);
  };

  useEffect(() => {
    const off = realtimeService.onFollowReceived((data) => {
      show(data.followerName);
    });
    return () => {
      off();
      if (dismissTimer.current) clearTimeout(dismissTimer.current);
    };
  }, []);

  if (!followerName) return null;

  return (
    <RNAnimated.View
      style={[
        styles.followToast,
        { top: Math.max(insets.top, 16) + 8, transform: [{ translateY: slideAnim }], backgroundColor: colors.card, borderColor: Colors.green + '40' },
      ]}
    >
      <View style={[styles.followToastIcon, { backgroundColor: Colors.green + '20' }]}>
        <Ionicons name="person-add" size={16} color={Colors.green} />
      </View>
      <Text style={[styles.followToastText, { color: colors.textPrimary }]} numberOfLines={1}>
        <Text style={{ fontFamily: 'Inter_700Bold' }}>{followerName}</Text>
        <Text style={{ fontFamily: 'Inter_400Regular' }}> is now following you!</Text>
      </Text>
    </RNAnimated.View>
  );
}

export default function TabsLayout() {
  useAudioManager();
  const { profile } = useAuth();

  useEffect(() => {
    if (!profile) return;
    const pub: PublicProfile = {
      profileId: profile.id,
      name: profile.name,
      characterId: profile.selectedCharacterId,
      hasSubscription: profile.hasSubscription,
      stats: {
        totalMatches: profile.stats.totalMatches,
        totalWins: profile.stats.totalWins,
        totalLosses: profile.stats.totalLosses ?? 0,
        totalDraws: profile.stats.totalDraws ?? 0,
        streak: profile.stats.streak,
        bestStreak: profile.stats.bestStreak,
      },
      earnedBadgeIds: profile.earnedBadgeIds ?? [],
    };
    realtimeService.connect(pub);
  }, [profile?.id]);

  return (
    <>
      <Tabs
        tabBar={(props) => <CustomTabBar {...props} />}
        screenOptions={{ headerShown: false }}
      >
        <Tabs.Screen name="index" />
        <Tabs.Screen name="rankings" />
        <Tabs.Screen name="missions" />
        <Tabs.Screen name="me" />
      </Tabs>
      <GlobalRematchSheet />
      <FollowNotification />
    </>
  );
}

const styles = StyleSheet.create({
  bar: {
    flexDirection: 'row',
    borderTopWidth: 1,
    paddingTop: 10,
  },
  tabItem: {
    flex: 1,
    alignItems: 'center',
    gap: 4,
    paddingVertical: 4,
  },
  tabPill: {
    width: 48,
    height: 36,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  tabLabel: {
    fontSize: 10,
    fontFamily: 'Inter_600SemiBold',
    letterSpacing: 0.3,
  },

  sheetContainer: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    borderWidth: 1,
    paddingTop: 12,
    paddingHorizontal: 20,
    paddingBottom: 24,
    gap: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: -4 },
    shadowOpacity: 0.3,
    shadowRadius: 16,
    elevation: 20,
  },
  sheetHandle: {
    width: 40,
    height: 4,
    borderRadius: 2,
    alignSelf: 'center',
    marginBottom: 4,
  },
  sheetContent: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
  },
  sheetIcon: {
    width: 48,
    height: 48,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sheetTextBlock: {
    flex: 1,
    gap: 3,
  },
  sheetTitle: {
    fontSize: 16,
    fontFamily: 'Inter_700Bold',
  },
  sheetSub: {
    fontSize: 12,
    fontFamily: 'Inter_400Regular',
  },
  sheetButtons: {
    flexDirection: 'row',
    gap: 10,
  },
  sheetBtn: {
    flex: 1,
    paddingVertical: 14,
    borderRadius: 14,
    alignItems: 'center',
  },
  sheetBtnAccept: {
    backgroundColor: Colors.green,
  },
  sheetBtnText: {
    fontSize: 15,
    fontFamily: 'Inter_700Bold',
    color: '#FFFFFF',
  },

  followToast: {
    position: 'absolute',
    left: 16,
    right: 16,
    borderRadius: 16,
    borderWidth: 1,
    paddingHorizontal: 14,
    paddingVertical: 12,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.25,
    shadowRadius: 12,
    elevation: 18,
    zIndex: 9999,
  },
  followToastIcon: {
    width: 32,
    height: 32,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  followToastText: {
    flex: 1,
    fontSize: 14,
  },
});
