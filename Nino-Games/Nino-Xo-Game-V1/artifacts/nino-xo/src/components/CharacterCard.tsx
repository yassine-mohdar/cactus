import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import Animated, {
  useAnimatedStyle,
  useSharedValue,
  withSpring,
} from 'react-native-reanimated';
import { Character } from '../types';
import { CharacterAvatar } from './CharacterAvatar';
import { useTheme } from '@/src/context/ThemeContext';

interface Props {
  character: Character;
  selected: boolean;
  onSelect: () => void;
}

export function CharacterCard({ character, selected, onSelect }: Props) {
  const { colors } = useTheme();
  const scale = useSharedValue(1);

  const animStyle = useAnimatedStyle(() => ({
    transform: [{ scale: scale.value }],
  }));

  return (
    <Pressable
      onPressIn={() => { scale.value = withSpring(0.96); }}
      onPressOut={() => { scale.value = withSpring(1); }}
      onPress={onSelect}
    >
      <Animated.View
        style={[
          styles.card,
          { backgroundColor: colors.card, borderColor: colors.border },
          selected && { borderColor: character.accentColor, borderWidth: 2.5, backgroundColor: character.accentColor + '1A' },
          animStyle,
        ]}
      >
        <CharacterAvatar character={character} size={72} showRing={selected} />
        <Text style={[styles.name, { color: colors.textPrimary }, selected && { color: character.accentColor }]}>{character.name}</Text>
        <Text style={[styles.personality, { color: colors.textMuted }]}>{character.personality}</Text>
        {selected && (
          <View style={[styles.badge, { backgroundColor: character.accentColor }]}>
            <Text style={styles.badgeText}>Selected</Text>
          </View>
        )}
      </Animated.View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  card: {
    width: 110,
    alignItems: 'center',
    padding: 14,
    borderRadius: 20,
    gap: 6,
    shadowColor: '#000',
    shadowOpacity: 0.3,
    shadowOffset: { width: 0, height: 3 },
    shadowRadius: 10,
    elevation: 3,
    borderWidth: 2,
  },
  name: {
    fontSize: 13,
    fontFamily: 'Inter_600SemiBold',
    textAlign: 'center',
  },
  personality: {
    fontSize: 10,
    fontFamily: 'Inter_400Regular',
    textAlign: 'center',
    lineHeight: 14,
  },
  badge: {
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 8,
    marginTop: 2,
  },
  badgeText: {
    fontSize: 9,
    fontFamily: 'Inter_600SemiBold',
    color: '#FFFFFF',
    letterSpacing: 0.3,
  },
});
