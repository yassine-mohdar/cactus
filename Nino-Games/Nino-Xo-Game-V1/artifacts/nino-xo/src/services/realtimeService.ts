import { io, Socket } from 'socket.io-client';
import type { CharacterId } from '../types';
import { APP_BASE_URL } from '../config';

export type MatchmakingStatus = 'idle' | 'searching' | 'found' | 'error' | 'cancelled';
export type RoomStatus = 'waiting' | 'ready' | 'error';

export interface RoomSettings {
  roomName: string;
  turnTimerSec: number | null;
}

export interface MatchmakingResult {
  opponentName: string;
  opponentCharacterId: CharacterId;
  opponentSocketId: string;
  opponentProfileId: string;
  roomId: string;
  yourSymbol: 'X' | 'O';
}

export interface RoomInfo {
  code: string;
  settings: RoomSettings;
  status: RoomStatus;
}

export interface RemotePlayer {
  name: string;
  characterId: CharacterId;
  socketId: string;
}

export interface PublicProfile {
  profileId: string;
  name: string;
  characterId: CharacterId;
  hasSubscription: boolean;
  stats: {
    totalMatches: number;
    totalWins: number;
    totalLosses: number;
    totalDraws: number;
    streak: number;
    bestStreak: number;
  };
  earnedBadgeIds: string[];
}

type ServerToClientEvents = {
  roomCreated: (data: { code: string; settings: RoomSettings }) => void;
  roomJoined: (data: {
    code: string;
    settings: RoomSettings;
    hostName: string;
    hostCharacterId: string;
    hostSocketId: string;
  }) => void;
  opponentJoined: (data: { name: string; characterId: string; socketId: string }) => void;
  opponentLeft: () => void;
  opponentMove: (data: { index: number }) => void;
  matchFound: (data: {
    opponentName: string;
    opponentCharacterId: string;
    opponentSocketId: string;
    roomCode: string;
    yourSymbol: 'X' | 'O';
  }) => void;
  rematchInvite: (data: { fromName: string; fromSocketId: string }) => void;
  rematchAccepted: (data: {
    roomCode: string;
    yourSymbol: 'X' | 'O';
    opponentName: string;
    opponentCharacterId: string;
    opponentSocketId: string;
    opponentProfileId: string;
  }) => void;
  rematchDeclined: () => void;
  inviteExpired: () => void;
  roomError: (data: { message: string }) => void;
  onlineUsers: (data: { count: number }) => void;
  followReceived: (data: { followerName: string; followerCharacterId: string; followerProfileId: string }) => void;
  opponentTurnTimeout: (data: { symbol: string }) => void;
};

type ClientToServerEvents = {
  register: (profile: PublicProfile) => void;
  createRoom: (data: { settings: RoomSettings; hostCharacterId: string }) => void;
  joinRoom: (data: { code: string; guestCharacterId: string }) => void;
  sendMove: (data: { code: string; index: number }) => void;
  leaveRoom: (data: { code: string }) => void;
  joinQueue: (data: { characterId: string }) => void;
  leaveQueue: () => void;
  sendRematchInvite: (data: { targetSocketId: string }) => void;
  respondRematch: (data: { requesterSocketId: string; accepted: boolean }) => void;
  sendTurnTimeout: (data: { code: string; symbol: string }) => void;
};

const SOCKET_PATH = '/api/socket.io';

function getSocketUrl(): string {
  return APP_BASE_URL;
}

class RealtimeService {
  private socket: Socket<ServerToClientEvents, ClientToServerEvents> | null = null;
  private _profile: PublicProfile | null = null;
  private _onlineUsers = 0;
  private _onlineUsersListeners: Array<(count: number) => void> = [];

  get socketId(): string | undefined {
    return this.socket?.id;
  }

  get isConnected(): boolean {
    return this.socket?.connected ?? false;
  }

  get onlineUsers(): number {
    return this._onlineUsers;
  }

  connect(profile: PublicProfile): void {
    this._profile = profile;

    if (this.socket?.connected) {
      this.socket.emit('register', profile);
      return;
    }

    if (this.socket) {
      this.socket.removeAllListeners();
      this.socket.disconnect();
    }

    const url = getSocketUrl();
    this.socket = io(url, {
      path: SOCKET_PATH,
      transports: ['websocket'],
      autoConnect: true,
      reconnection: true,
      reconnectionDelay: 1000,
      reconnectionAttempts: 5,
      timeout: 10000,
    });

    this.socket.on('connect', () => {
      if (this._profile && this.socket) {
        this.socket.emit('register', this._profile);
      }
    });

    this.socket.on('onlineUsers', (data) => {
      this._onlineUsers = data.count;
      this._onlineUsersListeners.forEach((cb) => cb(data.count));
    });
  }

  disconnect(): void {
    if (this.socket) {
      this.socket.removeAllListeners();
      this.socket.disconnect();
      this.socket = null;
    }
    this._profile = null;
  }

  updateProfile(profile: PublicProfile): void {
    this._profile = profile;
    if (this.socket?.connected) {
      this.socket.emit('register', profile);
    }
  }

  onConnect(handler: () => void): () => void {
    if (!this.socket) return () => {};
    if (this.socket.connected) {
      handler();
      return () => {};
    }
    this.socket.once('connect', handler);
    return () => { this.socket?.off('connect', handler); };
  }

  onDisconnect(handler: () => void): () => void {
    if (!this.socket) return () => {};
    this.socket.on('disconnect', handler);
    return () => { this.socket?.off('disconnect', handler); };
  }

  onOnlineUsers(cb: (count: number) => void): () => void {
    this._onlineUsersListeners.push(cb);
    return () => {
      this._onlineUsersListeners = this._onlineUsersListeners.filter((l) => l !== cb);
    };
  }

  createRoom(
    settings: RoomSettings,
    hostCharacterId: CharacterId,
    onCreated: (code: string) => void,
    onError: (message: string) => void,
  ): () => void {
    if (!this.socket?.connected) {
      onError('Not connected to server');
      return () => {};
    }

    const handleCreated = (data: { code: string; settings: RoomSettings }) => {
      onCreated(data.code);
    };
    const handleError = (data: { message: string }) => {
      onError(data.message);
    };

    this.socket.once('roomCreated', handleCreated);
    this.socket.once('roomError', handleError);
    this.socket.emit('createRoom', { settings, hostCharacterId });

    return () => {
      this.socket?.off('roomCreated', handleCreated);
      this.socket?.off('roomError', handleError);
    };
  }

  joinRoom(
    code: string,
    guestCharacterId: CharacterId,
    onRoomJoined: (data: {
      hostName: string;
      hostCharacterId: CharacterId;
      hostSocketId: string;
      roomCode: string;
      turnTimerSec: number | null;
    }) => void,
    onOpponentLeft: () => void,
    onError: (message: string) => void,
  ): () => void {
    if (!this.socket?.connected) {
      onError('Not connected to server');
      return () => {};
    }

    const handleRoomJoined = (data: {
      code: string;
      settings: RoomSettings;
      hostName: string;
      hostCharacterId: string;
      hostSocketId: string;
    }) => {
      onRoomJoined({
        hostName: data.hostName,
        hostCharacterId: data.hostCharacterId as CharacterId,
        hostSocketId: data.hostSocketId,
        roomCode: data.code,
        turnTimerSec: data.settings?.turnTimerSec ?? null,
      });
    };
    const handleLeft = () => onOpponentLeft();
    const handleError = (data: { message: string }) => onError(data.message);

    this.socket.once('roomJoined', handleRoomJoined);
    this.socket.once('opponentLeft', handleLeft);
    this.socket.once('roomError', handleError);
    this.socket.emit('joinRoom', { code: code.toUpperCase(), guestCharacterId });

    return () => {
      this.socket?.off('roomJoined', handleRoomJoined);
      this.socket?.off('opponentLeft', handleLeft);
      this.socket?.off('roomError', handleError);
    };
  }

  waitForOpponent(onJoined: (player: RemotePlayer) => void, onLeft: () => void): () => void {
    if (!this.socket) return () => {};

    const handleJoined = (data: { name: string; characterId: string; socketId: string }) => {
      onJoined({
        name: data.name,
        characterId: data.characterId as CharacterId,
        socketId: data.socketId,
      });
    };
    const handleLeft = () => onLeft();

    this.socket.on('opponentJoined', handleJoined);
    this.socket.on('opponentLeft', handleLeft);

    return () => {
      this.socket?.off('opponentJoined', handleJoined);
      this.socket?.off('opponentLeft', handleLeft);
    };
  }

  sendMove(code: string, index: number): void {
    this.socket?.emit('sendMove', { code, index });
  }

  onOpponentMove(handler: (index: number) => void): () => void {
    if (!this.socket) return () => {};
    const h = (data: { index: number }) => handler(data.index);
    this.socket.on('opponentMove', h);
    return () => { this.socket?.off('opponentMove', h); };
  }

  leaveRoom(code: string): void {
    this.socket?.emit('leaveRoom', { code });
  }

  startMatchmaking(
    characterId: CharacterId,
    onUpdate: (status: MatchmakingStatus, result?: MatchmakingResult) => void,
  ): () => void {
    if (!this.socket?.connected) {
      onUpdate('error');
      return () => {};
    }

    onUpdate('searching');

    const handleFound = (data: {
      opponentName: string;
      opponentCharacterId: string;
      opponentSocketId: string;
      opponentProfileId: string;
      roomCode: string;
      yourSymbol: 'X' | 'O';
    }) => {
      onUpdate('found', {
        opponentName: data.opponentName,
        opponentCharacterId: data.opponentCharacterId as CharacterId,
        opponentSocketId: data.opponentSocketId,
        opponentProfileId: data.opponentProfileId ?? '',
        roomId: data.roomCode,
        yourSymbol: data.yourSymbol,
      });
    };

    this.socket.once('matchFound', handleFound);
    this.socket.emit('joinQueue', { characterId });

    return () => {
      this.socket?.off('matchFound', handleFound);
      this.socket?.emit('leaveQueue');
      onUpdate('cancelled');
    };
  }

  sendRematchInvite(targetSocketId: string): void {
    this.socket?.emit('sendRematchInvite', { targetSocketId });
  }

  respondRematch(requesterSocketId: string, accepted: boolean): void {
    this.socket?.emit('respondRematch', { requesterSocketId, accepted });
  }

  onRematchInvite(
    handler: (data: { fromName: string; fromSocketId: string }) => void,
  ): () => void {
    if (!this.socket) return () => {};
    this.socket.on('rematchInvite', handler);
    return () => { this.socket?.off('rematchInvite', handler); };
  }

  onRematchAccepted(handler: (data: {
    roomCode: string;
    yourSymbol: 'X' | 'O';
    opponentName: string;
    opponentCharacterId: CharacterId;
    opponentSocketId: string;
    opponentProfileId: string;
  }) => void): () => void {
    if (!this.socket) return () => {};
    const h = (data: {
      roomCode: string;
      yourSymbol: 'X' | 'O';
      opponentName: string;
      opponentCharacterId: string;
      opponentSocketId: string;
      opponentProfileId: string;
    }) => handler({
      roomCode: data.roomCode,
      yourSymbol: data.yourSymbol,
      opponentName: data.opponentName,
      opponentCharacterId: data.opponentCharacterId as CharacterId,
      opponentSocketId: data.opponentSocketId,
      opponentProfileId: data.opponentProfileId,
    });
    this.socket.once('rematchAccepted', h);
    return () => { this.socket?.off('rematchAccepted', h); };
  }

  sendTurnTimeout(code: string, symbol: string): void {
    this.socket?.emit('sendTurnTimeout', { code, symbol });
  }

  onOpponentTurnTimeout(handler: (data: { symbol: string }) => void): () => void {
    if (!this.socket) return () => {};
    this.socket.on('opponentTurnTimeout', handler);
    return () => { this.socket?.off('opponentTurnTimeout', handler); };
  }

  onRematchDeclined(handler: () => void): () => void {
    if (!this.socket) return () => {};
    this.socket.once('rematchDeclined', handler);
    return () => { this.socket?.off('rematchDeclined', handler); };
  }

  onInviteExpired(handler: () => void): () => void {
    if (!this.socket) return () => {};
    this.socket.once('inviteExpired', handler);
    return () => { this.socket?.off('inviteExpired', handler); };
  }

  onFollowReceived(
    handler: (data: { followerName: string; followerCharacterId: string; followerProfileId: string }) => void,
  ): () => void {
    if (!this.socket) return () => {};
    this.socket.on('followReceived', handler);
    return () => { this.socket?.off('followReceived', handler); };
  }

  onOpponentLeft(handler: () => void): () => void {
    if (!this.socket) return () => {};
    this.socket.on('opponentLeft', handler);
    return () => { this.socket?.off('opponentLeft', handler); };
  }

  joinMatchmakingQueue(characterId: CharacterId, onUpdate: (status: 'searching' | 'found' | 'error', result?: MatchmakingResult) => void): () => void {
    return this.startMatchmaking(characterId, onUpdate);
  }

  cancelQueue(): void {
    this.socket?.emit('leaveQueue');
  }

  acceptRematch(requesterSocketId: string): void {
    this.respondRematch(requesterSocketId, true);
  }

  declineRematch(requesterSocketId: string): void {
    this.respondRematch(requesterSocketId, false);
  }
}

export const realtimeService = new RealtimeService();
