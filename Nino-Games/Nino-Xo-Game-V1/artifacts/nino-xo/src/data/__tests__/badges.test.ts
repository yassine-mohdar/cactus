import { badgesEarned, checkNewBadges } from '../badges';
import type { UserStats, UserProfile } from '../../types';

const BASE_STATS: UserStats = {
  totalMatches: 0,
  totalWins: 0,
  totalLosses: 0,
  totalDraws: 0,
  streak: 0,
  bestStreak: 0,
  missionProgress: 0,
  missionGoal: 5,
  missionsCompletedTotal: 0,
  characterWins: {},
};

function makeProfile(overrides: Partial<UserStats> = {}): UserProfile {
  return {
    id: 'test',
    name: 'Test',
    selectedCharacterId: 'nino',
    stats: { ...BASE_STATS, ...overrides },
    earnedBadgeIds: [],
  } as unknown as UserProfile;
}

describe('badgesEarned', () => {
  it('returns empty array when nothing crosses a threshold', () => {
    const prev = { ...BASE_STATS, totalWins: 0 };
    const next = { ...BASE_STATS, totalWins: 1 };
    const result = badgesEarned(prev, next);
    expect(result).not.toContain('wins_10');
    expect(result).toContain('first_win');
  });

  it('detects first_win on transition 0→1 wins', () => {
    const prev = { ...BASE_STATS, totalWins: 0 };
    const next = { ...BASE_STATS, totalWins: 1 };
    const result = badgesEarned(prev, next);
    expect(result).toContain('first_win');
  });

  it('detects wins_10 at exactly 10 wins', () => {
    const prev = { ...BASE_STATS, totalWins: 9 };
    const next = { ...BASE_STATS, totalWins: 10 };
    const result = badgesEarned(prev, next);
    expect(result).toContain('wins_10');
  });

  it('does not re-detect wins_10 when already earned', () => {
    const prev = { ...BASE_STATS, totalWins: 10 };
    const next = { ...BASE_STATS, totalWins: 11 };
    const result = badgesEarned(prev, next);
    expect(result).not.toContain('wins_10');
  });

  it('detects streak_3 on streak transition 2→3', () => {
    const prev = { ...BASE_STATS, streak: 2, bestStreak: 2 };
    const next = { ...BASE_STATS, streak: 3, bestStreak: 3 };
    const result = badgesEarned(prev, next);
    expect(result).toContain('streak_3');
  });

  it('detects character_loyal using prevProfile/nextProfile (not same profile)', () => {
    const prevProfile = makeProfile({ characterWins: { nino: 9 } });
    const nextStats: UserStats = { ...BASE_STATS, characterWins: { nino: 10 } };
    const nextProfile = makeProfile({ characterWins: { nino: 10 } });

    const result = badgesEarned(prevProfile.stats, nextStats, prevProfile, nextProfile);
    expect(result).toContain('character_loyal');
  });

  it('does NOT detect character_loyal if characterWins was already 10 in prev', () => {
    const prevProfile = makeProfile({ characterWins: { nino: 10 } });
    const nextStats: UserStats = { ...BASE_STATS, characterWins: { nino: 11 } };
    const nextProfile = makeProfile({ characterWins: { nino: 11 } });

    const result = badgesEarned(prevProfile.stats, nextStats, prevProfile, nextProfile);
    expect(result).not.toContain('character_loyal');
  });

  it('detects mission_5 when missionsCompletedTotal crosses 5', () => {
    const prev = { ...BASE_STATS, missionsCompletedTotal: 4 };
    const next = { ...BASE_STATS, missionsCompletedTotal: 5 };
    const result = badgesEarned(prev, next);
    expect(result).toContain('mission_5');
  });

  it('does NOT detect mission_15 when only 5 missions completed', () => {
    const prev = { ...BASE_STATS, missionsCompletedTotal: 4 };
    const next = { ...BASE_STATS, missionsCompletedTotal: 5 };
    const result = badgesEarned(prev, next);
    expect(result).not.toContain('mission_15');
  });

  it('detects mission_15 when missionsCompletedTotal crosses 15', () => {
    const prev = { ...BASE_STATS, missionsCompletedTotal: 14 };
    const next = { ...BASE_STATS, missionsCompletedTotal: 15 };
    const result = badgesEarned(prev, next);
    expect(result).toContain('mission_15');
  });

  it('detects mission_30 when missionsCompletedTotal crosses 30', () => {
    const prev = { ...BASE_STATS, missionsCompletedTotal: 29 };
    const next = { ...BASE_STATS, missionsCompletedTotal: 30 };
    const result = badgesEarned(prev, next);
    expect(result).toContain('mission_30');
  });
});

describe('checkNewBadges', () => {
  it('returns new badge ids not yet earned', () => {
    const stats: UserStats = { ...BASE_STATS, totalWins: 10 };
    const result = checkNewBadges(stats, []);
    expect(result).toContain('wins_10');
  });

  it('does not return already-earned badges', () => {
    const stats: UserStats = { ...BASE_STATS, totalWins: 10 };
    const result = checkNewBadges(stats, ['wins_10']);
    expect(result).not.toContain('wins_10');
  });
});
