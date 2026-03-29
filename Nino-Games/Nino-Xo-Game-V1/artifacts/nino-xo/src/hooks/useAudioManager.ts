import { useEffect } from 'react';
import { useSettings } from '../context/SettingsContext';
import { audioService } from '../services/audioService';

export function useAudioManager() {
  const { settings } = useSettings();

  useEffect(() => {
    audioService.setSoundEnabled(settings.soundEnabled);
    audioService.setMusicEnabled(settings.musicEnabled);
  }, [settings.soundEnabled, settings.musicEnabled]);

  useEffect(() => {
    audioService.startMusic();
    return () => {
      audioService.stopMusic();
    };
  }, []);
}
