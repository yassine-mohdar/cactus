import React, { useEffect } from 'react';
import { Image, StyleSheet } from 'react-native';
import Animated, {
  useAnimatedStyle,
  useSharedValue,
  withSequence,
  withSpring,
  withTiming,
  Easing,
} from 'react-native-reanimated';
import { Character } from '../types';

interface Props {
  character: Character;
  size?: number;
  animate?: boolean;
}

export function CharacterToken({ character, size = 48, animate = false }: Props) {
  const scale = useSharedValue(animate ? 0 : 1);
  const rotation = useSharedValue(0);

  useEffect(() => {
    if (animate) {
      scale.value = withSequence(
        withSpring(1.1, { stiffness: 300, damping: 12 }),
        withSpring(1.0, { stiffness: 200, damping: 15 })
      );
      rotation.value = withSequence(
        withTiming(-4, { duration: 60, easing: Easing.out(Easing.quad) }),
        withTiming(4, { duration: 70 }),
        withTiming(0, { duration: 55 })
      );
    }
  }, [animate]);

  const animStyle = useAnimatedStyle(() => ({
    transform: [{ scale: scale.value }, { rotate: `${rotation.value}deg` }],
  }));

  return (
    <Animated.View style={[styles.container, { width: size, height: size, borderRadius: size / 2, backgroundColor: character.accentPale }, animStyle]}>
      <Image
        source={character.image}
        style={{ width: size * 0.85, height: size * 0.85 }}
        resizeMode="contain"
      />
    </Animated.View>
  );
}

const styles = StyleSheet.create({
  container: {
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
});
