import * as SecureStore from 'expo-secure-store';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Platform } from 'react-native';
import { API_BASE_URL } from '../config';

const JWT_KEY = '@ninoxo_jwt_v1';

export function getApiBaseUrl(): string {
  return API_BASE_URL;
}

export async function getStoredJwt(): Promise<string | null> {
  try {
    if (Platform.OS === 'web') {
      return await AsyncStorage.getItem(JWT_KEY);
    }
    return await SecureStore.getItemAsync(JWT_KEY);
  } catch {
    try {
      return await AsyncStorage.getItem(JWT_KEY);
    } catch {
      return null;
    }
  }
}

export async function storeJwt(token: string): Promise<void> {
  try {
    if (Platform.OS === 'web') {
      await AsyncStorage.setItem(JWT_KEY, token);
      return;
    }
    await SecureStore.setItemAsync(JWT_KEY, token);
  } catch {
    await AsyncStorage.setItem(JWT_KEY, token);
  }
}

export async function clearJwt(): Promise<void> {
  try {
    if (Platform.OS === 'web') {
      await AsyncStorage.removeItem(JWT_KEY);
      return;
    }
    await SecureStore.deleteItemAsync(JWT_KEY);
  } catch {
    await AsyncStorage.removeItem(JWT_KEY);
  }
}

export function decodeJwtPayload(token: string): Record<string, unknown> | null {
  try {
    const parts = token.split('.');
    if (parts.length !== 3) return null;
    const payload = parts[1];
    const padded = payload + '='.repeat((4 - (payload.length % 4)) % 4);
    const decoded = atob(padded.replace(/-/g, '+').replace(/_/g, '/'));
    return JSON.parse(decoded) as Record<string, unknown>;
  } catch {
    return null;
  }
}

export function isJwtExpired(token: string): boolean {
  const payload = decodeJwtPayload(token);
  if (!payload) return true;
  const exp = payload['exp'];
  if (typeof exp !== 'number') return true;
  return Date.now() / 1000 > exp;
}

interface FetchOptions extends RequestInit {
  skipAuth?: boolean;
}

export async function apiFetch(path: string, options: FetchOptions = {}): Promise<Response> {
  const { skipAuth = false, ...fetchOptions } = options;
  const base = API_BASE_URL;
  const url = path.startsWith('http') ? path : `${base}${path}`;

  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    ...(fetchOptions.headers as Record<string, string> | undefined),
  };

  if (!skipAuth) {
    const token = await getStoredJwt();
    if (token && !isJwtExpired(token)) {
      headers['Authorization'] = `Bearer ${token}`;
    }
  }

  return fetch(url, { ...fetchOptions, headers });
}
