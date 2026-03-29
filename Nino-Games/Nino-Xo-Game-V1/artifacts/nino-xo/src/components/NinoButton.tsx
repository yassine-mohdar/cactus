import React from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, ViewStyle } from 'react-native';
import Animated, { useAnimatedStyle, useSharedValue, withSpring } from 'react-native-reanimated';
import { Colors } from '../../constants/colors';
import { audioService } from '../services/audioService';
import { useTheme } from '@/src/context/ThemeContext';

type Variant = 'primary' | 'secondary' | 'ghost' | 'danger';

interface Props {
  onPress: () => void;
  label: string;
  variant?: Variant;
  disabled?: boolean;
  loading?: boolean;
  color?: string;
  style?: ViewStyle;
  fullWidth?: boolean;
}

export function NinoButton({ onPress, label, variant = 'primary', disabled, loading, color, style, fullWidth }: Props) {
  const { colors } = useTheme();
  const scale = useSharedValue(1);

  const VARIANT_STYLES = {
    primary: { bg: Colors.green, text: Colors.white },
    secondary: { bg: colors.inputBg, text: colors.textPrimary },
    ghost: { bg: 'transparent', text: colors.textSecondary },
    danger: { bg: Colors.coral, text: Colors.white },
  };

  const vs = VARIANT_STYLES[variant];
  const bgColor = color ?? vs.bg;

  const animStyle = useAnimatedStyle(() => ({ transform: [{ scale: scale.value }] }));

  return (
    <Pressable
      onPress={disabled || loading ? undefined : () => { audioService.playTap(); onPress(); }}
      onPressIn={() => { scale.value = withSpring(0.96); }}
      onPressOut={() => { scale.value = withSpring(1); }}
      style={[fullWidth && { width: '100%' }]}
    >
      <Animated.View
        style={[
          styles.button,
          { backgroundColor: bgColor, opacity: disabled ? 0.5 : 1 },
          fullWidth && styles.fullWidth,
          style,
        ]}
      >
        {loading ? (
          <ActivityIndicator size="small" color={vs.text} />
        ) : (
          <Text style={[styles.label, { color: vs.text }]}>{label}</Text>
        )}
      </Animated.View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  button: {
    paddingVertical: 16,
    paddingHorizontal: 28,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000',
    shadowOpacity: 0.07,
    shadowOffset: { width: 0, height: 3 },
    shadowRadius: 8,
    elevation: 3,
    minHeight: 54,
  },
  fullWidth: {
    width: '100%',
  },
  label: {
    fontSize: 16,
    fontFamily: 'Inter_600SemiBold',
    letterSpacing: 0.2,
  },
});
