import { StyleSheet, Text, View } from 'react-native';
import { colors } from '../theme';
import type { Transaction } from '../types';
import { formatDate, formatMoney } from '../utils/format';

export function TransactionRow({
  transaction,
}: {
  transaction: Transaction;
}) {
  const normalizedType = transaction.type.toLowerCase();
  const isDebit =
    transaction.net_amount < 0 ||
    ['withdraw', 'debit', 'fee', 'charge'].some(key =>
      normalizedType.includes(key),
    );
  const amountLabel = `${isDebit ? '-' : '+'}${formatMoney(
    Math.abs(transaction.net_amount),
  )}`;

  return (
    <View style={styles.row}>
      <View style={styles.details}>
        <Text style={styles.title} numberOfLines={1}>
          {transaction.description || transaction.type}
        </Text>
        <Text style={styles.meta}>
          {formatDate(transaction.created_at)} · {transaction.type}
        </Text>
        {transaction.fee > 0 ? (
          <Text style={styles.fee}>Fee {formatMoney(transaction.fee)}</Text>
        ) : null}
      </View>
      <Text style={[styles.amount, isDebit ? styles.debit : styles.credit]}>
        {amountLabel}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  details: {
    flexShrink: 1,
    paddingRight: 12,
  },
  title: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '600',
  },
  meta: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 3,
  },
  fee: {
    color: colors.muted,
    fontSize: 11,
    marginTop: 2,
  },
  amount: {
    fontSize: 15,
    fontWeight: '700',
  },
  credit: {
    color: colors.mint,
  },
  debit: {
    color: colors.orange,
  },
});
