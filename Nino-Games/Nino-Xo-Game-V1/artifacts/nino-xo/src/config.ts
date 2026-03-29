import { Platform } from 'react-native';

// =============================================================================
//  Nino XO — App Configuration
//  All EXPO_PUBLIC_ environment variables are read here and exported as typed
//  constants. Set these in Replit Secrets (lock icon in the sidebar).
//  Only variables prefixed with EXPO_PUBLIC_ are bundled into the app.
// =============================================================================

// ── API ───────────────────────────────────────────────────────────────────────
// Base URLs for the Nino XO backend.
// EXPO_PUBLIC_DOMAIN is set automatically by the Replit workflow — no need to
// change it in development. Override in production if using a custom domain.

const _domain = process.env['EXPO_PUBLIC_DOMAIN'];

// Root origin of the backend (no path suffix). Used for WebSocket connections.
export const APP_BASE_URL: string = _domain
  ? `https://${_domain}`
  : 'http://localhost:8080';

// REST API base URL. Append route paths to this (e.g. `${API_BASE_URL}/auth/me`).
export const API_BASE_URL: string = `${APP_BASE_URL}/api`;

// ── Firebase ─────────────────────────────────────────────────────────────────
// Web SDK configuration for Firebase services (Analytics, etc.).
// Get all values from: Firebase Console → Project Settings → Your apps → Web app
//
//   EXPO_PUBLIC_FIREBASE_API_KEY            — web API key
//   EXPO_PUBLIC_FIREBASE_AUTH_DOMAIN        — e.g. your-project.firebaseapp.com
//   EXPO_PUBLIC_FIREBASE_PROJECT_ID         — Firebase project ID
//   EXPO_PUBLIC_FIREBASE_STORAGE_BUCKET     — e.g. your-project.firebasestorage.app
//   EXPO_PUBLIC_FIREBASE_MESSAGING_SENDER_ID — numeric sender ID
//   EXPO_PUBLIC_FIREBASE_APP_ID             — web app ID
//   EXPO_PUBLIC_FIREBASE_MEASUREMENT_ID     — Analytics measurement ID (G-XXXXXXXXXX)

export const FIREBASE_CONFIG = {
  apiKey:            process.env['EXPO_PUBLIC_FIREBASE_API_KEY'],
  authDomain:        process.env['EXPO_PUBLIC_FIREBASE_AUTH_DOMAIN'],
  projectId:         process.env['EXPO_PUBLIC_FIREBASE_PROJECT_ID'],
  storageBucket:     process.env['EXPO_PUBLIC_FIREBASE_STORAGE_BUCKET'],
  messagingSenderId: process.env['EXPO_PUBLIC_FIREBASE_MESSAGING_SENDER_ID'],
  appId:             process.env['EXPO_PUBLIC_FIREBASE_APP_ID'],
  measurementId:     process.env['EXPO_PUBLIC_FIREBASE_MEASUREMENT_ID'],
} as const;

// ── Google Sign-In ────────────────────────────────────────────────────────────
// OAuth 2.0 Client IDs for Google Sign-In via expo-auth-session.
// Get from: Google Cloud Console → APIs & Credentials → OAuth 2.0 Client IDs
//   — create a "Web application" client  → EXPO_PUBLIC_GOOGLE_CLIENT_ID
//   — create an "iOS" client             → EXPO_PUBLIC_GOOGLE_IOS_CLIENT_ID
//
// Important: add your app's redirect URI to the OAuth client's allowed list.
// The redirect URI for Expo is typically: https://auth.expo.io/@<username>/nino-xo
//
// GOOGLE_AVAILABLE is true only when the correct client ID for this platform
// is configured, so the button is never shown in an inoperable state.

export const GOOGLE_WEB_CLIENT_ID = process.env['EXPO_PUBLIC_GOOGLE_CLIENT_ID'] ?? '';
export const GOOGLE_IOS_CLIENT_ID = process.env['EXPO_PUBLIC_GOOGLE_IOS_CLIENT_ID'] ?? '';

export const GOOGLE_AVAILABLE: boolean = Platform.OS === 'ios'
  ? !!GOOGLE_IOS_CLIENT_ID
  : !!GOOGLE_WEB_CLIENT_ID;

// Returns the correct client ID for the current platform.
export const GOOGLE_CLIENT_ID_FOR_PLATFORM: string = Platform.OS === 'ios'
  ? GOOGLE_IOS_CLIENT_ID
  : GOOGLE_WEB_CLIENT_ID;

// ── Apple Sign-In ─────────────────────────────────────────────────────────────
// Used on iOS to show the Apple Sign-In button when configured.
// Get from: Apple Developer → Certificates, IDs & Profiles → Services IDs
//   EXPO_PUBLIC_APPLE_CLIENT_ID — your Apple Services ID (e.g. com.nino.xo.app.signin)
//
// Note: Apple Sign-In also requires a device check at runtime via
// AppleAuthentication.isAvailableAsync() — both must pass for the button to show.

export const APPLE_CLIENT_ID = process.env['EXPO_PUBLIC_APPLE_CLIENT_ID'] ?? '';
