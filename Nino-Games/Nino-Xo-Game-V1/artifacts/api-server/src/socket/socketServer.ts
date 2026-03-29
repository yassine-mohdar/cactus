import { Server } from 'socket.io';
import type { Server as HttpServer } from 'http';
import { profileRegistry } from './profileRegistry.js';
import { roomManager } from './roomManager.js';
import { setIo } from './socketNotifier.js';
import type {
  ClientToServerEvents,
  InterServerEvents,
  PublicProfile,
  QueueEntry,
  ServerToClientEvents,
  SocketData,
} from './types.js';

const INVITE_TIMEOUT_MS = 30_000;
const SOCKET_PATH = '/api/socket.io';

type AppSocket = ReturnType<
  Server<ClientToServerEvents, ServerToClientEvents, InterServerEvents, SocketData>['sockets']['sockets']['get']
> extends infer T
  ? NonNullable<T>
  : never;

export function createSocketServer(httpServer: HttpServer): Server {
  const io = new Server<ClientToServerEvents, ServerToClientEvents, InterServerEvents, SocketData>(
    httpServer,
    {
      path: SOCKET_PATH,
      cors: { origin: '*', methods: ['GET', 'POST'] },
      transports: ['websocket', 'polling'],
    },
  );

  setIo(io);

  const matchmakingQueue: QueueEntry[] = [];
  const inviteTimers = new Map<string, ReturnType<typeof setTimeout>>();
  const pendingInvites = new Map<string, string>();

  function getSocket(socketId: string): AppSocket | undefined {
    return io.sockets.sockets.get(socketId) as AppSocket | undefined;
  }

  io.on('connection', (socket) => {
    socket.on('register', (profile: PublicProfile) => {
      profileRegistry.register(socket.id, profile);
      socket.data.profile = profile;
      io.emit('onlineUsers', { count: profileRegistry.count() });
    });

    socket.on('createRoom', (data) => {
      const profile = profileRegistry.getBySocketId(socket.id);
      if (!profile) {
        socket.emit('roomError', { message: 'Not registered' });
        return;
      }
      const room = roomManager.createRoom(
        socket.id,
        profile.profileId,
        profile.name,
        data.hostCharacterId,
        data.settings,
      );
      void socket.join(room.code);
      socket.emit('roomCreated', { code: room.code, settings: room.settings });
    });

    socket.on('joinRoom', (data) => {
      const profile = profileRegistry.getBySocketId(socket.id);
      if (!profile) {
        socket.emit('roomError', { message: 'Not registered' });
        return;
      }
      const room = roomManager.joinRoom(
        data.code,
        socket.id,
        profile.profileId,
        profile.name,
        data.guestCharacterId,
      );
      if (!room) {
        socket.emit('roomError', { message: 'Room not found or is full' });
        return;
      }
      void socket.join(room.code);
      profileRegistry.setPlaying(socket.id, true);
      profileRegistry.setPlaying(room.host.socketId, true);
      const hostSocket = getSocket(room.host.socketId);
      hostSocket?.emit('opponentJoined', {
        name: profile.name,
        characterId: data.guestCharacterId,
        socketId: socket.id,
      });
      socket.emit('roomJoined', {
        code: room.code,
        settings: room.settings,
        hostName: room.host.name,
        hostCharacterId: room.host.characterId,
        hostSocketId: room.host.socketId,
      });
    });

    socket.on('sendMove', (data) => {
      const room = roomManager.getByCode(data.code);
      if (!room) return;
      const isHost = room.host.socketId === socket.id && !room.host.hasLeft;
      const isGuest = room.guest?.socketId === socket.id && !room.guest?.hasLeft;
      if (!isHost && !isGuest) return;
      socket.to(data.code).emit('opponentMove', { index: data.index });
    });

    socket.on('leaveRoom', (data) => {
      const room = roomManager.getByCode(data.code);
      if (!room) return;
      const isHost = room.host.socketId === socket.id && !room.host.hasLeft;
      const isGuest = room.guest?.socketId === socket.id && !room.guest?.hasLeft;
      if (!isHost && !isGuest) return;
      profileRegistry.setPlaying(socket.id, false);
      const opponentSocketId = isHost ? room.guest?.socketId : room.host.socketId;
      if (opponentSocketId) profileRegistry.setPlaying(opponentSocketId, false);
      socket.to(data.code).emit('opponentLeft');
      roomManager.markPlayerLeft(socket.id);
      void socket.leave(data.code);
    });

    socket.on('joinQueue', (data) => {
      const profile = profileRegistry.getBySocketId(socket.id);
      if (!profile) return;

      const existingIdx = matchmakingQueue.findIndex((e) => e.socketId === socket.id);
      if (existingIdx >= 0) matchmakingQueue.splice(existingIdx, 1);

      matchmakingQueue.push({
        socketId: socket.id,
        name: profile.name,
        characterId: data.characterId,
        joinedAt: Date.now(),
      });

      if (matchmakingQueue.length >= 2) {
        const p1 = matchmakingQueue.shift()!;
        const p2 = matchmakingQueue.shift()!;

        const code = Date.now().toString(36).toUpperCase().slice(-6);
        const p1Profile = profileRegistry.getBySocketId(p1.socketId);
        const p2Profile = profileRegistry.getBySocketId(p2.socketId);

        if (p1Profile && p2Profile) {
          roomManager.createRoom(
            p1.socketId,
            p1Profile.profileId,
            p1.name,
            p1.characterId,
            { roomName: 'Quick Match', turnTimerSec: null },
            code,
          );
          roomManager.joinRoom(code, p2.socketId, p2Profile.profileId, p2.name, p2.characterId);
        }

        const p1Socket = getSocket(p1.socketId);
        const p2Socket = getSocket(p2.socketId);

        void p1Socket?.join(code);
        void p2Socket?.join(code);

        profileRegistry.setPlaying(p1.socketId, true);
        profileRegistry.setPlaying(p2.socketId, true);

        p1Socket?.emit('matchFound', {
          opponentName: p2.name,
          opponentCharacterId: p2.characterId,
          opponentSocketId: p2.socketId,
          opponentProfileId: p2Profile?.profileId ?? '',
          roomCode: code,
          yourSymbol: 'X',
        });
        p2Socket?.emit('matchFound', {
          opponentName: p1.name,
          opponentCharacterId: p1.characterId,
          opponentSocketId: p1.socketId,
          opponentProfileId: p1Profile?.profileId ?? '',
          roomCode: code,
          yourSymbol: 'O',
        });
      }
    });

    socket.on('leaveQueue', () => {
      const idx = matchmakingQueue.findIndex((e) => e.socketId === socket.id);
      if (idx >= 0) matchmakingQueue.splice(idx, 1);
    });

    socket.on('sendRematchInvite', (data) => {
      const profile = profileRegistry.getBySocketId(socket.id);
      if (!profile) return;

      const targetSocket = getSocket(data.targetSocketId);
      if (!targetSocket) {
        socket.emit('rematchDeclined');
        return;
      }

      pendingInvites.set(socket.id, data.targetSocketId);

      targetSocket.emit('rematchInvite', {
        fromName: profile.name,
        fromSocketId: socket.id,
      });

      const existing = inviteTimers.get(socket.id);
      if (existing) clearTimeout(existing);

      const timer = setTimeout(() => {
        socket.emit('inviteExpired');
        inviteTimers.delete(socket.id);
        pendingInvites.delete(socket.id);
      }, INVITE_TIMEOUT_MS);

      inviteTimers.set(socket.id, timer);
    });

    socket.on('respondRematch', (data) => {
      const intendedTarget = pendingInvites.get(data.requesterSocketId);
      if (intendedTarget !== socket.id) {
        return;
      }
      pendingInvites.delete(data.requesterSocketId);

      const requesterSocket = getSocket(data.requesterSocketId);

      const timer = inviteTimers.get(data.requesterSocketId);
      if (timer) {
        clearTimeout(timer);
        inviteTimers.delete(data.requesterSocketId);
      }

      if (data.accepted) {
        const code = `R${Date.now().toString(36).toUpperCase().slice(-5)}`;

        const requesterProfile = profileRegistry.getBySocketId(data.requesterSocketId);
        const responderProfile = profileRegistry.getBySocketId(socket.id);

        if (requesterProfile && responderProfile) {
          roomManager.createRoom(
            data.requesterSocketId,
            requesterProfile.profileId,
            requesterProfile.name,
            requesterProfile.characterId ?? '',
            { roomName: 'Challenge', turnTimerSec: null },
            code,
          );
          roomManager.joinRoom(
            code,
            socket.id,
            responderProfile.profileId,
            responderProfile.name,
            responderProfile.characterId ?? '',
          );

          void requesterSocket?.join(code);
          void socket.join(code);

          profileRegistry.setPlaying(data.requesterSocketId, true);
          profileRegistry.setPlaying(socket.id, true);

          requesterSocket?.emit('rematchAccepted', {
            roomCode: code,
            yourSymbol: 'X',
            opponentName: responderProfile.name,
            opponentCharacterId: responderProfile.characterId ?? '',
            opponentSocketId: socket.id,
            opponentProfileId: responderProfile.profileId,
          });
          socket.emit('rematchAccepted', {
            roomCode: code,
            yourSymbol: 'O',
            opponentName: requesterProfile.name,
            opponentCharacterId: requesterProfile.characterId ?? '',
            opponentSocketId: data.requesterSocketId,
            opponentProfileId: requesterProfile.profileId,
          });
        }
      } else {
        requesterSocket?.emit('rematchDeclined');
      }
    });

    socket.on('sendTurnTimeout', (data) => {
      const room = roomManager.getByCode(data.code);
      if (!room) return;
      const isHost = room.host.socketId === socket.id;
      const isGuest = room.guest?.socketId === socket.id;
      if (!isHost && !isGuest) return;
      socket.to(data.code).emit('opponentTurnTimeout', { symbol: data.symbol });
    });

    socket.on('disconnect', () => {
      const queueIdx = matchmakingQueue.findIndex((e) => e.socketId === socket.id);
      if (queueIdx >= 0) matchmakingQueue.splice(queueIdx, 1);

      const roomResult = roomManager.markPlayerLeft(socket.id);
      if (roomResult) {
        socket.to(roomResult.room.code).emit('opponentLeft');
      }

      const timer = inviteTimers.get(socket.id);
      if (timer) {
        clearTimeout(timer);
        inviteTimers.delete(socket.id);
      }

      pendingInvites.delete(socket.id);

      profileRegistry.unregister(socket.id);
      io.emit('onlineUsers', { count: profileRegistry.count() });
    });
  });

  return io;
}
