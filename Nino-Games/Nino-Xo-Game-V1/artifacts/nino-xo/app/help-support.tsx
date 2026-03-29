import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import React, { useState } from 'react';
import {
  Linking,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useTheme } from '@/src/context/ThemeContext';
import { PremiumCard } from '@/src/components/PremiumCard';
import { NinoButton } from '@/src/components/NinoButton';

type IoniconsName = React.ComponentProps<typeof Ionicons>['name'];

interface FaqItem {
  q: string;
  a: string;
}

const FAQS: FaqItem[] = [
  {
    q: 'How do I cancel my subscription?',
    a: 'You can manage or cancel your subscription at any time through the App Store (iOS) or Google Play (Android) subscriptions settings. Go to Settings → Your Name → Subscriptions on iOS, or Play Store → Subscriptions on Android.',
  },
  {
    q: 'Can I play offline?',
    a: 'Yes! Bot matches work fully offline. Quick matches and Friend Rooms require an internet connection.',
  },
  {
    q: "Why can't I find a quick match?",
    a: "Quick matchmaking requires other online players. If it takes too long, try again later or play a bot match instead. We're growing the community every day!",
  },
  {
    q: 'How do I create a Friend Room?',
    a: 'Tap the Friend Room button on the home screen. A unique room code will be generated — share it with your friend so they can join. Both players need an active subscription.',
  },
  {
    q: 'My stats look wrong. How do I fix them?',
    a: "Stats are tracked locally on your device. If you reinstall the app your stats will reset. We're working on cloud sync for a future update.",
  },
  {
    q: 'Can I change my character after a match starts?',
    a: 'Characters can only be changed from the home screen or your profile before a match begins. Once a match starts your character is locked in.',
  },
  {
    q: 'How does the bot difficulty work?',
    a: 'Easy bot moves randomly — great for beginners. Medium bot uses a strategy to try to win or block you. Challenge yourself on Medium to build real skills!',
  },
  {
    q: 'Is my progress saved across devices?',
    a: "Currently your profile and progress are stored on-device. Cross-device sync is on our roadmap. Make sure not to uninstall the app to keep your stats!",
  },
];

interface QuickLinkProps {
  icon: IoniconsName;
  label: string;
  sub: string;
  color: string;
  onPress: () => void;
  colors: ReturnType<typeof useTheme>['colors'];
}

function QuickLink({ icon, label, sub, color, onPress, colors }: QuickLinkProps) {
  return (
    <Pressable style={styles.quickLink} onPress={onPress}>
      <View style={[styles.quickIcon, { backgroundColor: color + '18' }]}>
        <Ionicons name={icon} size={22} color={color} />
      </View>
      <View style={styles.quickText}>
        <Text style={[styles.quickLabel, { color: colors.textPrimary }]}>{label}</Text>
        <Text style={[styles.quickSub, { color: colors.textMuted }]}>{sub}</Text>
      </View>
      <Ionicons name="chevron-forward" size={18} color={colors.textFaint} />
    </Pressable>
  );
}

export default function HelpSupportScreen() {
  const insets = useSafeAreaInsets();
  const { colors } = useTheme();
  const [expanded, setExpanded] = useState<number | null>(null);

  const openWhatsApp = () => {
    const phone = '1234567890';
    const msg = encodeURIComponent('Hi! I need help with Nino XO.');
    Linking.openURL(`https://wa.me/${phone}?text=${msg}`).catch(() =>
      Linking.openURL('https://ninoworld.com/support')
    );
  };

  const openEmail = () => {
    Linking.openURL('mailto:support@ninoworld.com?subject=Nino%20XO%20Support').catch(() =>
      Linking.openURL('https://ninoworld.com/support')
    );
  };

  const openWebsite = () => {
    Linking.openURL('https://ninoworld.com/support');
  };

  const openLiveChat = () => {
    Linking.openURL('https://ninoworld.com/support/chat');
  };

  return (
    <ScrollView
      style={[styles.container, { backgroundColor: colors.background }]}
      contentContainerStyle={[
        styles.content,
        { paddingTop: insets.top + 8, paddingBottom: insets.bottom + 40 },
      ]}
      showsVerticalScrollIndicator={false}
    >
      <View style={styles.toolbar}>
        <Pressable onPress={() => router.back()} style={styles.backBtn}>
          <Ionicons name="chevron-back" size={26} color={colors.textPrimary} />
        </Pressable>
        <Text style={[styles.toolbarTitle, { color: colors.textPrimary }]}>Help & Support</Text>
        <View style={{ width: 40 }} />
      </View>

      <PremiumCard style={styles.heroCard}>
        <View style={[styles.heroIconWrap, { backgroundColor: Colors.green + '18' }]}>
          <Ionicons name="headset-outline" size={36} color={Colors.green} />
        </View>
        <Text style={[styles.heroTitle, { color: colors.textPrimary }]}>We're here to help</Text>
        <Text style={[styles.heroSub, { color: colors.textSecondary }]}>
          Browse the FAQ below or reach out directly — our team usually replies within 24 hours.
        </Text>
      </PremiumCard>

      <PremiumCard style={styles.contactCard}>
        <Text style={[styles.sectionTitle, { color: colors.textMuted }]}>Contact Us</Text>
        <QuickLink
          icon="logo-whatsapp"
          label="WhatsApp Chat"
          sub="Fastest response · typically < 1 hour"
          color="#25D366"
          onPress={openWhatsApp}
          colors={colors}
        />
        <View style={[styles.divider, { backgroundColor: colors.border }]} />
        <QuickLink
          icon="mail-outline"
          label="Email Support"
          sub="support@ninoworld.com"
          color={Colors.blue}
          onPress={openEmail}
          colors={colors}
        />
        <View style={[styles.divider, { backgroundColor: colors.border }]} />
        <QuickLink
          icon="globe-outline"
          label="Help Center"
          sub="ninoworld.com/support"
          color={Colors.purple}
          onPress={openWebsite}
          colors={colors}
        />
        <View style={[styles.divider, { backgroundColor: colors.border }]} />
        <QuickLink
          icon="chatbubbles-outline"
          label="Start Live Chat"
          sub="Open support chat in browser"
          color={Colors.coral}
          onPress={openLiveChat}
          colors={colors}
        />
      </PremiumCard>

      <Text style={[styles.faqHeading, { color: colors.textPrimary }]}>Frequently Asked Questions</Text>

      {FAQS.map((item, i) => (
        <Pressable key={i} onPress={() => setExpanded(expanded === i ? null : i)}>
          <PremiumCard style={styles.faqCard}>
            <View style={styles.faqRow}>
              <Text style={[styles.faqQ, { color: colors.textPrimary }]}>{item.q}</Text>
              <Ionicons
                name={expanded === i ? 'chevron-up' : 'chevron-down'}
                size={18}
                color={colors.textMuted}
              />
            </View>
            {expanded === i && (
              <Text style={[styles.faqA, { color: colors.textSecondary, borderTopColor: colors.border }]}>{item.a}</Text>
            )}
          </PremiumCard>
        </Pressable>
      ))}

      <NinoButton
        label="Start WhatsApp Chat"
        onPress={openWhatsApp}
        color={Colors.green}
        fullWidth
      />

      <Text style={[styles.footer, { color: colors.textFaint }]}>NinoWorld Support · support@ninoworld.com</Text>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  content: { paddingHorizontal: 20, gap: 16 },
  toolbar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingBottom: 8,
  },
  backBtn: { width: 40, height: 40, alignItems: 'center', justifyContent: 'center' },
  toolbarTitle: { fontSize: 17, fontFamily: 'Inter_600SemiBold' },
  heroCard: { alignItems: 'center', gap: 10 },
  heroIconWrap: {
    width: 68, height: 68, borderRadius: 22,
    alignItems: 'center', justifyContent: 'center',
    marginBottom: 4,
  },
  heroTitle: { fontSize: 20, fontFamily: 'Inter_700Bold' },
  heroSub: {
    fontSize: 13, fontFamily: 'Inter_400Regular',
    textAlign: 'center', lineHeight: 20,
  },
  contactCard: { gap: 0 },
  sectionTitle: {
    fontSize: 13, fontFamily: 'Inter_600SemiBold',
    letterSpacing: 0.8, textTransform: 'uppercase',
    marginBottom: 14,
  },
  quickLink: {
    flexDirection: 'row', alignItems: 'center', gap: 14, paddingVertical: 8,
  },
  quickIcon: { width: 44, height: 44, borderRadius: 14, alignItems: 'center', justifyContent: 'center' },
  quickText: { flex: 1 },
  quickLabel: { fontSize: 15, fontFamily: 'Inter_600SemiBold' },
  quickSub: { fontSize: 12, fontFamily: 'Inter_400Regular', marginTop: 2 },
  divider: { height: 1, marginVertical: 8 },
  faqHeading: { fontSize: 16, fontFamily: 'Inter_700Bold', marginBottom: -4 },
  faqCard: { gap: 0 },
  faqRow: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  faqQ: { flex: 1, fontSize: 14, fontFamily: 'Inter_600SemiBold', lineHeight: 20 },
  faqA: {
    fontSize: 13, fontFamily: 'Inter_400Regular',
    lineHeight: 20, marginTop: 12, paddingTop: 12, borderTopWidth: 1,
  },
  footer: {
    textAlign: 'center', fontSize: 12,
    fontFamily: 'Inter_400Regular', marginTop: 4,
  },
});
