import { useFocusEffect, useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useCallback, useMemo, useState } from 'react';
import {
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { getErrorMessage } from '../api/client';
import { endpoints } from '../api/endpoints';
import { Card } from '../components/Card';
import { Field } from '../components/Field';
import { PrimaryButton } from '../components/PrimaryButton';
import { Screen } from '../components/Screen';
import { StateView } from '../components/StateView';
import { StatusPill } from '../components/StatusPill';
import { WITHDRAWAL_RULES } from '../config';
import type { RootStackParamList } from '../navigation/types';
import { colors, radius } from '../theme';
import type { ID, PayoutAccount, PayoutRequest, WalletData } from '../types';
import { formatCooldown, formatDate, formatMoney } from '../utils/format';

const COOLDOWN_MS = WITHDRAWAL_RULES.cooldownHours * 60 * 60 * 1000;

export function WithdrawScreen() {
  const navigation = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  const [wallet, setWallet] = useState<WalletData | null>(null);
  const [accounts, setAccounts] = useState<PayoutAccount[]>([]);
  const [withdrawals, setWithdrawals] = useState<PayoutRequest[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [amount, setAmount] = useState('');
  const [selectedAccountId, setSelectedAccountId] = useState<ID | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  const load = useCallback(async (isRefresh: boolean) => {
    if (isRefresh) {
      setRefreshing(true);
    } else {
      setLoading(true);
    }

    setError(null);

    try {
      const [walletData, payoutAccounts, requests] = await Promise.all([
        endpoints.getWallet(),
        endpoints.getPayoutAccounts(),
        endpoints.getWithdrawals(),
      ]);
      setWallet(walletData);
      setAccounts(payoutAccounts);
      setWithdrawals(requests);
      setSelectedAccountId(current => {
        if (current !== null && payoutAccounts.some(item => item.id === current)) {
          return current;
        }

        return payoutAccounts.find(item => item.is_default)?.id ?? payoutAccounts[0]?.id ?? null;
      });
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

  const lastRequestTime = useMemo(() => {
    return withdrawals.reduce((latest, request) => {
      const time = new Date(request.created_at).getTime();

      return Number.isFinite(time) && time > latest ? time : latest;
    }, 0);
  }, [withdrawals]);

  const remainingCooldownMs =
    lastRequestTime > 0 ? COOLDOWN_MS - (Date.now() - lastRequestTime) : 0;
  const inCooldown = remainingCooldownMs > 0;

  const gross = Number.parseFloat(amount) || 0;
  const fee = Math.round(gross * WITHDRAWAL_RULES.feeRate * 100) / 100;
  const net = Math.round((gross - fee) * 100) / 100;

  const handleSubmit = async () => {
    setFormError(null);
    setSuccessMessage(null);

    if (inCooldown) {
      setFormError(
        `Only one withdrawal request is allowed every ${
          WITHDRAWAL_RULES.cooldownHours
        } hours. Try again in ${formatCooldown(remainingCooldownMs)}.`,
      );

      return;
    }

    if (
      !Number.isFinite(gross) ||
      gross < WITHDRAWAL_RULES.minimumGross
    ) {
      setFormError(
        `Minimum withdrawal is ${formatMoney(WITHDRAWAL_RULES.minimumGross)}.`,
      );

      return;
    }

    if (gross > WITHDRAWAL_RULES.maximumGross) {
      setFormError(
        `Maximum withdrawal is ${formatMoney(WITHDRAWAL_RULES.maximumGross)}.`,
      );

      return;
    }

    if (wallet && gross > wallet.wallet_balance) {
      setFormError('The amount exceeds your available balance.');

      return;
    }

    if (selectedAccountId === null) {
      setFormError('Select a payout account before withdrawing.');

      return;
    }

    setSubmitting(true);

    try {
      await endpoints.createWithdrawal({
        amount: gross,
        payout_account_id: selectedAccountId,
      });
      setAmount('');
      setSuccessMessage(
        `Withdrawal request submitted. You will receive ${formatMoney(net)}.`,
      );
      await load(true);
    } catch (err) {
      setFormError(getErrorMessage(err));
    } finally {
      setSubmitting(false);
    }
  };

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
        <ScrollView keyboardShouldPersistTaps="handled">
          <Card>
            <Text style={styles.sectionTitle}>Withdraw funds</Text>
            <Text style={styles.sectionSubtitle}>
              Minimum {formatMoney(WITHDRAWAL_RULES.minimumGross)} · Maximum{' '}
              {formatMoney(WITHDRAWAL_RULES.maximumGross)} · One request per{' '}
              {WITHDRAWAL_RULES.cooldownHours} hours
            </Text>
            <Text style={styles.balanceLine}>
              Available balance{' '}
              <Text style={styles.balanceValue}>
                {formatMoney(wallet?.wallet_balance ?? 0)}
              </Text>
            </Text>
            <Field
              label="Amount to withdraw"
              value={amount}
              onChangeText={setAmount}
              placeholder="50.00"
              keyboardType="decimal-pad"
            />
            <View style={styles.summaryRow}>
              <Text style={styles.summaryLabel}>Gross amount</Text>
              <Text style={styles.summaryValue}>{formatMoney(gross)}</Text>
            </View>
            <View style={styles.summaryRow}>
              <Text style={styles.summaryLabel}>
                {WITHDRAWAL_RULES.feeLabel} (10%)
              </Text>
              <Text style={styles.summaryValue}>{formatMoney(fee)}</Text>
            </View>
            <View style={styles.summaryRow}>
              <Text style={[styles.summaryLabel, styles.netLabel]}>
                You receive
              </Text>
              <Text style={[styles.summaryValue, styles.netValue]}>
                {formatMoney(net)}
              </Text>
            </View>

            {inCooldown ? (
              <Text style={styles.cooldownNote}>
                You recently requested a withdrawal. Next request available in{' '}
                {formatCooldown(remainingCooldownMs)}.
              </Text>
            ) : null}
            {formError ? <Text style={styles.formError}>{formError}</Text> : null}
            {successMessage ? (
              <Text style={styles.success}>{successMessage}</Text>
            ) : null}

            <PrimaryButton
              title="Request withdrawal"
              onPress={() => handleSubmit()}
              loading={submitting}
              disabled={inCooldown}
            />
          </Card>

          <Text style={styles.listTitle}>Select payout account</Text>
          {accounts.length === 0 ? (
            <Card>
              <Text style={styles.emptyText}>
                No payout accounts yet. Add one before withdrawing.
              </Text>
              <PrimaryButton
                title="Add payout account"
                variant="outline"
                onPress={() => navigation.navigate('PayoutAccounts')}
              />
            </Card>
          ) : (
            accounts.map(account => {
              const selected = account.id === selectedAccountId;

              return (
                <TouchableOpacity
                  key={String(account.id)}
                  onPress={() => setSelectedAccountId(account.id)}
                  style={[
                    styles.accountRow,
                    selected && styles.accountRowSelected,
                  ]}>
                  <View style={styles.accountDetails}>
                    <Text style={styles.accountTitle}>
                      {account.provider_label}
                    </Text>
                    <Text style={styles.accountDetail}>
                      {account.account_name} · {account.account_number}
                    </Text>
                  </View>
                  <View style={[styles.radio, selected && styles.radioSelected]} />
                </TouchableOpacity>
              );
            })
          )}

          <Text style={styles.listTitle}>Recent requests</Text>
          {withdrawals.length === 0 ? (
            <Card>
              <Text style={styles.emptyText}>No withdrawal requests yet.</Text>
            </Card>
          ) : (
            withdrawals.slice(0, 5).map(request => (
              <Card key={String(request.id)}>
                <View style={styles.requestHeader}>
                  <Text style={styles.requestAmount}>
                    {formatMoney(request.amount)}
                  </Text>
                  <StatusPill label={request.status} />
                </View>
                <Text style={styles.accountDetail}>
                  {request.provider} · {request.account_number}
                </Text>
                <Text style={styles.accountDetail}>
                  Fee {formatMoney(request.fee)} · Net{' '}
                  {formatMoney(request.net_amount)}
                </Text>
                <Text style={styles.requestDate}>
                  {formatDate(request.created_at)}
                </Text>
              </Card>
            ))
          )}
        </ScrollView>
      </StateView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  sectionTitle: {
    color: colors.text,
    fontSize: 17,
    fontWeight: '700',
  },
  sectionSubtitle: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 4,
    marginBottom: 12,
  },
  balanceLine: {
    color: colors.muted,
    fontSize: 13,
    marginBottom: 14,
  },
  balanceValue: {
    color: colors.text,
    fontWeight: '700',
  },
  summaryRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 6,
  },
  summaryLabel: {
    color: colors.muted,
    fontSize: 13,
  },
  summaryValue: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '600',
  },
  netLabel: {
    color: colors.text,
    fontWeight: '700',
  },
  netValue: {
    color: colors.mint,
    fontSize: 15,
    fontWeight: '800',
  },
  cooldownNote: {
    color: colors.orange,
    fontSize: 12,
    marginTop: 10,
  },
  formError: {
    color: colors.danger,
    fontSize: 13,
    marginTop: 10,
  },
  success: {
    color: colors.mint,
    fontSize: 13,
    marginTop: 10,
  },
  listTitle: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '700',
    marginTop: 8,
    marginBottom: 8,
  },
  emptyText: {
    color: colors.muted,
    fontSize: 13,
    marginBottom: 12,
  },
  accountRow: {
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 14,
    marginBottom: 8,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  accountRowSelected: {
    borderColor: colors.orange,
    backgroundColor: '#fff6f2',
  },
  accountDetails: {
    flexShrink: 1,
    paddingRight: 10,
  },
  accountTitle: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '700',
  },
  accountDetail: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 4,
  },
  radio: {
    width: 20,
    height: 20,
    borderRadius: 10,
    borderWidth: 2,
    borderColor: colors.muted,
  },
  radioSelected: {
    borderColor: colors.orange,
    backgroundColor: colors.orange,
  },
  requestHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  requestAmount: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '700',
  },
  requestDate: {
    color: colors.muted,
    fontSize: 11,
    marginTop: 6,
  },
});
