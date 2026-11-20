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
import type { WalletData } from '../../types';
import { formatMoney } from '../../utils/format';

export function WalletScreen() {
  const navigation = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  const [data, setData] = useState<WalletData | null>(null);
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
      setData(await endpoints.getWallet());
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
            <View style={styles.balanceCard}>
              <Text style={styles.balanceLabel}>Available balance</Text>
              <Text style={styles.balanceAmount}>
                {formatMoney(data.wallet_balance)}
              </Text>
              <View style={styles.balanceDetails}>
                <View style={styles.detailItem}>
                  <Text style={styles.detailLabel}>Total earned</Text>
                  <Text style={styles.detailValue}>
                    {formatMoney(data.total_earned)}
                  </Text>
                </View>
                <View style={styles.detailItem}>
                  <Text style={styles.detailLabel}>Pending withdrawals</Text>
                  <Text style={styles.detailValue}>
                    {formatMoney(data.pending_withdrawals)}
                  </Text>
                </View>
              </View>
            </View>

            <View style={styles.actions}>
              <View style={styles.actionButton}>
                <PrimaryButton
                  title="Withdraw"
                  variant="secondary"
                  onPress={() => navigation.navigate('Withdraw')}
                />
              </View>
              <View style={styles.actionButton}>
                <PrimaryButton
                  title="Deposit"
                  onPress={() => navigation.navigate('Deposit')}
                />
              </View>
            </View>

            <Card>
              <View style={styles.sectionHeader}>
                <Text style={styles.sectionTitle}>Recent activity</Text>
                <TouchableOpacity onPress={() => navigation.navigate('Earnings')}>
                  <Text style={styles.sectionLink}>View all</Text>
                </TouchableOpacity>
              </View>
              {data.transactions.length === 0 ? (
                <Text style={styles.emptyLine}>No transactions yet.</Text>
              ) : (
                data.transactions
                  .slice(0, 10)
                  .map(transaction => (
                    <TransactionRow
                      key={String(transaction.id)}
                      transaction={transaction}
                    />
                  ))
              )}
            </Card>

            <TouchableOpacity
              onPress={() => navigation.navigate('PayoutAccounts')}
              style={styles.menuRow}>
              <Text style={styles.menuText}>Manage payout accounts</Text>
              <Text style={styles.chevron}>›</Text>
            </TouchableOpacity>
          </>
        ) : null}
      </StateView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  balanceCard: {
    backgroundColor: colors.navy,
    borderRadius: radius.lg,
    padding: 20,
    marginBottom: 12,
  },
  balanceLabel: {
    color: '#a9b6c9',
    fontSize: 13,
  },
  balanceAmount: {
    color: colors.white,
    fontSize: 32,
    fontWeight: '800',
    marginTop: 4,
  },
  balanceDetails: {
    flexDirection: 'row',
    marginTop: 18,
  },
  detailItem: {
    flex: 1,
  },
  detailLabel: {
    color: '#8fa0b8',
    fontSize: 11,
  },
  detailValue: {
    color: colors.white,
    fontSize: 14,
    fontWeight: '700',
    marginTop: 4,
  },
  actions: {
    flexDirection: 'row',
    marginBottom: 12,
  },
  actionButton: {
    flex: 1,
    marginRight: 8,
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
  menuRow: {
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  menuText: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '600',
  },
  chevron: {
    color: colors.muted,
    fontSize: 22,
  },
});
