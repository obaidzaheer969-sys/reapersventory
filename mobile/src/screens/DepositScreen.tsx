import { useCallback, useEffect, useState } from 'react';
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
import { colors, radius } from '../theme';
import type { ID, PaymentMethod } from '../types';

export function DepositScreen() {
  const [methods, setMethods] = useState<PaymentMethod[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [selectedId, setSelectedId] = useState<ID | null>(null);
  const [amount, setAmount] = useState('');
  const [transactionId, setTransactionId] = useState('');
  const [note, setNote] = useState('');
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
      const result = await endpoints.getPaymentMethods();
      setMethods(result);
      setSelectedId(current => {
        if (current !== null && result.some(item => item.id === current)) {
          return current;
        }

        return result[0]?.id ?? null;
      });
    } catch (err) {
      setError(getErrorMessage(err));
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    load(false);
  }, [load]);

  const selectedMethod = methods.find(item => item.id === selectedId) ?? null;

  const handleSubmit = async () => {
    setFormError(null);
    setSuccessMessage(null);

    const numericAmount = Number.parseFloat(amount);

    if (!Number.isFinite(numericAmount) || numericAmount <= 0) {
      setFormError('Enter a valid deposit amount.');

      return;
    }

    if (selectedId === null) {
      setFormError('Select a payment method.');

      return;
    }

    setSubmitting(true);

    try {
      await endpoints.createDeposit({
        payment_method_id: selectedId,
        amount: numericAmount,
        transaction_id: transactionId.trim() || undefined,
        note: note.trim() || undefined,
      });
      setAmount('');
      setTransactionId('');
      setNote('');
      setSuccessMessage('Deposit submitted for review.');
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
            <Text style={styles.sectionTitle}>Make a deposit</Text>
            <Text style={styles.sectionSubtitle}>
              Send funds to one of the payment methods below, then submit the
              details for review.
            </Text>
            <Field
              label="Amount"
              value={amount}
              onChangeText={setAmount}
              placeholder="0.00"
              keyboardType="decimal-pad"
            />
            <Field
              label="Transaction ID (optional)"
              value={transactionId}
              onChangeText={setTransactionId}
              placeholder="Reference from your payment"
            />
            <Field
              label="Note (optional)"
              value={note}
              onChangeText={setNote}
              placeholder="Add a note"
              multiline
            />

            {formError ? <Text style={styles.formError}>{formError}</Text> : null}
            {successMessage ? (
              <Text style={styles.success}>{successMessage}</Text>
            ) : null}

            <PrimaryButton
              title="Submit deposit"
              onPress={() => handleSubmit()}
              loading={submitting}
            />
          </Card>

          <Text style={styles.listTitle}>Payment methods</Text>
          {methods.length === 0 ? (
            <Card>
              <Text style={styles.emptyText}>
                No payment methods are available right now.
              </Text>
            </Card>
          ) : (
            methods.map(method => {
              const selected = method.id === selectedId;

              return (
                <TouchableOpacity
                  key={String(method.id)}
                  onPress={() => setSelectedId(method.id)}
                  style={[
                    styles.methodRow,
                    selected && styles.methodRowSelected,
                  ]}>
                  <View style={styles.methodHeader}>
                    <Text style={styles.methodName}>{method.name}</Text>
                    <View
                      style={[styles.radio, selected && styles.radioSelected]}
                    />
                  </View>
                  {method.account_name ? (
                    <Text style={styles.methodDetail}>
                      Account name: {method.account_name}
                    </Text>
                  ) : null}
                  {method.account_number ? (
                    <Text style={styles.methodDetail}>
                      Account number: {method.account_number}
                    </Text>
                  ) : null}
                  {selected ? (
                    <View style={styles.instructions}>
                      {method.instructions ? (
                        <Text style={styles.instructionsText}>
                          {method.instructions}
                        </Text>
                      ) : null}
                      {method.additional_info ? (
                        <Text style={styles.instructionsText}>
                          {method.additional_info}
                        </Text>
                      ) : null}
                    </View>
                  ) : null}
                </TouchableOpacity>
              );
            })
          )}
          {selectedMethod?.code ? (
            <Text style={styles.codeNote}>Code: {selectedMethod.code}</Text>
          ) : null}
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
    lineHeight: 18,
  },
  formError: {
    color: colors.danger,
    fontSize: 13,
    marginBottom: 10,
  },
  success: {
    color: colors.mint,
    fontSize: 13,
    marginBottom: 10,
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
  },
  methodRow: {
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 14,
    marginBottom: 8,
  },
  methodRowSelected: {
    borderColor: colors.orange,
    backgroundColor: '#fff6f2',
  },
  methodHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  methodName: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '700',
    flexShrink: 1,
    paddingRight: 10,
  },
  methodDetail: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 5,
  },
  instructions: {
    marginTop: 10,
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  instructionsText: {
    color: colors.navySoft,
    fontSize: 12,
    lineHeight: 18,
    marginBottom: 4,
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
  codeNote: {
    color: colors.muted,
    fontSize: 11,
    marginTop: 4,
    marginBottom: 16,
  },
});
