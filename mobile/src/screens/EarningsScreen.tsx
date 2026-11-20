import { useFocusEffect } from '@react-navigation/native';
import { useCallback, useMemo, useState } from 'react';
import {
  FlatList,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { getErrorMessage } from '../api/client';
import { endpoints } from '../api/endpoints';
import { Card } from '../components/Card';
import { Screen } from '../components/Screen';
import { StateView } from '../components/StateView';
import { TransactionRow } from '../components/TransactionRow';
import { colors, radius } from '../theme';
import type { Transaction } from '../types';
import { formatMoney } from '../utils/format';

export function EarningsScreen() {
  const [transactions, setTransactions] = useState<Transaction[]>([]);
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
      setTransactions(await endpoints.getEarnings());
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

  const summary = useMemo(() => {
    return transactions.reduce(
      (totals, item) => {
        if (item.net_amount >= 0) {
          totals.credits += item.net_amount;
        } else {
          totals.debits += Math.abs(item.net_amount);
        }
        totals.fees += item.fee;

        return totals;
      },
      { credits: 0, debits: 0, fees: 0 },
    );
  }, [transactions]);

  return (
    <Screen scroll={false}>
      <StateView
        loading={loading}
        error={error}
        empty={transactions.length === 0}
        emptyText="You do not have any earnings history yet."
        onRetry={() => load(false)}>
        <FlatList
          data={transactions}
          keyExtractor={item => String(item.id)}
          renderItem={({ item }) => (
            <Card>
              <TransactionRow transaction={item} />
            </Card>
          )}
          contentContainerStyle={styles.list}
          ListHeaderComponent={
            <View style={styles.summary}>
              <View style={styles.summaryItem}>
                <Text style={styles.summaryLabel}>Total credits</Text>
                <Text style={[styles.summaryValue, styles.credit]}>
                  {formatMoney(summary.credits)}
                </Text>
              </View>
              <View style={styles.summaryItem}>
                <Text style={styles.summaryLabel}>Total withdrawals</Text>
                <Text style={[styles.summaryValue, styles.debit]}>
                  {formatMoney(summary.debits)}
                </Text>
              </View>
              <View style={styles.summaryItem}>
                <Text style={styles.summaryLabel}>Total fees</Text>
                <Text style={styles.summaryValue}>{formatMoney(summary.fees)}</Text>
              </View>
            </View>
          }
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={() => load(true)}
              tintColor={colors.orange}
              colors={[colors.orange]}
            />
          }
        />
      </StateView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  list: {
    paddingBottom: 16,
  },
  summary: {
    backgroundColor: colors.navy,
    borderRadius: radius.lg,
    padding: 16,
    marginBottom: 12,
  },
  summaryItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 6,
  },
  summaryLabel: {
    color: '#a9b6c9',
    fontSize: 13,
  },
  summaryValue: {
    color: colors.white,
    fontSize: 14,
    fontWeight: '700',
  },
  credit: {
    color: colors.mint,
  },
  debit: {
    color: colors.orange,
  },
});
