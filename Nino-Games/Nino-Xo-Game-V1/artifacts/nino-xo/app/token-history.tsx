import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import React, { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/colors';
import { useAuth } from '@/src/context/AuthContext';
import { useTheme } from '@/src/context/ThemeContext';
import { fetchTokenHistory, fetchTokenBalance, TOKEN_TYPE_LABELS, type TokenTransaction } from '@/src/services/tokenService';

type IoniconsName = React.ComponentProps<typeof Ionicons>['name'];

function typeIcon(type: string): { icon: IoniconsName; color: string } {
  switch (type) {
    case 'match_win_reward': return { icon: 'trophy', color: Colors.yellow };
    case 'challenge_entry_fee': return { icon: 'enter-outline', color: Colors.coral };
    case 'challenge_reward': return { icon: 'ribbon', color: Colors.purple };
    case 'admin_adjustment': return { icon: 'build', color: Colors.blue };
    case 'refund': return { icon: 'arrow-undo', color: Colors.green };
    default: return { icon: 'wallet', color: Colors.blue };
  }
}

function formatDate(iso: string): string {
  const d = new Date(iso);
  return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatTime(iso: string): string {
  const d = new Date(iso);
  return d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
}

function TxRow({ tx, colors }: { tx: TokenTransaction; colors: ReturnType<typeof useTheme>['colors'] }) {
  const { icon, color } = typeIcon(tx.type);
  const isCredit = tx.amount > 0;

  return (
    <View style={[styles.txRow, { borderBottomColor: colors.border }]}>
      <View style={[styles.txIcon, { backgroundColor: color + '20' }]}>
        <Ionicons name={icon} size={18} color={color} />
      </View>
      <View style={styles.txBody}>
        <Text style={[styles.txType, { color: colors.textPrimary }]}>{TOKEN_TYPE_LABELS[tx.type] ?? tx.type}</Text>
        {tx.description ? (
          <Text style={[styles.txDesc, { color: colors.textMuted }]}>{tx.description}</Text>
        ) : null}
        <Text style={[styles.txDate, { color: colors.textFaint }]}>{formatDate(tx.createdAt)} · {formatTime(tx.createdAt)}</Text>
      </View>
      <Text style={[styles.txAmount, { color: isCredit ? Colors.green : Colors.coral }]}>
        {isCredit ? '+' : ''}{tx.amount}
      </Text>
    </View>
  );
}

export default function TokenHistoryScreen() {
  const insets = useSafeAreaInsets();
  const { profile, setTokenBalance } = useAuth();
  const { colors } = useTheme();
  const [history, setHistory] = useState<TokenTransaction[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!profile) return;

    fetchTokenBalance(profile.id).then((balance) => {
      if (balance !== null) setTokenBalance(balance);
    });

    fetchTokenHistory(profile.id).then((txs) => {
      setHistory(txs);
      setLoading(false);
    });
  }, [profile?.id]);

  useEffect(() => {
    if (!profile) router.replace('/login');
  }, [profile]);

  if (!profile) return null;

  return (
    <View style={[styles.root, { backgroundColor: colors.background }]}>
      <View style={[styles.header, { paddingTop: insets.top + 8 }]}>
        <Pressable onPress={() => router.back()} style={styles.backBtn}>
          <Ionicons name="chevron-back" size={26} color={colors.textPrimary} />
        </Pressable>
        <Text style={[styles.headerTitle, { color: colors.textPrimary }]}>Token Wallet</Text>
        <View style={{ width: 40 }} />
      </View>

      <View style={[styles.balanceCard, { backgroundColor: colors.card, borderColor: 'rgba(250,204,21,0.2)' }]}>
        <Text style={[styles.balanceLabel, { color: colors.textMuted }]}>Current Balance</Text>
        <View style={styles.balanceRow}>
          <Text style={[styles.balanceNum, { color: Colors.yellow }]}>{profile.tokenBalance ?? 0}</Text>
          <Text style={styles.balanceCoin}>🪙</Text>
        </View>
        <Text style={[styles.balanceHint, { color: colors.textFaint }]}>Earn tokens by winning matches</Text>
      </View>

      <Text style={[styles.sectionLabel, { marginHorizontal: 20, marginBottom: 10, marginTop: 8, color: colors.textMuted }]}>
        Transaction History
      </Text>

      {loading ? (
        <View style={styles.loading}>
          <ActivityIndicator color={Colors.yellow} size="large" />
        </View>
      ) : history.length === 0 ? (
        <View style={styles.empty}>
          <Ionicons name="wallet-outline" size={48} color={colors.textFaint} />
          <Text style={[styles.emptyTitle, { color: colors.textMuted }]}>No transactions yet</Text>
          <Text style={[styles.emptyHint, { color: colors.textFaint }]}>Win a match to earn your first tokens!</Text>
        </View>
      ) : (
        <ScrollView
          style={{ flex: 1 }}
          contentContainerStyle={[styles.list, { paddingBottom: insets.bottom + 20 }]}
          showsVerticalScrollIndicator={false}
        >
          {history.map((tx) => (
            <TxRow key={tx.id} tx={tx} colors={colors} />
          ))}
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1 },

  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingBottom: 8,
  },
  backBtn: { width: 40, height: 40, alignItems: 'center', justifyContent: 'center' },
  headerTitle: { fontSize: 17, fontFamily: 'Inter_600SemiBold' },

  balanceCard: {
    margin: 20,
    borderRadius: 24,
    padding: 24,
    alignItems: 'center',
    borderWidth: 1,
    gap: 6,
  },
  balanceLabel: {
    fontSize: 12,
    fontFamily: 'Inter_600SemiBold',
    textTransform: 'uppercase',
    letterSpacing: 1,
  },
  balanceRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  balanceNum: { fontSize: 56, fontFamily: 'Inter_700Bold' },
  balanceCoin: { fontSize: 36 },
  balanceHint: {
    fontSize: 12,
    fontFamily: 'Inter_400Regular',
    marginTop: 4,
  },

  sectionLabel: {
    fontSize: 13,
    fontFamily: 'Inter_600SemiBold',
    textTransform: 'uppercase',
    letterSpacing: 0.8,
  },

  loading: { flex: 1, alignItems: 'center', justifyContent: 'center' },

  empty: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 10, paddingHorizontal: 40 },
  emptyTitle: { fontSize: 17, fontFamily: 'Inter_700Bold' },
  emptyHint: { fontSize: 13, fontFamily: 'Inter_400Regular', textAlign: 'center' },

  list: { paddingHorizontal: 20, gap: 2 },

  txRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
    paddingVertical: 14,
    borderBottomWidth: 1,
  },
  txIcon: {
    width: 42,
    height: 42,
    borderRadius: 13,
    alignItems: 'center',
    justifyContent: 'center',
  },
  txBody: { flex: 1 },
  txType: { fontSize: 14, fontFamily: 'Inter_600SemiBold' },
  txDesc: { fontSize: 12, fontFamily: 'Inter_400Regular', marginTop: 1 },
  txDate: { fontSize: 11, fontFamily: 'Inter_400Regular', marginTop: 3 },
  txAmount: { fontSize: 18, fontFamily: 'Inter_700Bold' },
});
