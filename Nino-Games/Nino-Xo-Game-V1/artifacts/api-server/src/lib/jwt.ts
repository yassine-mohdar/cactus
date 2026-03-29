import { SignJWT, jwtVerify, type JWTPayload } from 'jose';
import { JWT_SECRET as JWT_SECRET_STRING } from '../config.js';

const JWT_EXPIRY = '7d';

const JWT_SECRET: Uint8Array = new TextEncoder().encode(JWT_SECRET_STRING);

export interface JwtPlayerPayload extends JWTPayload {
  playerId: string;
  isAdmin: boolean;
}

export async function signPlayerJwt(playerId: string, isAdmin: boolean): Promise<string> {
  return new SignJWT({ playerId, isAdmin })
    .setProtectedHeader({ alg: 'HS256' })
    .setIssuedAt()
    .setExpirationTime(JWT_EXPIRY)
    .sign(JWT_SECRET);
}

export async function verifyPlayerJwt(token: string): Promise<JwtPlayerPayload | null> {
  try {
    const { payload } = await jwtVerify(token, JWT_SECRET);
    if (
      typeof payload['playerId'] === 'string' &&
      typeof payload['isAdmin'] === 'boolean'
    ) {
      return payload as JwtPlayerPayload;
    }
    return null;
  } catch {
    return null;
  }
}
