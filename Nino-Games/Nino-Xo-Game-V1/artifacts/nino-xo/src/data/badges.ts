import type { ComponentProps } from 'react';
import type { default as Ionicons } from '@expo/vector-icons/Ionicons';
import type { UserProfile, UserStats } from '../types';

export type IoniconsName = ComponentProps<typeof Ionicons>['name'];

export interface Badge {
  id: string;
  name: string;
  description: string;
  icon: IoniconsName;
  color: string;
  pale: string;
  tier: 'bronze' | 'silver' | 'gold' | 'platinum';
  check: (stats: UserStats, profile?: UserProfile) => boolean;
}

export const BADGES: Badge[] = [
  {
    id: 'first_win',
    name: 'First Victory',
    description: 'Win your very first match',
    icon: 'trophy',
    color: '#CD7F32',
    pale: '#CD7F3220',
    tier: 'bronze',
    check: (s) => s.totalWins >= 1,
  },
  {
    id: 'streak_3',
    name: 'Hot Streak',
    description: 'Win 3 games in a row',
    icon: 'flame',
    color: '#EF4444',
    pale: '#EF444420',
    tier: 'bronze',
    check: (s) => s.bestStreak >= 3,
  },
  {
    id: 'streak_5',
    name: 'Unstoppable',
    description: 'Win 5 games in a row',
    icon: 'rocket',
    color: '#F97316',
    pale: '#F9731620',
    tier: 'silver',
    check: (s) => s.bestStreak >= 5,
  },
  {
    id: 'streak_10',
    name: 'Legendary Streak',
    description: 'Win 10 games in a row',
    icon: 'bonfire',
    color: '#DC2626',
    pale: '#DC262620',
    tier: 'gold',
    check: (s) => s.bestStreak >= 10,
  },
  {
    id: 'wins_10',
    name: 'Sharp Shooter',
    description: 'Win 10 matches total',
    icon: 'flash',
    color: '#F59E0B',
    pale: '#F59E0B20',
    tier: 'silver',
    check: (s) => s.totalWins >= 10,
  },
  {
    id: 'wins_25',
    name: 'Dominator',
    description: 'Win 25 matches total',
    icon: 'medal',
    color: '#F97316',
    pale: '#F9731620',
    tier: 'silver',
    check: (s) => s.totalWins >= 25,
  },
  {
    id: 'wins_50',
    name: 'Grand Champion',
    description: 'Win 50 matches total',
    icon: 'trophy',
    color: '#FFD700',
    pale: '#FFD70020',
    tier: 'gold',
    check: (s) => s.totalWins >= 50,
  },
  {
    id: 'veteran_10',
    name: 'Veteran',
    description: 'Play 10 matches',
    icon: 'game-controller',
    color: '#6C8EBF',
    pale: '#6C8EBF20',
    tier: 'bronze',
    check: (s) => s.totalMatches >= 10,
  },
  {
    id: 'matches_25',
    name: 'Dedicated',
    description: 'Play 25 matches',
    icon: 'star',
    color: '#8B5CF6',
    pale: '#8B5CF620',
    tier: 'silver',
    check: (s) => s.totalMatches >= 25,
  },
  {
    id: 'matches_50',
    name: 'Marathon Runner',
    description: 'Play 50 matches',
    icon: 'infinite',
    color: '#10B981',
    pale: '#10B98120',
    tier: 'silver',
    check: (s) => s.totalMatches >= 50,
  },
  {
    id: 'draw_5',
    name: 'Equilibrium',
    description: 'Draw 5 matches',
    icon: 'remove-circle',
    color: '#64748B',
    pale: '#64748B20',
    tier: 'bronze',
    check: (s) => (s.totalDraws ?? 0) >= 5,
  },
  {
    id: 'win_rate_50',
    name: 'Half & Half',
    description: 'Reach a 50% win rate (min 10 matches)',
    icon: 'stats-chart',
    color: '#06B6D4',
    pale: '#06B6D420',
    tier: 'bronze',
    check: (s) => s.totalMatches >= 10 && s.totalWins / s.totalMatches >= 0.5,
  },
  {
    id: 'win_rate_70',
    name: 'Sharpened Edge',
    description: 'Reach a 70% win rate (min 10 matches)',
    icon: 'trending-up',
    color: '#10B981',
    pale: '#10B98120',
    tier: 'silver',
    check: (s) => s.totalMatches >= 10 && s.totalWins / s.totalMatches >= 0.7,
  },
  {
    id: 'win_rate_90',
    name: 'Flawless',
    description: 'Reach a 90% win rate (min 10 matches)',
    icon: 'sparkles',
    color: '#F59E0B',
    pale: '#F59E0B20',
    tier: 'gold',
    check: (s) => s.totalMatches >= 10 && s.totalWins / s.totalMatches >= 0.9,
  },
  {
    id: 'character_loyal',
    name: 'True Believer',
    description: 'Win 10 matches with the same character',
    icon: 'heart',
    color: '#E87FA8',
    pale: '#E87FA820',
    tier: 'silver',
    check: (_s, profile) => {
      if (!profile) return false;
      const wins = profile.stats.characterWins ?? {};
      return Object.values(wins).some((v) => v >= 10);
    },
  },
  {
    id: 'mission_5',
    name: 'Quest Starter',
    description: 'Complete 5 daily missions',
    icon: 'flag',
    color: '#EC4899',
    pale: '#EC489920',
    tier: 'bronze',
    check: (s) => (s.missionsCompletedTotal ?? 0) >= 5,
  },
  {
    id: 'mission_15',
    name: 'Quest Master',
    description: 'Complete 15 daily missions',
    icon: 'flag',
    color: '#A855F7',
    pale: '#A855F720',
    tier: 'silver',
    check: (s) => (s.missionsCompletedTotal ?? 0) >= 15,
  },
  {
    id: 'mission_30',
    name: 'Quest Legend',
    description: 'Complete 30 daily missions',
    icon: 'ribbon',
    color: '#7C3AED',
    pale: '#7C3AED20',
    tier: 'gold',
    check: (s) => (s.missionsCompletedTotal ?? 0) >= 30,
  },
  {
    id: 'wins_100',
    name: 'NinoWorld Legend',
    description: 'Win 100 matches — you are elite',
    icon: 'diamond',
    color: '#06B6D4',
    pale: '#06B6D420',
    tier: 'platinum',
    check: (s) => s.totalWins >= 100,
  },
  {
    id: 'matches_100',
    name: 'Century Club',
    description: 'Play 100 matches — truly dedicated',
    icon: 'infinite',
    color: '#8B5CF6',
    pale: '#8B5CF620',
    tier: 'gold',
    check: (s) => s.totalMatches >= 100,
  },
];

export function getBadge(id: string): Badge | undefined {
  return BADGES.find((b) => b.id === id);
}

export function checkNewBadges(
  stats: UserStats,
  currentBadgeIds: string[],
  profile?: UserProfile,
): string[] {
  const earned = new Set(currentBadgeIds);
  return BADGES.filter((b) => !earned.has(b.id) && b.check(stats, profile)).map((b) => b.id);
}

export function badgesEarned(
  prev: UserStats,
  next: UserStats,
  prevProfile?: UserProfile,
  nextProfile?: UserProfile,
): string[] {
  const earnedAfter = new Set(
    BADGES.filter((b) => b.check(next, nextProfile ?? prevProfile)).map((b) => b.id),
  );
  const earnedBefore = new Set(
    BADGES.filter((b) => b.check(prev, prevProfile)).map((b) => b.id),
  );
  return [...earnedAfter].filter((id) => !earnedBefore.has(id));
}
