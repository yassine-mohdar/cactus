import { router } from 'expo-router';
import React, { useEffect, useRef } from 'react';
import { Image, StyleSheet, Text, View } from 'react-native';
import Animated, {
  useAnimatedStyle,
  useSharedValue,
  withDelay,
  withSpring,
  withTiming,
} from 'react-native-reanimated';
import { Colors } from '@/constants/colors';
import { useAuthStore } from '@/src/stores/authStore';
import { subscriptionService } from '@/src/services/subscriptionService';
import { useTheme } from '@/src/context/ThemeContext';

export default function SplashScreen() {
  const { colors } = useTheme();
  const profile = useAuthStore(s => s.profile);
  const isLoading = useAuthStore(s => s.isLoading);
  const didNavigate = useRef(false);

  const logoScale = useSharedValue(0.6);
  const logoOpacity = useSharedValue(0);
  const titleOpacity = useSharedValue(0);
  const subtitleOpacity = useSharedValue(0);

  useEffect(() => {
    logoOpacity.value = withTiming(1, { duration: 600 });
    logoScale.value = withSpring(1, { damping: 12 });
    titleOpacity.value = withDelay(400, withTiming(1, { duration: 500 }));
    subtitleOpacity.value = withDelay(700, withTiming(1, { duration: 500 }));
  }, []);

  useEffect(() => {
    if (isLoading || didNavigate.current) return;

    const navigate = async () => {
      await new Promise(r => setTimeout(r, 1600));
      if (didNavigate.current) return;

      if (!profile) {
        didNavigate.current = true;
        router.replace('/login');
        return;
      }

      if (profile.hasSubscription) {
        didNavigate.current = true;
        router.replace('/(tabs)');
        return;
      }

      try {
        const { hasAccess } = await subscriptionService.checkEntitlement(profile.id);
        if (!hasAccess) {
          didNavigate.current = true;
          router.replace('/locked');
          return;
        }
      } catch {
        didNavigate.current = true;
        router.replace('/offline');
        return;
      }

      didNavigate.current = true;
      router.replace('/(tabs)');
    };

    navigate();
  }, [isLoading, profile]);

  const logoStyle = useAnimatedStyle(() => ({
    opacity: logoOpacity.value,
    transform: [{ scale: logoScale.value }],
  }));
  const titleStyle = useAnimatedStyle(() => ({ opacity: titleOpacity.value }));
  const subtitleStyle = useAnimatedStyle(() => ({ opacity: subtitleOpacity.value }));

  return (
    <View style={[styles.container, { backgroundColor: colors.background }]}>
      <View style={styles.decorTop} />
      <View style={styles.decorBottom} />
      <Animated.View style={[styles.logoContainer, logoStyle]}>
        <Image
          source={require('@/assets/images/char_nino.png')}
          style={styles.logo}
          resizeMode="contain"
        />
      </Animated.View>
      <Animated.View style={titleStyle}>
        <Text style={[styles.title, { color: colors.textPrimary }]}>NINO XO</Text>
      </Animated.View>
      <Animated.View style={subtitleStyle}>
        <Text style={[styles.subtitle, { color: colors.textFaint }]}>NinoWorld · Premium Game</Text>
      </Animated.View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1, alignItems: 'center',
    justifyContent: 'center', gap: 12,
  },
  decorTop: {
    position: 'absolute', top: -80, right: -80,
    width: 280, height: 280, borderRadius: 140,
    backgroundColor: Colors.green, opacity: 0.08,
  },
  decorBottom: {
    position: 'absolute', bottom: -60, left: -60,
    width: 200, height: 200, borderRadius: 100,
    backgroundColor: Colors.yellow, opacity: 0.06,
  },
  logoContainer: { width: 160, height: 160, marginBottom: 8 },
  logo: { width: '100%', height: '100%' },
  title: {
    fontSize: 42, fontFamily: 'Inter_700Bold',
    letterSpacing: 6,
  },
  subtitle: {
    fontSize: 13, fontFamily: 'Inter_400Regular',
    letterSpacing: 2, textTransform: 'uppercase',
  },
});
