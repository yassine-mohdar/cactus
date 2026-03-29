import { Audio } from 'expo-av';

// Local bundled assets — always available, no CDN dependency
const SFX = {
  tap:  require('../../assets/sounds/tap.wav')  as number,
  move: require('../../assets/sounds/move.wav') as number,
  win:  require('../../assets/sounds/win.wav')  as number,
  lose: require('../../assets/sounds/lose.wav') as number,
  draw: require('../../assets/sounds/draw.wav') as number,
};

const MUSIC_SRC = require('../../assets/sounds/bg.wav') as number;

let _soundEnabled = true;
let _musicEnabled = true;
let _musicSound: Audio.Sound | null = null;
let _audioReady = false;
let _musicStarting = false;

async function initAudio() {
  if (_audioReady) return;
  try {
    await Audio.setAudioModeAsync({
      playsInSilentModeIOS: true,
      staysActiveInBackground: false,
    });
    _audioReady = true;
  } catch {
    _audioReady = true;
  }
}

async function playSFX(source: number, volume = 0.7) {
  if (!_soundEnabled) return;
  try {
    await initAudio();
    const { sound } = await Audio.Sound.createAsync(source, { shouldPlay: true, volume });
    sound.setOnPlaybackStatusUpdate((status) => {
      if (status.isLoaded && status.didJustFinish) {
        sound.unloadAsync();
      }
    });
  } catch {
    // audio failure is non-fatal
  }
}

export const audioService = {
  setSoundEnabled(v: boolean) {
    _soundEnabled = v;
  },

  setMusicEnabled(v: boolean) {
    _musicEnabled = v;
    if (!v) {
      this.stopMusic();
    } else {
      this.startMusic();
    }
  },

  playTap()  { return playSFX(SFX.tap,  0.5); },
  playMove() { return playSFX(SFX.move, 0.7); },
  playWin()  { return playSFX(SFX.win,  0.9); },
  playLose() { return playSFX(SFX.lose, 0.8); },
  playDraw() { return playSFX(SFX.draw, 0.7); },

  async startMusic() {
    // Guard: skip if disabled, already playing, or a start is already in flight
    if (!_musicEnabled || _musicSound || _musicStarting) return;
    _musicStarting = true;
    try {
      await initAudio();
      // Re-check after every await — toggle may have changed while we were waiting
      if (!_musicEnabled) return;
      const { sound } = await Audio.Sound.createAsync(
        MUSIC_SRC,
        { shouldPlay: true, isLooping: true, volume: 0.22 },
      );
      if (!_musicEnabled) {
        // Toggled off while the file was loading — discard immediately
        sound.unloadAsync().catch(() => {});
        return;
      }
      _musicSound = sound;
    } catch {
      // music failure is non-fatal
    } finally {
      _musicStarting = false;
    }
  },

  async stopMusic() {
    _musicStarting = false; // cancel any in-flight start
    if (_musicSound) {
      const s = _musicSound;
      _musicSound = null; // clear immediately so startMusic can't see a stale ref
      try {
        await s.stopAsync();
        await s.unloadAsync();
      } catch {
        // ignore
      }
    }
  },
};
