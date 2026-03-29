import type { Server } from 'socket.io';
import type {
  ClientToServerEvents,
  InterServerEvents,
  ServerToClientEvents,
  SocketData,
} from './types.js';

type AppServer = Server<ClientToServerEvents, ServerToClientEvents, InterServerEvents, SocketData>;

let _io: AppServer | null = null;

export function setIo(io: AppServer): void {
  _io = io;
}

export function emitToSocket<E extends keyof ServerToClientEvents>(
  socketId: string,
  event: E,
  ...args: Parameters<ServerToClientEvents[E]>
): void {
  if (!_io) return;
  _io.to(socketId).emit(event, ...args);
}
