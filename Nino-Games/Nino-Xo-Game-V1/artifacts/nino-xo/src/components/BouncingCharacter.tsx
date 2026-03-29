import React, { useEffect } from 'react';
import { Image, StyleSheet } from 'react-native';
import Animated, {
  useAnimatedStyle,
  useSharedValue,
  withRepeat,
  withSequence,
  withSpring,
  withTiming,
  Easing,
} from 'react-native-reanimated';
import { Character } from '../types';

interface Props {
  character: Character;
  size?: number;
  looping?: boolean;
  reactionType?: 'idle' | 'win' | 'lose' | 'search' | 'found';
}

export function BouncingCharacter({ character, size = 120, looping = true, reactionType = 'idle' }: Props) {
  const translateY = useSharedValue(0);
  const scale = useSharedValue(1);
  const rotation = useSharedValue(0);

  useEffect(() => {
    if (reactionType === 'idle' || reactionType === 'search') {
      translateY.value = withRepeat(
        withSequence(
          withTiming(-8, { duration: 600, easing: Easing.inOut(Easing.sin) }),
          withTiming(0, { duration: 600, easing: Easing.inOut(Easing.sin) })
        ),
        -1, true
      );
    } else if (reactionType === 'win') {
      translateY.value = withRepeat(
        withSequence(
          withSpring(-20, { damping: 6 }),
          withSpring(0, { damping: 8 })
        ),
        3, false
      );
      scale.value = withSequence(
        withSpring(1.2, { damping: 6 }),
        withSpring(1.0, { damping: 10 })
      );
    } else if (reactionType === 'lose') {
      rotation.value = withSequence(
        withTiming(-15, { duration: 200 }),
        withTiming(15, { duration: 200 }),
        withTiming(-10, { duration: 150 }),
        withTiming(10, { duration: 150 }),
        withTiming(0, { duration: 100 })
      );
      translateY.value = withTiming(4, { duration: 400 });
    } else if (reactionType === 'found') {
      scale.value = withSequence(
        withSpring(1.3, { damping: 5 }),
        withSpring(1.0, { damping: 10 })
      );
      translateY.value = withSequence(
        withSpring(-16, { damping: 5 }),
        withSpring(0, { damping: 10 })
      );
    }
  }, [reactionType]);

  const animStyle = useAnimatedStyle(() => ({
    transform: [
      { translateY: translateY.value },
      { scale: scale.value },
      { rotate: `${rotation.value}deg` },
    ],
  }));

  return (
    <Animated.View style={[styles.container, { width: size, height: size }, animStyle]}>
      <Image
        source={character.image}
        style={styles.image}
        resizeMode="contain"
      />
    </Animated.View>
  );
}

const styles = StyleSheet.create({
  container: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  image: {
    width: '100%',
    height: '100%',
  },
});
