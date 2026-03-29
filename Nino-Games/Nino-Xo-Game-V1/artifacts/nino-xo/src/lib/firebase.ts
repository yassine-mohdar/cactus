import { initializeApp, getApps } from 'firebase/app';
import { FIREBASE_CONFIG } from '../config';

export const firebaseApp = getApps().length === 0
  ? initializeApp(FIREBASE_CONFIG)
  : getApps()[0]!;
