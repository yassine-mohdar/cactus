import React from 'react';
import { Image, StyleSheet, View, ViewStyle } from 'react-native';
import { Character } from '../types';

interface Props {
  character: Character;
  size?: number;
  showRing?: boolean;
  style?: ViewStyle;
}

export function CharacterAvatar({ character, size = 64, showRing = false, style }: Props) {
  return (
    <View
      style={[
        styles.container,
        {
          width: size,
          height: size,
          borderRadius: size / 2,
          backgroundColor: character.accentPale,
          borderWidth: showRing ? 2.5 : 0,
          borderColor: character.accentColor,
        },
        style,
      ]}
    >
      <Image
        source={character.image}
        style={{ width: size * 0.9, height: size * 0.9 }}
        resizeMode="contain"
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
});
