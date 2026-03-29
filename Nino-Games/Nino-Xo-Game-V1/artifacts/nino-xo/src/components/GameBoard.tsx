import React from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import Animated, {
  useAnimatedStyle,
  useSharedValue,
  withSequence,
  withTiming,
} from 'react-native-reanimated';
import { Colors } from '../../constants/colors';
import { audioService } from '../services/audioService';
import { Board, Player, WinLine } from '../types';
import { getCharacter } from '../data/characters';
import { CharacterToken } from './CharacterToken';

interface CellProps {
  cell: string | null;
  index: number;
  players: [Player, Player];
  onPress: (i: number) => void;
  disabled: boolean;
  isWinCell: boolean;
  activeColor: string;
}

function BoardCell({ cell, index, players, onPress, disabled, isWinCell, activeColor }: CellProps) {
  const scale = useSharedValue(1);
  const ringOpacity = useSharedValue(0);

  const handlePressIn = () => {
    if (disabled || cell) return;
    ringOpacity.value = withTiming(1, { duration: 60 });
  };

  const handlePressOut = () => {
    ringOpacity.value = withTiming(0, { duration: 120 });
  };

  const handlePress = () => {
    if (disabled || cell) return;
    scale.value = withSequence(withTiming(0.94, { duration: 60 }), withTiming(1, { duration: 70 }));
    audioService.playMove();
    onPress(index);
  };

  const animStyle = useAnimatedStyle(() => ({
    transform: [{ scale: scale.value }],
  }));

  const ringStyle = useAnimatedStyle(() => ({
    opacity: ringOpacity.value,
  }));

  const player = cell ? players.find(p => p.symbol === cell) : null;
  const character = player ? getCharacter(player.characterId) : null;

  return (
    <Pressable
      onPressIn={handlePressIn}
      onPressOut={handlePressOut}
      onPress={handlePress}
      style={styles.cellPressable}
    >
      <Animated.View
        style={[
          styles.cell,
          isWinCell && styles.winCell,
          isWinCell && character && { backgroundColor: character.accentPale, borderColor: character.accentColor, borderWidth: 2 },
          animStyle,
        ]}
      >
        {!cell && !disabled && (
          <Animated.View style={[styles.pressRing, { borderColor: activeColor }, ringStyle]} />
        )}
        {character && (
          <CharacterToken character={character} size={74} animate={true} />
        )}
      </Animated.View>
    </Pressable>
  );
}

interface Props {
  board: Board;
  players: [Player, Player];
  onMove: (index: number) => void;
  disabled: boolean;
  winLine: WinLine;
  activeColor?: string;
}

export function GameBoard({ board, players, onMove, disabled, winLine, activeColor = Colors.textMid }: Props) {
  return (
    <View style={styles.board}>
      {board.map((cell, i) => (
        <BoardCell
          key={i}
          cell={cell}
          index={i}
          players={players}
          onPress={onMove}
          disabled={disabled}
          isWinCell={winLine?.includes(i) ?? false}
          activeColor={activeColor}
        />
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  board: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    width: 342,
    height: 342,
    gap: 6,
  },
  cellPressable: {
    width: 110,
    height: 110,
  },
  cell: {
    width: 110,
    height: 110,
    borderRadius: 22,
    backgroundColor: Colors.white,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowOffset: { width: 0, height: 2 },
    shadowRadius: 8,
    elevation: 2,
  },
  winCell: {
    shadowOpacity: 0.15,
    shadowRadius: 12,
    elevation: 6,
  },
  pressRing: {
    position: 'absolute',
    width: 96,
    height: 96,
    borderRadius: 18,
    borderWidth: 2.5,
    backgroundColor: 'transparent',
  },
});
