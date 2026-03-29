import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import Animated, {
  useAnimatedStyle,
  useSharedValue,
  withRepeat,
  withSequence,
  withTiming,
} from 'react-native-reanimated';
import { Colors } from '../../constants/colors';
import { Player } from '../types';
import { getCharacter } from '../data/characters';
import { CharacterAvatar } from './CharacterAvatar';
import { useTheme } from '@/src/context/ThemeContext';

interface Props {
  player: Player;
  isCurrentTurn: boolean;
  isRight?: boolean;
  isWinner?: boolean;
}

export function PlayerHeader({ player, isCurrentTurn, isRight = false, isWinner }: Props) {
  const { colors } = useTheme();
  const character = getCharacter(player.characterId);
  const glow = useSharedValue(0);

  React.useEffect(() => {
    if (isCurrentTurn) {
      glow.value = withRepeat(
        withSequence(
          withTiming(1, { duration: 700 }),
          withTiming(0.3, { duration: 700 })
        ),
        -1, true
      );
    } else {
      glow.value = withTiming(0);
    }
  }, [isCurrentTurn]);

  const glowStyle = useAnimatedStyle(() => ({
    opacity: glow.value,
    shadowOpacity: glow.value * 0.4,
  }));

  return (
    <View style={[styles.container, isRight && styles.containerRight]}>
      <Animated.View
        style={[
          styles.glowRing,
          { borderColor: character.accentColor },
          glowStyle,
        ]}
      />
      <CharacterAvatar
        character={character}
        size={56}
        showRing={isCurrentTurn || !!isWinner}
      />
      <View style={[styles.info, isRight && styles.infoRight]}>
        <Text style={[styles.name, { color: colors.textPrimary }]} numberOfLines={1}>{player.name}</Text>
        <Text style={[styles.charName, { color: character.accentColor }]}>{character.name}</Text>
        {isCurrentTurn && (
          <View style={[styles.turnBadge, { backgroundColor: character.accentColor }]}>
            <Text style={styles.turnText}>Your turn</Text>
          </View>
        )}
        {isWinner && (
          <View style={[styles.turnBadge, { backgroundColor: Colors.winGold }]}>
            <Text style={styles.turnText}>Winner!</Text>
          </View>
        )}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    flex: 1,
  },
  containerRight: {
    flexDirection: 'row-reverse',
  },
  glowRing: {
    position: 'absolute',
    width: 64,
    height: 64,
    borderRadius: 32,
    borderWidth: 3,
  },
  info: {
    flex: 1,
    gap: 2,
  },
  infoRight: {
    alignItems: 'flex-end',
  },
  name: {
    fontSize: 14,
    fontFamily: 'Inter_600SemiBold',
  },
  charName: {
    fontSize: 12,
    fontFamily: 'Inter_400Regular',
  },
  turnBadge: {
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 8,
    marginTop: 2,
    alignSelf: 'flex-start',
  },
  turnText: {
    fontSize: 10,
    fontFamily: 'Inter_600SemiBold',
    color: '#FFFFFF',
    letterSpacing: 0.3,
  },
});
