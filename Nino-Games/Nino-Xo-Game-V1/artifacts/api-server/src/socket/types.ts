export interface RoomSettings {
  roomName: string;
  turnTimerSec: number | null;
}

export interface PublicStats {
  totalMatches: number;
  totalWins: number;
  totalLosses: number;
  totalDraws: number;
  streak: number;
  bestStreak: number;
}

export interface PublicProfile {
  profileId: string;
  name: string;
  characterId: string;
  hasSubscription: boolean;
  stats: PublicStats;
  earnedBadgeIds: string[];
}

export interface RoomPlayer {
  socketId: string;
  profileId: string;
  name: string;
  characterId: string;
  hasLeft?: boolean;
}

export interface Room {
  code: string;
  settings: RoomSettings;
  host: RoomPlayer;
  guest?: RoomPlayer;
  createdAt: number;
  expiryTimer?: ReturnType<typeof setTimeout>;
}

export interface QueueEntry {
  socketId: string;
  name: string;
  characterId: string;
  joinedAt: number;
}

export interface ClientToServerEvents {
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
}

export interface MatchFoundPayload {
  opponentName: string;
  opponentCharacterId: string;
  opponentSocketId: string;
  opponentProfileId: string;
  roomCode: string;
  yourSymbol: 'X' | 'O';
}

export interface RematchAcceptedPayload {
  roomCode: string;
  yourSymbol: 'X' | 'O';
  opponentName: string;
  opponentCharacterId: string;
  opponentSocketId: string;
  opponentProfileId: string;
}

export interface ServerToClientEvents {
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
  matchFound: (data: MatchFoundPayload) => void;
  rematchInvite: (data: { fromName: string; fromSocketId: string }) => void;
  rematchAccepted: (data: RematchAcceptedPayload) => void;
  rematchDeclined: () => void;
  inviteExpired: () => void;
  roomError: (data: { message: string }) => void;
  onlineUsers: (data: { count: number }) => void;
  followReceived: (data: { followerName: string; followerCharacterId: string; followerProfileId: string }) => void;
  opponentTurnTimeout: (data: { symbol: string }) => void;
}

export interface InterServerEvents {}

export interface SocketData {
  profile?: PublicProfile;
}
