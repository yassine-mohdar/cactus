import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import * as StoreReview from 'expo-store-review';
import React, { useEffect, useState } from 'react';
import { Image, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useAuth } from '@/src/context/AuthContext';
import { getCharacter } from '@/src/data/characters';
import { BADGES, getBadge, type Badge } from '@/src/data/badges';
import { API_BASE_URL } from '@/src/config';
import { useTheme } from '@/src/context/ThemeContext';

type IoniconsName = React.ComponentProps<typeof Ionicons>['name'];

function StatCard({ label, value, icon, color }: { label: string; value: string | number; icon: IoniconsName; color: string }) {
  const { colors } = useTheme();
  return (
    <View style={[styles.statCard, { backgroundColor: colors.card, borderColor: color + '30' }]}>
      <View style={[styles.statIcon, { backgroundColor: color + '18' }]}>
        <Ionicons name={icon} size={16} color={color} />
      </View>
      <Text style={[styles.statValue, { color: colors.textPrimary }]}>{value}</Text>
      <Text style={[styles.statLabel, { color: colors.textFaint }]}>{label}</Text>
    </View>
  );
}

function MenuItem({ icon, label, value, color, onPress }: { icon: IoniconsName; label: string; value?: string; color: string; onPress?: () => void }) {
  const { colors } = useTheme();
  return (
    <Pressable style={styles.menuItem} onPress={onPress}>
      <View style={[styles.menuIcon, { backgroundColor: color + '18' }]}>
        <Ionicons name={icon} size={18} color={color} />
      </View>
      <Text style={[styles.menuLabel, { color: colors.textPrimary }]}>{label}</Text>
      <View style={styles.menuRight}>
        {value ? <Text style={[styles.menuValue, { color: colors.textMuted }]}>{value}</Text> : null}
        <Ionicons name="chevron-forward" size={16} color={colors.textVeryFaint} />
      </View>
    </Pressable>
  );
}

export default function MeTab() {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const { profile, logout, isAdmin } = useAuth();
  const [followerCount, setFollowerCount] = useState(0);
  const [followingCount, setFollowingCount] = useState(0);

  useEffect(() => {
    if (!profile) return;
    const base = API_BASE_URL;
    fetch(`${base}/follows/counts/${encodeURIComponent(profile.id)}`)
      .then((r) => r.json())
      .then((data: { followerCount: number; followingCount: number }) => {
        setFollowerCount(data.followerCount ?? 0);
        setFollowingCount(data.followingCount ?? 0);
      })
      .catch(() => {});
  }, [profile?.id]);

  useEffect(() => {
    if (!profile) router.replace('/login');
  }, [profile]);

  if (!profile) return null;

  const character = getCharacter(profile.selectedCharacterId);
  const winRate =
    profile.stats.totalMatches > 0
      ? Math.round((profile.stats.totalWins / profile.stats.totalMatches) * 100)
      : 0;

  const earnedBadgeIds = profile.earnedBadgeIds ?? [];
  const totalBadges = BADGES.length;
  const previewBadges: Badge[] = (
    earnedBadgeIds.slice(-4).reverse().map((id: string) => getBadge(id)) as (Badge | undefined)[]
  ).filter((b): b is Badge => b != null);

  return (
    <ScrollView
      style={[styles.container, { backgroundColor: colors.background }]}
      contentContainerStyle={[styles.content, { paddingTop: insets.top + 14, paddingBottom: 24 }]}
      showsVerticalScrollIndicator={false}
    >
      <View style={[styles.blob, { top: -40, right: -60, backgroundColor: character.accentColor, width: 240, height: 240, opacity: 0.08 }]} />

      {/* ── PROFILE CARD ── */}
      <View style={[styles.profileCard, { backgroundColor: colors.card, borderColor: character.accentColor + '35' }]}>
        <View style={[styles.profileGlow, { backgroundColor: character.accentColor }]} />
        <View style={styles.profileTop}>
          <View style={[styles.avatarRing, { borderColor: character.accentColor + '60', backgroundColor: colors.inputBg }]}>
            <Image source={character.image} style={styles.avatarImg} resizeMode="contain" />
          </View>
          <View style={styles.profileMeta}>
            <Text style={[styles.profileName, { color: colors.textPrimary }]}>{profile.name}</Text>
            <Text style={[styles.profileChar, { color: character.accentColor }]}>{character.name} · {character.personality}</Text>
            {previewBadges.length > 0 ? (
              <Pressable style={styles.profileBadgeStrip} onPress={() => router.push('/badges')}>
                {previewBadges.map((badge) => (
                  <View key={badge.id} style={[styles.profileBadgePip, { backgroundColor: badge.pale }]}>
                    <Ionicons name={badge.icon} size={13} color={badge.color} />
                  </View>
                ))}
                <Text style={[styles.profileBadgeCount, { color: colors.textFaint }]}>{earnedBadgeIds.length}/{totalBadges}</Text>
              </Pressable>
            ) : (
              <View style={[styles.premiumBadge, { backgroundColor: colors.inputBg }]}>
                <Text style={[styles.premiumText, { color: colors.textMuted }]}>✦ NinoWorld Premium</Text>
              </View>
            )}
          </View>
        </View>

        <View style={styles.socialRow}>
          <View style={styles.socialChip}>
            <Text style={[styles.socialCount, { color: colors.textPrimary }]}>{followerCount}</Text>
            <Text style={[styles.socialLabel, { color: colors.textMuted }]}>Followers</Text>
          </View>
          <View style={[styles.socialDivider, { backgroundColor: colors.border }]} />
          <Pressable style={styles.socialChip} onPress={() => router.push('/token-history')}>
            <View style={styles.tokenBalanceRow}>
              <Text style={[styles.tokenBalanceNum]}>{profile.tokenBalance ?? 0}</Text>
              <Text style={styles.tokenCoin}>🪙</Text>
            </View>
            <Text style={[styles.socialLabel, { color: colors.textMuted }]}>Tokens</Text>
          </Pressable>
          <View style={[styles.socialDivider, { backgroundColor: colors.border }]} />
          <View style={styles.socialChip}>
            <Text style={[styles.socialCount, { color: colors.textPrimary }]}>{followingCount}</Text>
            <Text style={[styles.socialLabel, { color: colors.textMuted }]}>Following</Text>
          </View>
        </View>

        <Pressable onPress={() => router.push('/characters')} style={[styles.changeBtn, { borderColor: character.accentColor + '40', backgroundColor: colors.subtleBg }]}>
          <Ionicons name="swap-horizontal" size={14} color={character.accentColor} />
          <Text style={[styles.changeBtnText, { color: character.accentColor }]}>Change Fighter</Text>
        </Pressable>
      </View>

      {/* ── STATS GRID ── */}
      <Text style={[styles.sectionTitle, { color: colors.textPrimary }]}>Battle Stats</Text>
      <View style={styles.statsGrid}>
        <StatCard label="Wins" value={profile.stats.totalWins} icon="trophy" color={Colors.yellow} />
        <StatCard label="Losses" value={profile.stats.totalLosses ?? 0} icon="close-circle" color={Colors.coral} />
        <StatCard label="Draws" value={profile.stats.totalDraws ?? 0} icon="remove-circle" color={Colors.blue} />
        <StatCard label="Win Rate" value={`${winRate}%`} icon="stats-chart" color={Colors.green} />
        <StatCard label="Streak" value={profile.stats.streak} icon="flame" color={Colors.coral} />
        <StatCard label="Matches" value={profile.stats.totalMatches} icon="game-controller" color={Colors.purple} />
      </View>

      {/* ── BADGES ── */}
      <View style={styles.badgesHeader}>
        <Text style={[styles.sectionTitle, { color: colors.textPrimary }]}>Badges</Text>
        <Pressable onPress={() => router.push('/badges')}>
          <Text style={[styles.viewAllText, { color: Colors.purple }]}>View All →</Text>
        </Pressable>
      </View>
      <Pressable
        style={[styles.badgesCard, { backgroundColor: colors.card, borderColor: colors.border }]}
        onPress={() => router.push('/badges')}
      >
        {previewBadges.length > 0 ? (
          <View style={styles.badgesRow}>
            {previewBadges.map((badge) => (
              <View key={badge.id} style={[styles.badgePip, { backgroundColor: badge.pale }]}>
                <Ionicons name={badge.icon} size={18} color={badge.color} />
              </View>
            ))}
            {earnedBadgeIds.length > 4 && (
              <View style={[styles.badgeMore, { backgroundColor: colors.inputBg }]}>
                <Text style={[styles.badgeMoreText, { color: colors.textMuted }]}>+{earnedBadgeIds.length - 4}</Text>
              </View>
            )}
            <Text style={[styles.badgesProgress, { color: colors.textFaint }]}>
              {earnedBadgeIds.length}/{totalBadges} collected
            </Text>
          </View>
        ) : (
          <View style={styles.badgesEmpty}>
            <Ionicons name="ribbon-outline" size={28} color={colors.textVeryFaint} />
            <Text style={[styles.badgesEmptyText, { color: colors.textFaint }]}>Play matches to earn badges</Text>
          </View>
        )}
      </Pressable>

      {/* ── MENU ── */}
      <Text style={[styles.sectionTitle, { color: colors.textPrimary }]}>Account</Text>
      <View style={[styles.menuCard, { backgroundColor: colors.card, borderColor: colors.border }]}>
        <MenuItem icon="create-outline" label="Edit Profile" color={Colors.purple} onPress={() => router.push('/edit-profile')} />
        <View style={[styles.menuDivider, { backgroundColor: colors.subtleBg }]} />
        <MenuItem icon="wallet-outline" label="Token Wallet" value={`${profile.tokenBalance ?? 0} 🪙`} color={Colors.yellow} onPress={() => router.push('/token-history')} />
        <View style={[styles.menuDivider, { backgroundColor: colors.subtleBg }]} />
        <MenuItem icon="trophy-outline" label="Challenges" color={Colors.purple} onPress={() => router.push('/challenges')} />
        <View style={[styles.menuDivider, { backgroundColor: colors.subtleBg }]} />
        <MenuItem icon="time" label="Match History" color={Colors.blue} onPress={() => router.push('/match-history')} />
        <View style={[styles.menuDivider, { backgroundColor: colors.subtleBg }]} />
        <MenuItem icon="help-circle-outline" label="Help & Support" color={Colors.green} onPress={() => router.push('/help-support')} />
        <View style={[styles.menuDivider, { backgroundColor: colors.subtleBg }]} />
        <MenuItem
          icon="star"
          label="Rate the App"
          color="#FFD700"
          onPress={async () => {
            const available = await StoreReview.isAvailableAsync();
            if (available) await StoreReview.requestReview();
          }}
        />
        <View style={[styles.menuDivider, { backgroundColor: colors.subtleBg }]} />
        <MenuItem icon="settings-outline" label="Settings" color={colors.textSecondary} onPress={() => router.push('/settings')} />
        <View style={[styles.menuDivider, { backgroundColor: colors.subtleBg }]} />
        <MenuItem icon="star" label="Subscription" value="Active" color={Colors.yellow} onPress={() => router.push('/locked')} />
        {isAdmin && (
          <>
            <View style={[styles.menuDivider, { backgroundColor: colors.subtleBg }]} />
            <MenuItem icon="shield-checkmark-outline" label="Admin Panel" color={Colors.coral} onPress={() => router.push('/admin-challenges')} />
          </>
        )}
      </View>

      <Pressable style={styles.logoutBtn} onPress={() => logout()}>
        <Ionicons name="log-out-outline" size={18} color={Colors.coral} />
        <Text style={styles.logoutText}>Sign Out</Text>
      </Pressable>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  content: { paddingHorizontal: 18, gap: 14 },
  blob: { position: 'absolute', borderRadius: 999 },

  profileCard: {
    borderRadius: 24, padding: 20,
    borderWidth: 1, overflow: 'hidden', gap: 14,
  },
  profileGlow: { position: 'absolute', top: -40, right: -40, width: 160, height: 160, borderRadius: 80, opacity: 0.14 },
  profileTop: { flexDirection: 'row', alignItems: 'center', gap: 16 },
  avatarRing: {
    width: 80, height: 80, borderRadius: 40,
    borderWidth: 2,
    overflow: 'hidden', alignItems: 'center', justifyContent: 'center',
  },
  avatarImg: { width: 76, height: 76 },
  profileMeta: { flex: 1, gap: 4 },
  profileName: { fontSize: 22, fontFamily: 'Inter_700Bold' },
  profileChar: { fontSize: 13, fontFamily: 'Inter_600SemiBold' },
  premiumBadge: {
    alignSelf: 'flex-start', marginTop: 4,
    paddingHorizontal: 10, paddingVertical: 4,
    borderRadius: 20,
  },
  premiumText: { fontSize: 10, fontFamily: 'Inter_600SemiBold', letterSpacing: 0.8 },

  profileBadgeStrip: {
    flexDirection: 'row', alignItems: 'center', gap: 5, marginTop: 5, flexWrap: 'wrap',
  },
  profileBadgePip: {
    width: 26, height: 26, borderRadius: 8, alignItems: 'center', justifyContent: 'center',
  },
  profileBadgeCount: {
    fontSize: 10, fontFamily: 'Inter_600SemiBold',
    marginLeft: 2,
  },
  socialRow: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
    paddingVertical: 4,
  },
  socialChip: { alignItems: 'center', paddingHorizontal: 20 },
  socialCount: { fontSize: 18, fontFamily: 'Inter_700Bold' },
  socialLabel: { fontSize: 10, fontFamily: 'Inter_400Regular' },
  socialDivider: { width: 1, height: 28 },
  tokenBalanceRow: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  tokenBalanceNum: { fontSize: 18, fontFamily: 'Inter_700Bold', color: Colors.yellow },
  tokenCoin: { fontSize: 16 },

  changeBtn: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8,
    paddingVertical: 11, borderRadius: 14, borderWidth: 1,
  },
  changeBtnText: { fontSize: 14, fontFamily: 'Inter_600SemiBold' },

  sectionTitle: { fontSize: 16, fontFamily: 'Inter_700Bold', marginBottom: -4 },

  statsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  statCard: {
    width: '30.5%', borderRadius: 18, padding: 14,
    alignItems: 'center', gap: 6, borderWidth: 1,
  },
  statIcon: { width: 36, height: 36, borderRadius: 11, alignItems: 'center', justifyContent: 'center' },
  statValue: { fontSize: 20, fontFamily: 'Inter_700Bold' },
  statLabel: { fontSize: 10, fontFamily: 'Inter_400Regular', textAlign: 'center' },

  menuCard: { borderRadius: 20, overflow: 'hidden', borderWidth: 1 },
  menuItem: { flexDirection: 'row', alignItems: 'center', gap: 14, padding: 16 },
  menuIcon: { width: 38, height: 38, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
  menuLabel: { flex: 1, fontSize: 15, fontFamily: 'Inter_600SemiBold' },
  menuRight: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  menuValue: { fontSize: 13, fontFamily: 'Inter_600SemiBold' },
  menuDivider: { height: 1, marginLeft: 68 },

  badgesHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: -4 },
  viewAllText: { fontSize: 13, fontFamily: 'Inter_600SemiBold' },
  badgesCard: {
    borderRadius: 20,
    padding: 16,
    borderWidth: 1,
    minHeight: 64,
    justifyContent: 'center',
  },
  badgesRow: { flexDirection: 'row', alignItems: 'center', gap: 8, flexWrap: 'wrap' },
  badgePip: {
    width: 40,
    height: 40,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  badgeMore: {
    width: 40,
    height: 40,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  badgeMoreText: { fontSize: 12, fontFamily: 'Inter_600SemiBold' },
  badgesProgress: { fontSize: 12, fontFamily: 'Inter_400Regular', marginLeft: 'auto' },
  badgesEmpty: { alignItems: 'center', gap: 8 },
  badgesEmptyText: { fontSize: 13, fontFamily: 'Inter_400Regular', textAlign: 'center' },

  logoutBtn: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 10,
    backgroundColor: Colors.coral + '15', borderRadius: 18, paddingVertical: 16,
    borderWidth: 1, borderColor: Colors.coral + '30',
  },
  logoutText: { fontSize: 15, fontFamily: 'Inter_700Bold', color: Colors.coral },
});
