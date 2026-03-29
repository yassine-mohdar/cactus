import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import * as Clipboard from 'expo-clipboard';
import { router, useLocalSearchParams } from 'expo-router';
import React, { useEffect, useRef, useState } from 'react';
import {
  Animated,
  Modal,
  Pressable,
  ScrollView,
  Share,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuth } from '@/src/context/AuthContext';
import { useGame } from '@/src/context/GameContext';
import { useTheme } from '@/src/context/ThemeContext';
import { getCharacter } from '@/src/data/characters';
import { getBadge, BADGES, type Badge } from '@/src/data/badges';
import { BouncingCharacter } from '@/src/components/BouncingCharacter';
import { realtimeService, PublicProfile } from '@/src/services/realtimeService';
import { getFlag, getCountryByIso } from '@/src/data/countries';
import { Colors } from '@/constants/colors';
import { API_BASE_URL } from '@/src/config';

const BOT_PROFILE: PublicProfile = {
  profileId: 'bot',
  name: 'NinoWorld Bot',
  characterId: 'blue_detective',
  hasSubscription: false,
  stats: {
    totalMatches: 9999,
    totalWins: 5012,
    totalLosses: 2847,
    totalDraws: 2140,
    streak: 0,
    bestStreak: 42,
  },
  earnedBadgeIds: ['first_win', 'veteran_10', 'wins_10', 'streak_3', 'streak_5', 'matches_25', 'wins_50', 'matches_100'],
};

interface ExtendedProfile extends PublicProfile {
  isOnline?: boolean;
  isPlaying?: boolean;
  socketId?: string;
  followerCount?: number;
  followingCount?: number;
  country?: string;
}

function SkeletonCard({ colors }: { colors: ReturnType<typeof useTheme>['colors'] }) {
  return (
    <View style={[styles.skeletonWrap, { backgroundColor: colors.card }]}>
      <View style={[styles.skeletonBanner, { backgroundColor: colors.subtleBg }]} />
      <View style={styles.skeletonAvatarWrap}>
        <View style={[styles.skeletonAvatar, { backgroundColor: colors.inputBg }]} />
      </View>
      <View style={styles.skeletonBody}>
        <View style={[styles.skeletonLine, { backgroundColor: colors.subtleBg }]} />
        <View style={[styles.skeletonLine, { width: '50%', backgroundColor: colors.subtleBg }]} />
        <View style={styles.skeletonStatsRow}>
          {[0, 1, 2, 3, 4].map((i) => (
            <View key={i} style={[styles.skeletonStat, { backgroundColor: colors.subtleBg }]} />
          ))}
        </View>
      </View>
    </View>
  );
}

export default function PublicProfileScreen() {
  const insets = useSafeAreaInsets();
  const { id } = useLocalSearchParams<{ id: string }>();
  const { profile: myProfile } = useAuth();
  const { startOnlineGame } = useGame();
  const { colors } = useTheme();

  const [publicProfile, setPublicProfile] = useState<ExtendedProfile | null>(null);
  const [loading, setLoading] = useState(true);
  const [notFound, setNotFound] = useState(false);
  const [isOnline, setIsOnline] = useState(false);
  const [isPlaying, setIsPlaying] = useState(false);
  const [challengeSent, setChallengeSent] = useState(false);
  const [challengeWaiting, setChallengeWaiting] = useState(false);
  const [isFollowing, setIsFollowing] = useState(false);
  const [theyFollowMe, setTheyFollowMe] = useState(false);
  const [followerCount, setFollowerCount] = useState(0);
  const [followingCount, setFollowingCount] = useState(0);
  const [selectedBadge, setSelectedBadge] = useState<Badge | null>(null);
  const [shareCopied, setShareCopied] = useState(false);
  const toastOpacity = useRef(new Animated.Value(0)).current;
  const toastTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  const isBot = id === 'bot' || id?.startsWith('bot_');
  const isMe = !!myProfile && !!id && myProfile.id === id;

  useEffect(() => {
    if (!id) {
      setNotFound(true);
      setLoading(false);
      return;
    }

    if (isBot) {
      setPublicProfile(BOT_PROFILE);
      setIsOnline(false);
      setIsPlaying(false);
      setLoading(false);
      return;
    }

    const base = API_BASE_URL;

    Promise.all([
      fetch(`${base}/profiles/${id}`)
        .then((res) => {
          if (res.status === 404) return null;
          return res.json() as Promise<ExtendedProfile>;
        }),
      myProfile
        ? fetch(`${base}/follows/me?followerId=${encodeURIComponent(myProfile.id)}`)
            .then((res) => res.json())
            .then((data: { following: string[] }) => data.following ?? [])
            .catch(() => [] as string[])
        : Promise.resolve([] as string[]),
      fetch(`${base}/follows/me?followerId=${encodeURIComponent(id)}`)
        .then((res) => res.json())
        .then((data: { following: string[] }) => data.following ?? [])
        .catch(() => [] as string[]),
    ])
      .then(([profileData, myFollowingList, theirFollowingList]) => {
        if (!profileData) {
          setNotFound(true);
        } else {
          setPublicProfile(profileData);
          setIsOnline(profileData.isOnline ?? false);
          setIsPlaying(profileData.isPlaying ?? false);
          setFollowerCount(profileData.followerCount ?? 0);
          setFollowingCount(profileData.followingCount ?? 0);
          setIsFollowing(myFollowingList.includes(id));
          setTheyFollowMe(myProfile ? theirFollowingList.includes(myProfile.id) : false);
        }
      })
      .catch(() => setNotFound(true))
      .finally(() => setLoading(false));
  }, [id, isBot, myProfile]);

  const handleFollow = async () => {
    if (!myProfile || !publicProfile) return;
    const base = API_BASE_URL;
    if (isFollowing) {
      setIsFollowing(false);
      setFollowerCount((c) => Math.max(0, c - 1));
      try {
        const res = await fetch(`${base}/follows/${encodeURIComponent(id!)}?followerId=${encodeURIComponent(myProfile.id)}`, {
          method: 'DELETE',
        });
        if (!res.ok) throw new Error('unfollow failed');
      } catch {
        setIsFollowing(true);
        setFollowerCount((c) => c + 1);
      }
    } else {
      setIsFollowing(true);
      setFollowerCount((c) => c + 1);
      try {
        const res = await fetch(`${base}/follows/${encodeURIComponent(id!)}`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ followerId: myProfile.id }),
        });
        if (!res.ok) throw new Error('follow failed');
      } catch {
        setIsFollowing(false);
        setFollowerCount((c) => Math.max(0, c - 1));
      }
    }
  };

  const showToast = () => {
    if (toastTimer.current) clearTimeout(toastTimer.current);
    Animated.sequence([
      Animated.timing(toastOpacity, { toValue: 1, duration: 200, useNativeDriver: true }),
      Animated.delay(1800),
      Animated.timing(toastOpacity, { toValue: 0, duration: 300, useNativeDriver: true }),
    ]).start();
    toastTimer.current = setTimeout(() => {
      toastTimer.current = null;
    }, 2300);
  };

  const handleChallenge = () => {
    if (!publicProfile || !myProfile || !realtimeService.isConnected) return;
    const opponentSocketId = publicProfile.socketId;
    if (!opponentSocketId) return;

    realtimeService.sendRematchInvite(opponentSocketId);
    setChallengeSent(true);
    setChallengeWaiting(true);
    showToast();

    const offAccepted = realtimeService.onRematchAccepted((data) => {
      setChallengeWaiting(false);
      startOnlineGame(
        myProfile.selectedCharacterId,
        myProfile.name,
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

    const offDeclined = realtimeService.onRematchDeclined(() => {
      setChallengeWaiting(false);
      setChallengeSent(false);
    });

    const offExpired = realtimeService.onInviteExpired(() => {
      setChallengeWaiting(false);
      setChallengeSent(false);
    });

    setTimeout(() => {
      offAccepted();
      offDeclined();
      offExpired();
      setChallengeWaiting(false);
    }, 35_000);
  };

  const handleShare = async () => {
    const link = `ninoxo://public-profile?id=${id}`;
    try {
      await Share.share({ message: link, url: link });
    } catch {
      await Clipboard.setStringAsync(link);
      setShareCopied(true);
      setTimeout(() => setShareCopied(false), 2000);
    }
  };

  const character = publicProfile ? getCharacter(publicProfile.characterId) : null;

  const winRate = publicProfile && publicProfile.stats.totalMatches > 0
    ? Math.round((publicProfile.stats.totalWins / publicProfile.stats.totalMatches) * 100)
    : 0;

  const earnedBadges = publicProfile
    ? BADGES.filter((b) => publicProfile.earnedBadgeIds.includes(b.id))
    : [];

  const statusColor = isPlaying ? Colors.yellow : isOnline ? Colors.green : 'transparent';
  const statusLabel = isPlaying ? 'In a match' : isOnline ? 'Online now' : null;
  const isMutual = isFollowing && theyFollowMe;

  return (
    <View style={[styles.container, { paddingTop: insets.top, backgroundColor: colors.background }]}>
      <View style={[styles.toolbar, { backgroundColor: colors.background }]}>
        <Pressable onPress={() => router.back()} style={styles.toolbarBtn}>
          <Ionicons name="chevron-back" size={26} color={colors.textPrimary} />
        </Pressable>
        <Text style={[styles.toolbarTitle, { color: colors.textPrimary }]}>Player Profile</Text>
        <Pressable style={styles.toolbarBtn} onPress={handleShare}>
          <Ionicons name={shareCopied ? 'checkmark' : 'share-outline'} size={22} color={colors.textPrimary} />
        </Pressable>
      </View>

      {loading && (
        <ScrollView contentContainerStyle={styles.scrollContent}>
          <SkeletonCard colors={colors} />
        </ScrollView>
      )}

      {!loading && notFound && (
        <View style={styles.notFound}>
          <Text style={styles.notFoundEmoji}>🔍</Text>
          <Text style={[styles.notFoundTitle, { color: colors.textPrimary }]}>Profile not found</Text>
          <Text style={[styles.notFoundDesc, { color: colors.textSecondary }]}>
            This player may be offline or their profile doesn't exist yet.
          </Text>
          <Pressable onPress={() => router.back()} style={[styles.backLinkBtn, { backgroundColor: colors.card }]}>
            <Text style={[styles.backLinkText, { color: Colors.blue }]}>Go Back</Text>
          </Pressable>
        </View>
      )}

      {!loading && !notFound && publicProfile && character && (
        <ScrollView
          showsVerticalScrollIndicator={false}
          contentContainerStyle={[styles.scrollContent, { paddingBottom: insets.bottom + 32 }]}
        >
          {isBot && (
            <View style={[styles.botBanner, { backgroundColor: Colors.purplePale + '22', borderColor: Colors.purple + '30' }]}>
              <Ionicons name="hardware-chip-outline" size={16} color={Colors.purple} />
              <Text style={[styles.botBannerText, { color: Colors.purple }]}>NinoWorld Bot · Automated opponent</Text>
            </View>
          )}

          {/* ── HERO CARD ── */}
          <View style={[styles.heroCard, { backgroundColor: colors.card, borderColor: colors.border }]}>
            <LinearGradient
              colors={[character.accentColor + 'CC', character.accentColor + '33', colors.card + '00']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              style={styles.heroBanner}
            />

            <View style={styles.heroAvatarWrap}>
              <View style={[styles.heroAvatarRing, { borderColor: character.accentColor + '80' }]}>
                <BouncingCharacter character={character} size={110} reactionType="idle" />
              </View>
              {statusLabel && (
                <View style={[styles.statusPill, { backgroundColor: statusColor + '22', borderColor: statusColor + '55' }]}>
                  <View style={[styles.statusDot, { backgroundColor: statusColor }]} />
                  <Text style={[styles.statusText, { color: statusColor }]}>{statusLabel}</Text>
                </View>
              )}
            </View>

            <View style={styles.heroInfo}>
              <View style={styles.nameRow}>
                <Text style={[styles.playerName, { color: character.accentColor }]}>{publicProfile.name}</Text>
                {publicProfile.hasSubscription && (
                  <View style={[styles.premiumPill, { backgroundColor: character.accentColor }]}>
                    <Ionicons name="star" size={10} color={Colors.white} />
                    <Text style={styles.premiumPillText}>Pro</Text>
                  </View>
                )}
              </View>
              <View style={styles.subtitleRow}>
                <Text style={[styles.charSubtitle, { color: colors.textSecondary }]}>{character.name} · {character.personality}</Text>
                {!!publicProfile.country && getCountryByIso(publicProfile.country) && (
                  <View style={[styles.countryPill, { backgroundColor: colors.subtleBg }]}>
                    <Text style={styles.countryFlag}>{getFlag(publicProfile.country)}</Text>
                    <Text style={[styles.countryIso, { color: colors.textMuted }]}>{publicProfile.country.toUpperCase()}</Text>
                  </View>
                )}
              </View>

              {!isBot && (
                <View style={styles.socialRow}>
                  <View style={styles.socialChip}>
                    <Text style={[styles.socialCount, { color: colors.textPrimary }]}>{followerCount}</Text>
                    <Text style={[styles.socialLabel, { color: colors.textMuted }]}>Followers</Text>
                  </View>
                  <View style={[styles.socialDivider, { backgroundColor: colors.border }]} />
                  <View style={styles.socialChip}>
                    <Text style={[styles.socialCount, { color: colors.textPrimary }]}>{followingCount}</Text>
                    <Text style={[styles.socialLabel, { color: colors.textMuted }]}>Following</Text>
                  </View>
                </View>
              )}
            </View>
          </View>

          {/* ── STATS STRIP ── */}
          <View style={[styles.statsStrip, { backgroundColor: colors.card, borderColor: colors.border }]}>
            {[
              { label: 'W', value: publicProfile.stats.totalWins, color: Colors.green },
              { label: 'L', value: publicProfile.stats.totalLosses, color: Colors.coral },
              { label: 'D', value: publicProfile.stats.totalDraws, color: Colors.blue },
              { label: 'Rate', value: `${winRate}%`, color: Colors.yellow },
              { label: 'Streak', value: publicProfile.stats.streak, color: Colors.purple },
              { label: 'Best', value: publicProfile.stats.bestStreak, color: Colors.pink },
            ].map((s, i) => (
              <View key={i} style={styles.statItem}>
                <Text style={[styles.statNum, { color: s.color }]}>{s.value}</Text>
                <Text style={[styles.statLabel, { color: colors.textMuted }]}>{s.label}</Text>
              </View>
            ))}
          </View>

          {/* ── ACTION BAR ── */}
          {!isMe && !isBot && (
            <View style={styles.actionBar}>
              <Pressable
                onPress={handleFollow}
                style={[
                  styles.followBtn,
                  isMutual
                    ? { backgroundColor: Colors.green + '22', borderColor: Colors.green + '55' }
                    : isFollowing
                      ? { backgroundColor: character.accentColor + '22', borderColor: character.accentColor + '55' }
                      : { backgroundColor: character.accentColor, borderColor: character.accentColor },
                ]}
              >
                <Ionicons
                  name={isMutual ? 'people' : isFollowing ? 'checkmark' : 'person-add'}
                  size={16}
                  color={isMutual ? Colors.green : isFollowing ? character.accentColor : Colors.white}
                />
                <Text style={[
                  styles.followBtnText,
                  isMutual && { color: Colors.green },
                  !isMutual && isFollowing && { color: character.accentColor },
                  !isMutual && !isFollowing && { color: Colors.white },
                ]}>
                  {isMutual ? 'Mutual' : isFollowing ? 'Following' : 'Follow'}
                </Text>
              </Pressable>

              <Pressable
                onPress={isOnline && !isPlaying && !challengeSent ? handleChallenge : undefined}
                style={[
                  styles.challengeBtn,
                  isOnline && !isPlaying && !challengeWaiting
                    ? { backgroundColor: Colors.purple }
                    : { backgroundColor: colors.card, opacity: challengeWaiting ? 0.85 : 0.5 },
                ]}
              >
                {challengeWaiting ? (
                  <>
                    <Ionicons name="time-outline" size={16} color={Colors.yellow} />
                    <Text style={[styles.challengeBtnText, { color: Colors.yellow }]}>Waiting…</Text>
                  </>
                ) : challengeSent ? (
                  <>
                    <Ionicons name="checkmark-circle" size={16} color={Colors.white} />
                    <Text style={[styles.challengeBtnText, { color: Colors.white }]}>Sent!</Text>
                  </>
                ) : (
                  <>
                    <Ionicons
                      name="flash"
                      size={16}
                      color={isOnline && !isPlaying ? Colors.white : colors.textMuted}
                    />
                    <Text style={[styles.challengeBtnText, { color: isOnline && !isPlaying ? Colors.white : colors.textMuted }]}>
                      {isPlaying ? 'In Match' : isOnline ? 'Challenge' : 'Offline'}
                    </Text>
                  </>
                )}
              </Pressable>
            </View>
          )}

          {isMe && (
            <View style={[styles.isYouCard, { backgroundColor: Colors.blue + '15', borderColor: Colors.blue + '30' }]}>
              <Ionicons name="person-circle" size={20} color={Colors.blue} />
              <Text style={[styles.isYouText, { color: Colors.blue }]}>This is your profile</Text>
            </View>
          )}

          {isBot && (
            <View style={[styles.isYouCard, { backgroundColor: Colors.purple + '15', borderColor: Colors.purple + '30' }]}>
              <Ionicons name="hardware-chip-outline" size={20} color={Colors.purple} />
              <Text style={[styles.isYouText, { color: Colors.purple }]}>Automated bot opponent</Text>
            </View>
          )}

          {/* ── BADGES RIBBON ── */}
          <View style={[styles.section, { backgroundColor: colors.card, borderColor: colors.border }]}>
            <View style={styles.sectionHeader}>
              <Ionicons name="ribbon" size={16} color={Colors.yellow} />
              <Text style={[styles.sectionTitle, { color: colors.textPrimary }]}>Badges</Text>
              <Text style={[styles.sectionCount, { color: colors.textMuted }]}>{earnedBadges.length}/{BADGES.length}</Text>
            </View>
            {earnedBadges.length === 0 ? (
              <View style={styles.emptyBadges}>
                <Text style={[styles.emptyBadgesText, { color: colors.textMuted }]}>No badges earned yet</Text>
              </View>
            ) : (
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.badgesRibbon}>
                {earnedBadges.map((badge) => (
                  <Pressable
                    key={badge.id}
                    style={[styles.badgePip, { backgroundColor: badge.pale, borderColor: badge.color + '40' }]}
                    onPress={() => setSelectedBadge(badge)}
                  >
                    <View style={[styles.badgeIconCircle, { backgroundColor: badge.color }]}>
                      <Ionicons name={badge.icon as 'trophy'} size={18} color={Colors.white} />
                    </View>
                    <Text style={[styles.badgeName, { color: colors.textSecondary }]}>{badge.name}</Text>
                  </Pressable>
                ))}
              </ScrollView>
            )}
          </View>
        </ScrollView>
      )}

      {/* ── CHALLENGE TOAST ── */}
      <Animated.View
        pointerEvents="none"
        style={[styles.toast, { bottom: insets.bottom + 24, opacity: toastOpacity }]}
      >
        <Ionicons name="checkmark-circle" size={18} color={Colors.white} />
        <Text style={styles.toastText}>Invite sent!</Text>
      </Animated.View>

      {/* ── BADGE TOOLTIP MODAL ── */}
      <Modal
        visible={!!selectedBadge}
        transparent
        animationType="fade"
        onRequestClose={() => setSelectedBadge(null)}
      >
        <Pressable style={styles.modalBackdrop} onPress={() => setSelectedBadge(null)}>
          {selectedBadge && (
            <View style={[styles.badgeModal, { backgroundColor: colors.card, borderColor: selectedBadge.color + '40' }]}>
              <View style={[styles.badgeModalIcon, { backgroundColor: selectedBadge.color }]}>
                <Ionicons name={selectedBadge.icon as 'trophy'} size={32} color={Colors.white} />
              </View>
              <Text style={[styles.badgeModalName, { color: colors.textPrimary }]}>{selectedBadge.name}</Text>
              <Text style={[styles.badgeModalDesc, { color: colors.textSecondary }]}>{selectedBadge.description}</Text>
              <Pressable
                style={[styles.badgeModalClose, { backgroundColor: selectedBadge.color }]}
                onPress={() => setSelectedBadge(null)}
              >
                <Text style={styles.badgeModalCloseText}>Got it</Text>
              </Pressable>
            </View>
          )}
        </Pressable>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },

  toolbar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 8,
    paddingVertical: 8,
  },
  toolbarBtn: { width: 44, height: 44, alignItems: 'center', justifyContent: 'center' },
  toolbarTitle: { fontSize: 17, fontFamily: 'Inter_600SemiBold' },

  scrollContent: { paddingHorizontal: 16, gap: 14, paddingTop: 4 },

  /* ── Skeleton ── */
  skeletonWrap: { borderRadius: 28, overflow: 'hidden' },
  skeletonBanner: { height: 100 },
  skeletonAvatarWrap: { alignItems: 'center', marginTop: -40 },
  skeletonAvatar: { width: 80, height: 80, borderRadius: 40 },
  skeletonBody: { padding: 20, gap: 12, alignItems: 'center' },
  skeletonLine: { height: 16, width: '70%', borderRadius: 8 },
  skeletonStatsRow: { flexDirection: 'row', gap: 8, marginTop: 4 },
  skeletonStat: { flex: 1, height: 48, borderRadius: 12 },

  /* ── Not Found ── */
  notFound: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 14, paddingHorizontal: 40 },
  notFoundEmoji: { fontSize: 56 },
  notFoundTitle: { fontSize: 22, fontFamily: 'Inter_700Bold' },
  notFoundDesc: { fontSize: 14, fontFamily: 'Inter_400Regular', textAlign: 'center', lineHeight: 20 },
  backLinkBtn: { marginTop: 8, paddingHorizontal: 24, paddingVertical: 12, borderRadius: 14 },
  backLinkText: { fontSize: 14, fontFamily: 'Inter_600SemiBold' },

  /* ── Bot Banner ── */
  botBanner: {
    flexDirection: 'row', alignItems: 'center', gap: 8,
    borderRadius: 14,
    paddingHorizontal: 14, paddingVertical: 10,
    borderWidth: 1,
  },
  botBannerText: { fontSize: 13, fontFamily: 'Inter_600SemiBold' },

  /* ── Hero Card ── */
  heroCard: {
    borderRadius: 28,
    overflow: 'hidden',
    paddingBottom: 20,
    borderWidth: 1,
  },
  heroBanner: { height: 110, width: '100%' },
  heroAvatarWrap: { alignItems: 'center', marginTop: -50 },
  heroAvatarRing: {
    width: 122, height: 122, borderRadius: 61,
    borderWidth: 3,
    alignItems: 'center', justifyContent: 'center',
    overflow: 'hidden',
  },
  statusPill: {
    flexDirection: 'row', alignItems: 'center', gap: 5,
    paddingHorizontal: 10, paddingVertical: 4,
    borderRadius: 20, borderWidth: 1,
    marginTop: 8,
  },
  statusDot: { width: 7, height: 7, borderRadius: 4 },
  statusText: { fontSize: 11, fontFamily: 'Inter_600SemiBold' },
  heroInfo: { paddingHorizontal: 20, paddingTop: 12, gap: 6 },
  nameRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  playerName: { fontSize: 24, fontFamily: 'Inter_700Bold' },
  premiumPill: {
    flexDirection: 'row', alignItems: 'center', gap: 3,
    paddingHorizontal: 8, paddingVertical: 3, borderRadius: 8,
  },
  premiumPillText: { fontSize: 10, fontFamily: 'Inter_700Bold', color: Colors.white },
  subtitleRow: { flexDirection: 'row', alignItems: 'center', gap: 8, flexWrap: 'wrap' },
  charSubtitle: { fontSize: 13, fontFamily: 'Inter_400Regular' },
  countryPill: { flexDirection: 'row', alignItems: 'center', gap: 4, borderRadius: 8, paddingHorizontal: 7, paddingVertical: 3 },
  countryFlag: { fontSize: 14 },
  countryIso: { fontSize: 11, fontFamily: 'Inter_600SemiBold' },
  socialRow: { flexDirection: 'row', alignItems: 'center', marginTop: 4, gap: 0 },
  socialChip: { alignItems: 'center', paddingHorizontal: 16, paddingVertical: 6 },
  socialCount: { fontSize: 18, fontFamily: 'Inter_700Bold' },
  socialLabel: { fontSize: 11, fontFamily: 'Inter_400Regular' },
  socialDivider: { width: 1, height: 28, marginHorizontal: 4 },

  /* ── Stats Strip ── */
  statsStrip: {
    flexDirection: 'row',
    borderRadius: 20, padding: 16, borderWidth: 1,
    justifyContent: 'space-between',
  },
  statItem: { alignItems: 'center', gap: 4, flex: 1 },
  statNum: { fontSize: 18, fontFamily: 'Inter_700Bold' },
  statLabel: { fontSize: 10, fontFamily: 'Inter_500Medium' },

  /* ── Action Bar ── */
  actionBar: { flexDirection: 'row', gap: 10 },
  followBtn: {
    flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6,
    borderRadius: 16, paddingVertical: 14,
    borderWidth: 1.5,
  },
  followBtnText: { fontSize: 15, fontFamily: 'Inter_600SemiBold' },
  challengeBtn: {
    flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6,
    borderRadius: 16, paddingVertical: 14,
  },
  challengeBtnText: { fontSize: 15, fontFamily: 'Inter_600SemiBold' },

  isYouCard: {
    flexDirection: 'row', alignItems: 'center', gap: 8,
    borderRadius: 14, padding: 14,
    borderWidth: 1,
  },
  isYouText: { fontSize: 14, fontFamily: 'Inter_500Medium' },

  /* ── Badges ── */
  section: { borderRadius: 20, padding: 16, gap: 12, borderWidth: 1 },
  sectionHeader: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  sectionTitle: { flex: 1, fontSize: 15, fontFamily: 'Inter_600SemiBold' },
  sectionCount: { fontSize: 13, fontFamily: 'Inter_400Regular' },
  emptyBadges: { paddingVertical: 8 },
  emptyBadgesText: { fontSize: 13, fontFamily: 'Inter_400Regular' },
  badgesRibbon: { gap: 10, paddingVertical: 4 },
  badgePip: {
    alignItems: 'center', gap: 6, paddingHorizontal: 12, paddingVertical: 10,
    borderRadius: 16, borderWidth: 1,
  },
  badgeIconCircle: { width: 36, height: 36, borderRadius: 18, alignItems: 'center', justifyContent: 'center' },
  badgeName: { fontSize: 11, fontFamily: 'Inter_500Medium' },

  /* ── Toast ── */
  toast: {
    position: 'absolute',
    alignSelf: 'center',
    flexDirection: 'row', alignItems: 'center', gap: 8,
    backgroundColor: 'rgba(0,0,0,0.8)',
    paddingHorizontal: 16, paddingVertical: 10,
    borderRadius: 20,
  },
  toastText: { fontSize: 14, fontFamily: 'Inter_600SemiBold', color: Colors.white },

  /* ── Badge Modal ── */
  modalBackdrop: {
    flex: 1, backgroundColor: 'rgba(0,0,0,0.6)',
    alignItems: 'center', justifyContent: 'center', padding: 32,
  },
  badgeModal: {
    width: '100%', borderRadius: 24, padding: 24,
    alignItems: 'center', gap: 10,
    borderWidth: 1,
  },
  badgeModalIcon: { width: 72, height: 72, borderRadius: 22, alignItems: 'center', justifyContent: 'center', marginBottom: 4 },
  badgeModalName: { fontSize: 20, fontFamily: 'Inter_700Bold' },
  badgeModalDesc: { fontSize: 14, fontFamily: 'Inter_400Regular', textAlign: 'center', lineHeight: 20 },
  badgeModalClose: { marginTop: 8, paddingHorizontal: 28, paddingVertical: 12, borderRadius: 14 },
  badgeModalCloseText: { fontSize: 15, fontFamily: 'Inter_600SemiBold', color: Colors.white },
});
