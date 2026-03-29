import { Ionicons } from '@expo/vector-icons';
import { router, useLocalSearchParams } from 'expo-router';
import React, { useRef, useState } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import WebView from 'react-native-webview';
import { Colors } from '@/constants/colors';
import { useTheme } from '@/src/context/ThemeContext';

export default function PolicyWebViewScreen() {
  const insets = useSafeAreaInsets();
  const { url, title } = useLocalSearchParams<{ url: string; title: string }>();
  const { colors } = useTheme();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);
  const webViewRef = useRef<WebView>(null);

  if (!url) {
    return (
      <View style={[styles.container, { paddingTop: insets.top, backgroundColor: colors.background }]}>
        <View style={[styles.header, { borderBottomColor: colors.border }]}>
          <Pressable onPress={() => router.back()} style={[styles.backBtn, { backgroundColor: colors.inputBg }]}>
            <Ionicons name="chevron-back" size={26} color={colors.textPrimary} />
          </Pressable>
          <Text style={[styles.title, { color: colors.textPrimary }]} numberOfLines={1}>{title ?? 'Page'}</Text>
          <View style={{ width: 40 }} />
        </View>
        <View style={styles.center}>
          <Ionicons name="alert-circle-outline" size={56} color={colors.textFaint} />
          <Text style={[styles.errorTitle, { color: colors.textPrimary }]}>Not configured</Text>
          <Text style={[styles.errorSub, { color: colors.textSecondary }]}>This URL has not been set up yet.</Text>
        </View>
      </View>
    );
  }

  return (
    <View style={[styles.container, { paddingTop: insets.top, backgroundColor: colors.background }]}>
      <View style={[styles.header, { borderBottomColor: colors.border }]}>
        <Pressable onPress={() => router.back()} style={[styles.backBtn, { backgroundColor: colors.inputBg }]}>
          <Ionicons name="chevron-back" size={26} color={colors.textPrimary} />
        </Pressable>
        <Text style={[styles.title, { color: colors.textPrimary }]} numberOfLines={1}>{title ?? 'Page'}</Text>
        <Pressable
          style={styles.reloadBtn}
          onPress={() => { setError(false); setLoading(true); webViewRef.current?.reload(); }}
        >
          <Ionicons name="refresh" size={18} color={colors.textMuted} />
        </Pressable>
      </View>

      {loading && !error && (
        <View style={[styles.loadingOverlay, { backgroundColor: colors.background }]}>
          <ActivityIndicator size="large" color={Colors.purple} />
        </View>
      )}

      {error ? (
        <View style={styles.center}>
          <Ionicons name="wifi-outline" size={56} color={colors.textFaint} />
          <Text style={[styles.errorTitle, { color: colors.textPrimary }]}>Could not load page</Text>
          <Text style={[styles.errorSub, { color: colors.textSecondary }]}>Check your connection and try again.</Text>
          <Pressable
            style={styles.retryBtn}
            onPress={() => { setError(false); setLoading(true); webViewRef.current?.reload(); }}
          >
            <Text style={styles.retryText}>Retry</Text>
          </Pressable>
        </View>
      ) : (
        <WebView
          ref={webViewRef}
          source={{ uri: url }}
          style={styles.webview}
          onLoadStart={() => { setLoading(true); setError(false); }}
          onLoadEnd={() => setLoading(false)}
          onError={() => { setLoading(false); setError(true); }}
          onHttpError={() => { setLoading(false); setError(true); }}
          startInLoadingState={false}
          allowsBackForwardNavigationGestures
          javaScriptEnabled
          domStorageEnabled
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderBottomWidth: 1,
  },
  backBtn: {
    width: 40, height: 40,
    alignItems: 'center', justifyContent: 'center',
    borderRadius: 12,
  },
  reloadBtn: {
    width: 40, height: 40,
    alignItems: 'center', justifyContent: 'center',
  },
  title: {
    fontSize: 17,
    fontFamily: 'Inter_600SemiBold',
    flex: 1,
    textAlign: 'center',
  },
  webview: { flex: 1 },
  loadingOverlay: {
    ...StyleSheet.absoluteFillObject,
    top: 64,
    alignItems: 'center',
    justifyContent: 'center',
    zIndex: 10,
  },
  center: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 14,
    padding: 40,
  },
  errorTitle: {
    fontSize: 18,
    fontFamily: 'Inter_700Bold',
    textAlign: 'center',
  },
  errorSub: {
    fontSize: 14,
    fontFamily: 'Inter_400Regular',
    textAlign: 'center',
    lineHeight: 21,
  },
  retryBtn: {
    marginTop: 8,
    paddingVertical: 12,
    paddingHorizontal: 32,
    backgroundColor: Colors.purple,
    borderRadius: 14,
  },
  retryText: {
    fontSize: 15,
    fontFamily: 'Inter_600SemiBold',
    color: Colors.white,
  },
});
