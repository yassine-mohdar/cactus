import {
  Inter_400Regular,
  Inter_500Medium,
  Inter_600SemiBold,
  Inter_700Bold,
  useFonts,
} from "@expo-google-fonts/inter";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { Stack } from "expo-router";
import * as SplashScreen from "expo-splash-screen";
import React, { useEffect } from "react";
import { StatusBar } from "react-native";
import { GestureHandlerRootView } from "react-native-gesture-handler";
import { SafeAreaProvider } from "react-native-safe-area-context";

import { ErrorBoundary } from "@/components/ErrorBoundary";
import { AuthProvider } from "@/src/context/AuthContext";
import { GameProvider } from "@/src/context/GameContext";
import { SettingsProvider } from "@/src/context/SettingsContext";
import { ThemeProvider, useTheme } from "@/src/context/ThemeContext";

SplashScreen.preventAutoHideAsync();

const queryClient = new QueryClient();

function RootLayoutNav() {
  const { colors, isDark } = useTheme();
  return (
    <>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} />
      <Stack screenOptions={{ headerShown: false, contentStyle: { backgroundColor: colors.background } }}>
      <Stack.Screen name="index" />
      <Stack.Screen name="login" />
      <Stack.Screen name="(tabs)" />
      <Stack.Screen name="home" />
      <Stack.Screen name="characters" />
      <Stack.Screen name="bot-difficulty" />
      <Stack.Screen name="quick-match" />
      <Stack.Screen name="friend-room" />
      <Stack.Screen name="room-lobby" />
      <Stack.Screen name="room/[code]" />
      <Stack.Screen name="join/[code]" />
      <Stack.Screen name="match" />
      <Stack.Screen name="result" />
      <Stack.Screen name="rewards" />
      <Stack.Screen name="locked" />
      <Stack.Screen name="profile" />
      <Stack.Screen name="settings" />
      <Stack.Screen name="edit-profile" />
      <Stack.Screen name="notifications" />
      <Stack.Screen name="about" />
      <Stack.Screen name="help-support" />
      <Stack.Screen name="policy-webview" />
      <Stack.Screen name="admin-users" />
      <Stack.Screen name="admin-app-settings" />
      <Stack.Screen name="admin-challenges" />
      <Stack.Screen name="badges" />
      <Stack.Screen name="challenges" />
      <Stack.Screen name="leaderboard" />
      <Stack.Screen name="match-history" />
      <Stack.Screen name="missions" />
      <Stack.Screen name="stats" />
      <Stack.Screen name="public-profile" />
      <Stack.Screen name="how-to-play" />
      <Stack.Screen name="token-history" />
      <Stack.Screen name="session-restore" />
      <Stack.Screen name="offline" />
      <Stack.Screen name="auth-email" />
      <Stack.Screen name="auth-otp" />
    </Stack>
    </>
  );
}

export default function RootLayout() {
  const [fontsLoaded, fontError] = useFonts({
    Inter_400Regular,
    Inter_500Medium,
    Inter_600SemiBold,
    Inter_700Bold,
  });

  useEffect(() => {
    if (fontsLoaded || fontError) {
      SplashScreen.hideAsync();
    }
  }, [fontsLoaded, fontError]);

  if (!fontsLoaded && !fontError) return null;

  return (
    <SafeAreaProvider>
      <ErrorBoundary>
        <QueryClientProvider client={queryClient}>
          <GestureHandlerRootView style={{ flex: 1 }}>
            <ThemeProvider>
              <AuthProvider>
                <SettingsProvider>
                  <GameProvider>
                    <RootLayoutNav />
                  </GameProvider>
                </SettingsProvider>
              </AuthProvider>
            </ThemeProvider>
          </GestureHandlerRootView>
        </QueryClientProvider>
      </ErrorBoundary>
    </SafeAreaProvider>
  );
}
