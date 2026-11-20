import type { ReactNode } from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';
import { colors } from '../theme';
import { PrimaryButton } from './PrimaryButton';

type StateViewProps = {
  children: ReactNode;
  loading?: boolean;
  error?: string | null;
  empty?: boolean;
  emptyText?: string;
  onRetry?: () => void;
};

export function StateView({
  children,
  loading = false,
  error = null,
  empty = false,
  emptyText = 'Nothing to show yet.',
  onRetry,
}: StateViewProps) {
  if (loading) {
    return (
      <View style={styles.state}>
        <ActivityIndicator color={colors.orange} size="large" />
        <Text style={styles.message}>Loading...</Text>
      </View>
    );
  }

  if (error) {
    return (
      <View style={styles.state}>
        <Text style={styles.title}>Could not load this page</Text>
        <Text style={styles.message}>{error}</Text>
        {onRetry ? (
          <View style={styles.retry}>
            <PrimaryButton title="Try again" onPress={onRetry} />
          </View>
        ) : null}
      </View>
    );
  }

  if (empty) {
    return (
      <View style={styles.state}>
        <Text style={styles.message}>{emptyText}</Text>
      </View>
    );
  }

  return <>{children}</>;
}

const styles = StyleSheet.create({
  state: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 24,
  },
  title: {
    color: colors.text,
    fontSize: 17,
    fontWeight: '700',
    marginBottom: 8,
    textAlign: 'center',
  },
  message: {
    color: colors.muted,
    fontSize: 14,
    marginTop: 12,
    textAlign: 'center',
  },
  retry: {
    marginTop: 20,
    width: 180,
  },
});
