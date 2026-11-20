import { useFocusEffect } from '@react-navigation/native';
import { useCallback, useState } from 'react';
import { FlatList, RefreshControl, StyleSheet, Text } from 'react-native';
import { getErrorMessage } from '../api/client';
import { endpoints } from '../api/endpoints';
import { Card } from '../components/Card';
import { Screen } from '../components/Screen';
import { StateView } from '../components/StateView';
import { colors } from '../theme';
import type { Announcement } from '../types';
import { formatDateTime } from '../utils/format';

export function AnnouncementsScreen() {
  const [announcements, setAnnouncements] = useState<Announcement[]>([]);
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
      setAnnouncements(await endpoints.getAnnouncements());
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
    <Screen scroll={false}>
      <StateView
        loading={loading}
        error={error}
        empty={announcements.length === 0}
        emptyText="There are no announcements right now."
        onRetry={() => load(false)}>
        <FlatList
          data={announcements}
          keyExtractor={item => String(item.id)}
          contentContainerStyle={styles.list}
          renderItem={({ item }) => (
            <Card>
              <Text style={styles.title}>{item.title}</Text>
              <Text style={styles.body}>{item.body}</Text>
              <Text style={styles.date}>{formatDateTime(item.created_at)}</Text>
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
  title: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '700',
  },
  body: {
    color: colors.muted,
    fontSize: 13,
    lineHeight: 20,
    marginTop: 8,
  },
  date: {
    color: colors.muted,
    fontSize: 11,
    marginTop: 12,
  },
});
