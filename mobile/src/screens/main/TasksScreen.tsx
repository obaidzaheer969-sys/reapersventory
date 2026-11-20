import { useFocusEffect, useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useCallback, useState } from 'react';
import {
  FlatList,
  Linking,
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
import { StatusPill } from '../../components/StatusPill';
import type { RootStackParamList } from '../../navigation/types';
import { colors, radius } from '../../theme';
import type { ID, Task } from '../../types';
import { formatDuration, formatMoney } from '../../utils/format';

type TaskAction = 'start' | 'complete';

function isActiveTask(status: string): boolean {
  const value = status.toLowerCase();

  return ['active', 'started', 'in_progress', 'in progress', 'running'].some(
    key => value.includes(key),
  );
}

export function TasksScreen() {
  const navigation = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  const [tasks, setTasks] = useState<Task[]>([]);
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
      setTasks(await endpoints.getTasks());
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

  const handleAction = useCallback(
    async (task: Task, action: TaskAction) => {
      setBusyId(task.id);
      setActionError(null);

      try {
        if (action === 'start') {
          await endpoints.startTask(task.id);
        } else {
          await endpoints.completeTask(task.id);
        }

        await load(true);
      } catch (err) {
        setActionError(getErrorMessage(err));
      } finally {
        setBusyId(null);
      }
    },
    [load],
  );

  const handleOpenLink = useCallback(
    async (task: Task) => {
      setBusyId(task.id);
      setActionError(null);

      try {
        await Linking.openURL(task.url).catch(() => undefined);
        await endpoints.markTaskOpened(task.id);
        await load(true);
      } catch (err) {
        setActionError(getErrorMessage(err));
      } finally {
        setBusyId(null);
      }
    },
    [load],
  );

  const renderItem = useCallback(
    ({ item }: { item: Task }) => {
      const active = isActiveTask(item.status);
      const isBusy = busyId === item.id;

      return (
        <Card>
          <View style={styles.rowHeader}>
            <Text style={styles.title} numberOfLines={2}>
              {item.title}
            </Text>
            <StatusPill label={item.status} />
          </View>
          {item.description ? (
            <Text style={styles.description}>{item.description}</Text>
          ) : null}
          <View style={styles.metaRow}>
            <View style={styles.metaChip}>
              <Text style={styles.metaText}>
                Reward {formatMoney(item.reward)}
              </Text>
            </View>
            <View style={styles.metaChip}>
              <Text style={styles.metaText}>
                Duration {formatDuration(item.duration_seconds)}
              </Text>
            </View>
          </View>
          {active ? (
            <Text style={styles.countdown}>
              Time left {formatDuration(item.remaining_seconds)}
            </Text>
          ) : null}
          {item.proof_required ? (
            <Text style={styles.note}>Proof of completion is required.</Text>
          ) : null}
          {item.embedded ? (
            <View style={styles.linkRow}>
              <PrimaryButton
                title="Watch video"
                variant="outline"
                onPress={() =>
                  navigation.navigate('TaskRun', {
                    id: item.id,
                    mode: 'task',
                    title: item.title,
                    embedUrl: item.embed_url ?? undefined,
                    targetUrl: item.url || item.embed_url || undefined,
                    reward: item.reward,
                  })
                }
                disabled={busyId !== null && !isBusy}
              />
            </View>
          ) : item.url ? (
            <>
              <TouchableOpacity
                onPress={() => handleOpenLink(item)}
                disabled={isBusy}
                style={styles.linkRow}>
                <Text style={styles.link}>Open task link</Text>
              </TouchableOpacity>
              {!item.opened ? (
                <Text style={styles.note}>Open the link to start the timer.</Text>
              ) : null}
            </>
          ) : null}
          {item.can_start || (active && !item.embedded) ? (
            <View style={styles.actions}>
              {item.can_start ? (
                <View style={styles.actionButton}>
                  <PrimaryButton
                    title="Start task"
                    onPress={() => handleAction(item, 'start')}
                    loading={isBusy}
                    disabled={busyId !== null && !isBusy}
                  />
                </View>
              ) : null}
              {active && !item.embedded ? (
                <View style={styles.actionButton}>
                  <PrimaryButton
                    title="Complete"
                    variant="secondary"
                    onPress={() => handleAction(item, 'complete')}
                    loading={isBusy}
                    disabled={busyId !== null && !isBusy}
                  />
                </View>
              ) : null}
            </View>
          ) : null}
          {active && item.embedded ? (
            <Text style={styles.note}>
              Open the video player to finish the timer and claim.
            </Text>
          ) : null}
        </Card>
      );
    },
    [busyId, handleAction, handleOpenLink, navigation],
  );

  return (
    <Screen scroll={false}>
      {actionError ? <Text style={styles.actionError}>{actionError}</Text> : null}
      <StateView
        loading={loading}
        error={error}
        empty={tasks.length === 0}
        emptyText="No tasks are available right now."
        onRetry={() => load(false)}>
        <FlatList
          data={tasks}
          keyExtractor={item => String(item.id)}
          renderItem={renderItem}
          contentContainerStyle={styles.list}
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
  rowHeader: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
  },
  title: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '700',
    flexShrink: 1,
    paddingRight: 10,
  },
  description: {
    color: colors.muted,
    fontSize: 13,
    marginTop: 6,
    lineHeight: 19,
  },
  metaRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    marginTop: 12,
  },
  metaChip: {
    backgroundColor: '#eef2f7',
    borderRadius: radius.sm,
    paddingHorizontal: 10,
    paddingVertical: 6,
    marginRight: 8,
    marginBottom: 6,
  },
  metaText: {
    color: colors.navySoft,
    fontSize: 12,
    fontWeight: '600',
  },
  countdown: {
    color: colors.orange,
    fontSize: 12,
    fontWeight: '700',
    marginTop: 4,
  },
  note: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 6,
  },
  linkRow: {
    marginTop: 10,
  },
  link: {
    color: colors.orange,
    fontSize: 13,
    fontWeight: '700',
  },
  actions: {
    flexDirection: 'row',
    marginTop: 14,
  },
  actionButton: {
    flex: 1,
    marginRight: 8,
  },
  actionError: {
    color: colors.danger,
    fontSize: 13,
    marginBottom: 8,
  },
});
