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
import { Screen } from '../components/Screen';
import { StateView } from '../components/StateView';
import { colors } from '../theme';
import type { ID, NotificationItem } from '../types';
import { formatDateTime } from '../utils/format';

export function NotificationsScreen() {
  const [notifications, setNotifications] = useState<NotificationItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);
  const [busyId, setBusyId] = useState<ID | null>(null);

  const load = useCallback(async (isRefresh: boolean) => {
    if (isRefresh) {
      setRefreshing(true);
    } else {
      setLoading(true);
    }

    setError(null);

    try {
      setNotifications(await endpoints.getNotifications());
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

  const markRead = useCallback(
    async (notification: NotificationItem) => {
      setBusyId(notification.id);
      setActionError(null);

      try {
        await endpoints.markNotificationRead(notification.id);
        await load(true);
      } catch (err) {
        setActionError(getErrorMessage(err));
      } finally {
        setBusyId(null);
      }
    },
    [load],
  );

  const unreadCount = notifications.filter(item => item.read_at === null).length;

  return (
    <Screen scroll={false}>
      {actionError ? <Text style={styles.actionError}>{actionError}</Text> : null}
      <StateView
        loading={loading}
        error={error}
        empty={notifications.length === 0}
        emptyText="You have no notifications."
        onRetry={() => load(false)}>
        <FlatList
          data={notifications}
          keyExtractor={item => String(item.id)}
          contentContainerStyle={styles.list}
          ListHeaderComponent={
            <Text style={styles.unread}>
              {unreadCount} unread notification(s)
            </Text>
          }
          renderItem={({ item }) => (
            <Card>
              <View style={styles.rowHeader}>
                <Text style={styles.title}>{item.title}</Text>
                {item.read_at === null ? (
                  <View style={styles.unreadDot} />
                ) : null}
              </View>
              <Text style={styles.body}>{item.body}</Text>
              <View style={styles.footer}>
                <Text style={styles.date}>{formatDateTime(item.created_at)}</Text>
                {item.read_at === null ? (
                  <TouchableOpacity
                    disabled={busyId === item.id}
                    onPress={() => markRead(item)}>
                    <Text style={styles.readLink}>Mark as read</Text>
                  </TouchableOpacity>
                ) : (
                  <Text style={styles.readLabel}>Read</Text>
                )}
              </View>
            </Card>
          )}
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
  unread: {
    color: colors.muted,
    fontSize: 13,
    marginBottom: 8,
  },
  rowHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  title: {
    color: colors.text,
    fontSize: 15,
    fontWeight: '700',
    flexShrink: 1,
    paddingRight: 8,
  },
  unreadDot: {
    width: 9,
    height: 9,
    borderRadius: 5,
    backgroundColor: colors.orange,
  },
  body: {
    color: colors.muted,
    fontSize: 13,
    marginTop: 6,
    lineHeight: 19,
  },
  footer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 12,
  },
  date: {
    color: colors.muted,
    fontSize: 11,
  },
  readLink: {
    color: colors.orange,
    fontSize: 12,
    fontWeight: '700',
  },
  readLabel: {
    color: colors.mint,
    fontSize: 12,
    fontWeight: '700',
  },
  actionError: {
    color: colors.danger,
    fontSize: 13,
    marginBottom: 8,
  },
});
