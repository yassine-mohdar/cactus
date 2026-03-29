import { Ionicons } from '@expo/vector-icons';
import { router, useLocalSearchParams } from 'expo-router';
import React, { useState } from 'react';
import {
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuth } from '@/src/context/AuthContext';
import { useGame } from '@/src/context/GameContext';
import { CHARACTERS, getCharacter } from '@/src/data/characters';
import { CharacterId } from '@/src/types';
import { CharacterCard } from '@/src/components/CharacterCard';
import { NinoButton } from '@/src/components/NinoButton';
import { BouncingCharacter } from '@/src/components/BouncingCharacter';
import { PremiumCard } from '@/src/components/PremiumCard';
import { useTheme } from '@/src/context/ThemeContext';

export default function CharactersScreen() {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const { profile, updateCharacter } = useAuth();
  const { startBotGame, startQuickGame } = useGame();
  const params = useLocalSearchParams<{ mode?: string }>();
  const mode = params.mode ?? 'select';

  const [selected, setSelected] = useState<CharacterId>(
    profile?.selectedCharacterId ?? 'nino'
  );

  const character = getCharacter(selected);

  const handleConfirm = async () => {
    await updateCharacter(selected);
    if (mode === 'bot') {
      router.push('/bot-difficulty');
    } else if (mode === 'quick') {
      if (profile) {
        startQuickGame(selected, profile.name);
        router.push('/quick-match');
      }
    } else {
      router.back();
    }
  };

  const btnLabel = mode === 'bot' ? 'Choose Difficulty' : mode === 'quick' ? 'Find Match' : 'Play as this character';

  return (
    <View style={[styles.container, { paddingTop: insets.top, backgroundColor: colors.background }]}>
      <View style={styles.toolbar}>
        <Pressable onPress={() => router.back()} style={styles.backBtn}>
          <Ionicons name="chevron-back" size={26} color={colors.textPrimary} />
        </Pressable>
        <Text style={[styles.toolbarTitle, { color: colors.textPrimary }]}>Choose Character</Text>
        <View style={{ width: 40 }} />
      </View>

      <ScrollView contentContainerStyle={styles.scroll} showsVerticalScrollIndicator={false}>
        <PremiumCard style={styles.previewCard}>
          <BouncingCharacter character={character} size={130} reactionType="idle" />
          <Text style={[styles.charName, { color: character.accentColor }]}>{character.name}</Text>
          <Text style={[styles.personality, { color: colors.textSecondary }]}>{character.personality}</Text>
          <Text style={[styles.flavor, { color: colors.textMuted }]}>{character.flavor}</Text>
        </PremiumCard>

        <View style={[styles.reactionPreview, { backgroundColor: colors.card }]}>
          <Text style={[styles.reactionLabel, { color: colors.textSecondary }]}>Win reaction:</Text>
          <Text style={[styles.reactionText, { color: character.accentColor }]}>
            "{character.reactions.win}"
          </Text>
        </View>

        <Text style={[styles.sectionTitle, { color: colors.textPrimary }]}>All Characters</Text>

        <View style={styles.grid}>
          {CHARACTERS.map(c => (
            <CharacterCard
              key={c.id}
              character={c}
              selected={selected === c.id}
              onSelect={() => setSelected(c.id)}
            />
          ))}
        </View>

        <View style={{ paddingBottom: insets.bottom + 24 }}>
          <NinoButton
            label={btnLabel}
            onPress={handleConfirm}
            color={character.accentColor}
            fullWidth
          />
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  toolbar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  backBtn: { width: 40, height: 40, alignItems: 'center', justifyContent: 'center' },
  toolbarTitle: { fontSize: 17, fontFamily: 'Inter_600SemiBold' },
  scroll: { paddingHorizontal: 20, gap: 16, paddingBottom: 24 },
  previewCard: { alignItems: 'center', gap: 8 },
  charName: { fontSize: 24, fontFamily: 'Inter_700Bold' },
  personality: { fontSize: 14, fontFamily: 'Inter_500Medium' },
  flavor: {
    fontSize: 13,
    fontFamily: 'Inter_400Regular',
    textAlign: 'center',
    lineHeight: 20,
    paddingHorizontal: 8,
  },
  reactionPreview: {
    borderRadius: 16,
    padding: 14,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  reactionLabel: { fontSize: 13, fontFamily: 'Inter_500Medium' },
  reactionText: { fontSize: 13, fontFamily: 'Inter_600SemiBold', flex: 1 },
  sectionTitle: { fontSize: 17, fontFamily: 'Inter_700Bold' },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
    justifyContent: 'flex-start',
  },
});
