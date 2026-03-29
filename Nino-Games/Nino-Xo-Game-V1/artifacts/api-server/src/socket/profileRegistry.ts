import type { PublicProfile } from './types.js';

const socketToProfile = new Map<string, PublicProfile>();
const profileIdToSocketId = new Map<string, string>();
const playingSockets = new Set<string>();

export const profileRegistry = {
  register(socketId: string, profile: PublicProfile): void {
    const existing = socketToProfile.get(socketId);
    if (existing) {
      profileIdToSocketId.delete(existing.profileId);
    }
    const prevSocketId = profileIdToSocketId.get(profile.profileId);
    if (prevSocketId) {
      socketToProfile.delete(prevSocketId);
      playingSockets.delete(prevSocketId);
    }
    socketToProfile.set(socketId, profile);
    profileIdToSocketId.set(profile.profileId, socketId);
  },

  unregister(socketId: string): void {
    const profile = socketToProfile.get(socketId);
    if (profile) {
      profileIdToSocketId.delete(profile.profileId);
    }
    socketToProfile.delete(socketId);
    playingSockets.delete(socketId);
  },

  getBySocketId(socketId: string): PublicProfile | undefined {
    return socketToProfile.get(socketId);
  },

  getByProfileId(profileId: string): PublicProfile | undefined {
    const socketId = profileIdToSocketId.get(profileId);
    if (!socketId) return undefined;
    return socketToProfile.get(socketId);
  },

  getSocketIdByProfileId(profileId: string): string | undefined {
    return profileIdToSocketId.get(profileId);
  },

  isOnline(profileId: string): boolean {
    return profileIdToSocketId.has(profileId);
  },

  isPlayingByProfileId(profileId: string): boolean {
    const socketId = profileIdToSocketId.get(profileId);
    if (!socketId) return false;
    return playingSockets.has(socketId);
  },

  setPlaying(socketId: string, playing: boolean): void {
    if (playing) {
      playingSockets.add(socketId);
    } else {
      playingSockets.delete(socketId);
    }
  },

  count(): number {
    return socketToProfile.size;
  },
};
