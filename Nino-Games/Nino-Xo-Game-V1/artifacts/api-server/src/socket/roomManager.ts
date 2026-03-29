import type { Room, RoomSettings } from './types.js';

const ROOM_EXPIRY_MS = 30 * 60 * 1000;
const ROOM_EMPTY_EXPIRY_MS = 5 * 60 * 1000;
const CODE_CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

function generateCode(length = 6): string {
  return Array.from(
    { length },
    () => CODE_CHARS[Math.floor(Math.random() * CODE_CHARS.length)],
  ).join('');
}

const rooms = new Map<string, Room>();

function clearExpiry(room: Room): void {
  if (room.expiryTimer) clearTimeout(room.expiryTimer);
}

function scheduleExpiry(room: Room, delayMs: number): void {
  clearExpiry(room);
  room.expiryTimer = setTimeout(() => rooms.delete(room.code), delayMs);
}

export const roomManager = {
  createRoom(
    socketId: string,
    profileId: string,
    name: string,
    characterId: string,
    settings: RoomSettings,
    customCode?: string,
  ): Room {
    let code = customCode ?? generateCode();
    while (!customCode && rooms.has(code)) {
      code = generateCode();
    }

    const room: Room = {
      code,
      settings,
      host: { socketId, profileId, name, characterId },
      createdAt: Date.now(),
    };

    scheduleExpiry(room, ROOM_EXPIRY_MS);
    rooms.set(code, room);
    return room;
  },

  joinRoom(
    code: string,
    socketId: string,
    profileId: string,
    name: string,
    characterId: string,
  ): Room | null {
    const room = rooms.get(code.toUpperCase());
    if (!room) return null;
    if (room.host.hasLeft) return null;
    if (room.guest && !room.guest.hasLeft) return null;
    room.guest = { socketId, profileId, name, characterId };
    scheduleExpiry(room, ROOM_EXPIRY_MS);
    return room;
  },

  getByCode(code: string): Room | undefined {
    return rooms.get(code.toUpperCase());
  },

  getBySocketId(socketId: string): Room | undefined {
    for (const room of rooms.values()) {
      if (
        (room.host.socketId === socketId && !room.host.hasLeft) ||
        (room.guest?.socketId === socketId && !room.guest.hasLeft)
      ) {
        return room;
      }
    }
    return undefined;
  },

  markPlayerLeft(socketId: string): { room: Room; wasHost: boolean } | null {
    const room = roomManager.getBySocketId(socketId);
    if (!room) return null;

    const wasHost = room.host.socketId === socketId;
    if (wasHost) {
      room.host.hasLeft = true;
    } else if (room.guest) {
      room.guest.hasLeft = true;
    }

    const hasGuest = !!room.guest;
    const bothGone = room.host.hasLeft && (!hasGuest || room.guest!.hasLeft);
    const hostAloneGone = wasHost && !hasGuest;

    if (hostAloneGone) {
      scheduleExpiry(room, ROOM_EMPTY_EXPIRY_MS);
    } else if (bothGone) {
      scheduleExpiry(room, ROOM_EMPTY_EXPIRY_MS);
    }

    return { room, wasHost };
  },

  deleteRoom(code: string): void {
    const room = rooms.get(code);
    if (room) {
      clearExpiry(room);
      rooms.delete(code);
    }
  },
};
