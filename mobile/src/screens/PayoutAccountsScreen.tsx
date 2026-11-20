import { useFocusEffect } from '@react-navigation/native';
import { useCallback, useState } from 'react';
import {
  FlatList,
  RefreshControl,
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
import { colors, radius } from '../theme';
import type { PayoutAccount } from '../types';

const PROVIDERS = [
  { label: 'Bank', value: 'bank' },
  { label: 'PayPal', value: 'paypal' },
  { label: 'Crypto', value: 'crypto' },
  { label: 'Other', value: 'other' },
];

export function PayoutAccountsScreen() {
  const [accounts, setAccounts] = useState<PayoutAccount[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [provider, setProvider] = useState('bank');
  const [accountName, setAccountName] = useState('');
  const [accountNumber, setAccountNumber] = useState('');
  const [isDefault, setIsDefault] = useState(false);
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
      setAccounts(await endpoints.getPayoutAccounts());
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

  const handleSubmit = async () => {
    if (accountName.trim() === '' || accountNumber.trim() === '') {
      setFormError('Enter an account name and account number.');
      setSuccessMessage(null);

      return;
    }

    setSubmitting(true);
    setFormError(null);
    setSuccessMessage(null);

    try {
      await endpoints.createPayoutAccount({
        provider,
        account_name: accountName.trim(),
        account_number: accountNumber.trim(),
        is_default: isDefault,
      });
      setAccountName('');
      setAccountNumber('');
      setIsDefault(false);
      setSuccessMessage('Payout account added.');
      await load(true);
    } catch (err) {
      setFormError(getErrorMessage(err));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <Screen scroll={false}>
      <StateView loading={loading} error={error} onRetry={() => load(false)}>
        <FlatList
          data={accounts}
          keyExtractor={item => String(item.id)}
          contentContainerStyle={styles.list}
          ListHeaderComponent={
            <View>
              <Card>
                <Text style={styles.sectionTitle}>Add a payout account</Text>
                <Text style={styles.sectionSubtitle}>
                  Select where you want to receive withdrawals.
                </Text>
                <Text style={styles.fieldLabel}>Provider</Text>
                <View style={styles.providerRow}>
                  {PROVIDERS.map(option => (
                    <TouchableOpacity
                      key={option.value}
                      onPress={() => setProvider(option.value)}
                      style={[
                        styles.providerChip,
                        provider === option.value && styles.providerChipActive,
                      ]}>
                      <Text
                        style={[
                          styles.providerText,
                          provider === option.value && styles.providerTextActive,
                        ]}>
                        {option.label}
                      </Text>
                    </TouchableOpacity>
                  ))}
                </View>
                <Field
                  label="Account name"
                  value={accountName}
                  onChangeText={setAccountName}
                  placeholder="Name on the account"
                  autoCapitalize="words"
                />
                <Field
                  label="Account number"
                  value={accountNumber}
                  onChangeText={setAccountNumber}
                  placeholder="Account number"
                />
                <TouchableOpacity
                  accessibilityRole="checkbox"
                  accessibilityState={{ checked: isDefault }}
                  onPress={() => setIsDefault(current => !current)}
                  style={styles.checkboxRow}>
                  <View
                    style={[
                      styles.checkbox,
                      isDefault && styles.checkboxActive,
                    ]}>
                    {isDefault ? <Text style={styles.checkmark}>✓</Text> : null}
                  </View>
                  <Text style={styles.checkboxLabel}>
                    Use as my default payout account
                  </Text>
                </TouchableOpacity>
                {formError ? <Text style={styles.formError}>{formError}</Text> : null}
                {successMessage ? (
                  <Text style={styles.success}>{successMessage}</Text>
                ) : null}
                <PrimaryButton
                  title="Add account"
                  onPress={() => handleSubmit()}
                  loading={submitting}
                />
              </Card>
              <Text style={styles.listTitle}>Your accounts</Text>
            </View>
          }
          renderItem={({ item }) => (
            <Card>
              <View style={styles.accountHeader}>
                <Text style={styles.accountTitle}>{item.provider_label}</Text>
                {item.is_default ? (
                  <StatusPill label="Default" tone="success" />
                ) : null}
              </View>
              <Text style={styles.accountDetail}>Name: {item.account_name}</Text>
              <Text style={styles.accountDetail}>
                Number: {item.account_number}
              </Text>
            </Card>
          )}
          ListEmptyComponent={
            <Text style={styles.emptyText}>
              No payout accounts yet. Add one using the form above.
            </Text>
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
  sectionTitle: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '700',
  },
  sectionSubtitle: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 4,
    marginBottom: 14,
  },
  fieldLabel: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '700',
    marginBottom: 8,
  },
  providerRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    marginBottom: 14,
  },
  providerChip: {
    borderRadius: radius.pill,
    borderWidth: 1,
    borderColor: colors.border,
    paddingHorizontal: 14,
    paddingVertical: 8,
    marginRight: 8,
    marginBottom: 8,
  },
  providerChipActive: {
    backgroundColor: colors.orange,
    borderColor: colors.orange,
  },
  providerText: {
    color: colors.navySoft,
    fontSize: 12,
    fontWeight: '700',
  },
  providerTextActive: {
    color: colors.white,
  },
  checkboxRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  checkbox: {
    width: 20,
    height: 20,
    borderRadius: 5,
    borderWidth: 1,
    borderColor: colors.muted,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 8,
  },
  checkboxActive: {
    backgroundColor: colors.orange,
    borderColor: colors.orange,
  },
  checkmark: {
    color: colors.white,
    fontSize: 13,
    fontWeight: '800',
  },
  checkboxLabel: {
    color: colors.text,
    fontSize: 13,
    flexShrink: 1,
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
    marginTop: 6,
    marginBottom: 8,
  },
  accountHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  accountTitle: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '700',
  },
  accountDetail: {
    color: colors.muted,
    fontSize: 13,
    marginTop: 6,
  },
  emptyText: {
    color: colors.muted,
    fontSize: 13,
    textAlign: 'center',
    paddingVertical: 20,
  },
});
