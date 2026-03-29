import { Ionicons } from '@expo/vector-icons';
import React, { useEffect, useRef, useState } from 'react';
import { Modal, Pressable, StyleSheet, Text, View } from 'react-native';
import Animated, {
  runOnJS,
  useAnimatedStyle,
  useSharedValue,
  withSpring,
  withTiming,
} from 'react-native-reanimated';
import { Colors } from '@/constants/colors';
import { getBadge, type Badge } from '@/src/data/badges';
import { useTheme } from '@/src/context/ThemeContext';

interface BadgeUnlockModalProps {
  badgeIds: string[];
  onDismissAll: () => void;
}

export function BadgeUnlockModal({ badgeIds, onDismissAll }: BadgeUnlockModalProps) {
  const { colors } = useTheme();
  const remainingRef = useRef<string[]>([]);
  const [currentBadge, setCurrentBadge] = useState<Badge | undefined>(undefined);
  const [visible, setVisible] = useState(false);

  const scale = useSharedValue(0.7);
  const opacity = useSharedValue(0);
  const translateY = useSharedValue(60);
  const overlayOpacity = useSharedValue(0);

  function showNext() {
    const next = remainingRef.current.shift();
    if (!next) {
      setVisible(false);
      setCurrentBadge(undefined);
      onDismissAll();
      return;
    }
    const badge = getBadge(next);
    if (!badge) {
      showNext();
      return;
    }
    setCurrentBadge(badge);
    setVisible(true);
  }

  useEffect(() => {
    if (badgeIds.length === 0) return;
    remainingRef.current = [...badgeIds];
    showNext();
  }, []);

  useEffect(() => {
    if (!visible || !currentBadge) return;
    scale.value = 0.85;
    opacity.value = 0;
    translateY.value = 60;
    overlayOpacity.value = 0;
    overlayOpacity.value = withTiming(1, { duration: 220 });
    scale.value = withSpring(1, { damping: 14, stiffness: 200 });
    translateY.value = withSpring(0, { damping: 18, stiffness: 220 });
    opacity.value = withTiming(1, { duration: 200 });
  }, [currentBadge]);

  function handleDismiss() {
    opacity.value = withTiming(0, { duration: 150 });
    scale.value = withTiming(0.85, { duration: 160 });
    translateY.value = withTiming(40, { duration: 160 });
    overlayOpacity.value = withTiming(0, { duration: 200 }, (finished) => {
      if (finished) {
        runOnJS(showNext)();
      }
    });
  }

  const cardStyle = useAnimatedStyle(() => ({
    transform: [{ scale: scale.value }, { translateY: translateY.value }],
    opacity: opacity.value,
  }));

  const overlayStyle = useAnimatedStyle(() => ({
    opacity: overlayOpacity.value,
  }));

  if (!visible || !currentBadge) return null;

  return (
    <Modal transparent animationType="none" statusBarTranslucent>
      <Animated.View style={[styles.overlay, overlayStyle]}>
        <Animated.View style={[styles.card, { backgroundColor: colors.card, borderColor: colors.borderMid }, cardStyle]}>
          <View style={styles.sparkleRow}>
            <Text style={styles.sparkle}>✦</Text>
            <Text style={styles.unlockLabel}>Badge Unlocked!</Text>
            <Text style={styles.sparkle}>✦</Text>
          </View>

          <View style={[styles.iconWrap, { backgroundColor: currentBadge.color }]}>
            <Ionicons name={currentBadge.icon} size={40} color={Colors.white} />
          </View>

          <Text style={[styles.badgeName, { color: currentBadge.color }]}>
            {currentBadge.name}
          </Text>

          <View style={[styles.descWrap, { backgroundColor: currentBadge.pale }]}>
            <Text style={[styles.badgeDesc, { color: colors.textSecondary }]}>{currentBadge.description}</Text>
          </View>

          <Pressable
            style={[styles.claimBtn, { backgroundColor: currentBadge.color }]}
            onPress={handleDismiss}
          >
            <Text style={styles.claimBtnText}>Awesome!</Text>
          </Pressable>
        </Animated.View>
      </Animated.View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.78)',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 32,
  },
  card: {
    borderRadius: 28,
    padding: 28,
    alignItems: 'center',
    gap: 14,
    width: '100%',
    borderWidth: 1,
    shadowColor: '#000',
    shadowOpacity: 0.4,
    shadowOffset: { width: 0, height: 12 },
    shadowRadius: 32,
    elevation: 20,
  },
  sparkleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  sparkle: {
    fontSize: 14,
    color: '#F2B84B',
  },
  unlockLabel: {
    fontSize: 13,
    fontFamily: 'Inter_700Bold',
    color: '#F2B84B',
    letterSpacing: 1.2,
    textTransform: 'uppercase',
  },
  iconWrap: {
    width: 90,
    height: 90,
    borderRadius: 28,
    alignItems: 'center',
    justifyContent: 'center',
    marginVertical: 4,
    shadowColor: '#000',
    shadowOpacity: 0.25,
    shadowOffset: { width: 0, height: 6 },
    shadowRadius: 16,
    elevation: 10,
  },
  badgeName: {
    fontSize: 26,
    fontFamily: 'Inter_700Bold',
    textAlign: 'center',
  },
  descWrap: {
    borderRadius: 14,
    paddingHorizontal: 16,
    paddingVertical: 10,
    width: '100%',
    alignItems: 'center',
  },
  badgeDesc: {
    fontSize: 13,
    fontFamily: 'Inter_400Regular',
    textAlign: 'center',
    lineHeight: 20,
  },
  claimBtn: {
    marginTop: 4,
    paddingVertical: 14,
    paddingHorizontal: 48,
    borderRadius: 16,
    alignItems: 'center',
    width: '100%',
  },
  claimBtnText: {
    fontSize: 16,
    fontFamily: 'Inter_700Bold',
    color: '#FFFFFF',
    letterSpacing: 0.3,
  },
});
