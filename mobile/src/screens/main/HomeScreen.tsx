import { useFocusEffect, useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useCallback, useState } from 'react';
import {
  RefreshControl,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { getErrorMessage } from '../../api/client';
import { endpoints } from '../../api/endpoints';
import { Card } from '../../components/Card';
import { PrimaryButton } from '../../components/PrimaryButton';
import { Screen } from '../../components/Screen';
import { StateView } from '../../components/StateView';
import { TransactionRow } from '../../components/TransactionRow';
import type { RootStackParamList } from '../../navigation/types';
import { colors, radius } from '../../theme';
import type { DashboardData } from '../../types';
import { formatDate, formatMoney } from '../../utils/format';

export function HomeScreen() {
  const navigation = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  const [data, setData] = useState<DashboardData | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async (isRefresh: boolean) => {
    if (isRefresh) {
      setRefreshing(true);
    } else {
      setLoading(true);
    }

    setError(null);

    try {
      setData(await endpoints.getDashboard());
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      load(false);
    }, [load]),
  );

  return (
    <Screen
      refreshControl={
        <RefreshControl
          refreshing={refreshing}
          onRefresh={() => load(true)}
          tintColor={colors.orange}
          colors={[colors.orange]}
        />
      }>
      <StateView
        loading={loading}
        error={error}
        onRetry={() => load(false)}>
        {data ? (
          <>
            <View style={styles.hero}>
              <Text style={styles.heroLabel}>Wallet balance</Text>
              <Text style={styles.heroAmount}>
                {formatMoney(data.wallet_balance)}
              </Text>
              <Text style={styles.heroCaption}>
                Use your balance to withdraw or deposit funds.
              </Text>
              <View style={styles.heroActions}>
                <View style={styles.heroButton}>
                  <PrimaryButton
                    title="Withdraw"
                    variant="secondary"
                    onPress={() => navigation.navigate('Withdraw')}
                  />
                </View>
                <View style={styles.heroButton}>
                  <PrimaryButton
                    title="Deposit"
                    variant="outline"
                    onPress={() => navigation.navigate('Deposit')}
                  />
                </View>
              </View>
            </View>

            <View style={styles.statsGrid}>
              <StatCard
                label="Today earned"
                value={formatMoney(data.today_earned)}
                accent="mint"
              />
              <StatCard
                label="Total earned"
                value={formatMoney(data.total_earned)}
                accent="orange"
              />
              <StatCard
                label="Available tasks"
                value={String(data.available_tasks)}
                accent="navy"
              />
              <StatCard
                label="Active campaigns"
                value={String(data.active_campaigns)}
                accent="mint"
              />
            </View>

            <Card>
              <View style={styles.sectionHeader}>
                <Text style={styles.sectionTitle}>Recent transactions</Text>
                <TouchableOpacity onPress={() => navigation.navigate('Earnings')}>
                  <Text style={styles.sectionLink}>View all</Text>
                </TouchableOpacity>
              </View>
              {data.recent_transactions.length === 0 ? (
                <Text style={styles.emptyLine}>No transactions yet.</Text>
              ) : (
                data.recent_transactions
                  .slice(0, 5)
                  .map(transaction => (
                    <TransactionRow key={String(transaction.id)} transaction={transaction} />
                  ))
              )}
              {data.pending_withdrawals > 0 ? (
                <Text style={styles.pendingNote}>
                  {data.pending_withdrawals} withdrawal request(s) pending.
                </Text>
              ) : null}
            </Card>

            <Card>
              <View style={styles.sectionHeader}>
                <Text style={styles.sectionTitle}>Announcements</Text>
                <TouchableOpacity
                  onPress={() => navigation.navigate('Announcements')}>
                  <Text style={styles.sectionLink}>View all</Text>
                </TouchableOpacity>
              </View>
              {data.announcements.length === 0 ? (
                <Text style={styles.emptyLine}>No announcements yet.</Text>
              ) : (
                data.announcements.slice(0, 2).map(announcement => (
                  <View key={String(announcement.id)} style={styles.announcement}>
                    <Text style={styles.announcementTitle} numberOfLines={1}>
                      {announcement.title}
                    </Text>
                    <Text style={styles.announcementBody} numberOfLines={2}>
                      {announcement.body}
                    </Text>
                    <Text style={styles.announcementDate}>
                      {formatDate(announcement.created_at)}
                    </Text>
                  </View>
                ))
              )}
            </Card>
          </>
        ) : null}
      </StateView>
    </Screen>
  );
}

type AccentKey = 'mint' | 'orange' | 'navy';

function StatCard({
  label,
  value,
  accent,
}: {
  label: string;
  value: string;
  accent: AccentKey;
}) {
  return (
    <View style={styles.statCard}>
      <View style={[styles.statAccent, accentStyles[accent]]} />
      <Text style={styles.statLabel}>{label}</Text>
      <Text style={styles.statValue} numberOfLines={1}>
        {value}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  hero: {
    backgroundColor: colors.navy,
    borderRadius: radius.lg,
    padding: 20,
    marginBottom: 12,
  },
  heroLabel: {
    color: '#a9b6c9',
    fontSize: 13,
  },
  heroAmount: {
    color: colors.white,
    fontSize: 32,
    fontWeight: '800',
    marginTop: 4,
  },
  heroCaption: {
    color: '#8fa0b8',
    fontSize: 12,
    marginTop: 6,
  },
  heroActions: {
    flexDirection: 'row',
    marginTop: 18,
  },
  heroButton: {
    flex: 1,
    marginRight: 8,
  },
  statsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    marginBottom: 4,
  },
  statCard: {
    width: '48%',
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 14,
    marginBottom: 12,
    marginRight: '2%',
  },
  statAccent: {
    width: 24,
    height: 4,
    borderRadius: 2,
    marginBottom: 10,
  },
  accentMint: {
    backgroundColor: colors.mint,
  },
  accentOrange: {
    backgroundColor: colors.orange,
  },
  accentNavy: {
    backgroundColor: colors.navySoft,
  },
  statLabel: {
    color: colors.muted,
    fontSize: 12,
  },
  statValue: {
    color: colors.text,
    fontSize: 19,
    fontWeight: '800',
    marginTop: 4,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 6,
  },
  sectionTitle: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '700',
  },
  sectionLink: {
    color: colors.orange,
    fontSize: 13,
    fontWeight: '700',
  },
  emptyLine: {
    color: colors.muted,
    fontSize: 13,
    paddingVertical: 12,
  },
  pendingNote: {
    color: colors.orange,
    fontSize: 12,
    marginTop: 10,
  },
  announcement: {
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  announcementTitle: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '700',
  },
  announcementBody: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 3,
  },
  announcementDate: {
    color: colors.muted,
    fontSize: 11,
    marginTop: 4,
  },
});

const accentStyles = {
  mint: styles.accentMint,
  orange: styles.accentOrange,
  navy: styles.accentNavy,
};
