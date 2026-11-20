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
import type { Campaign, ID } from '../../types';
import { formatDate, formatMoney } from '../../utils/format';
import { percentWidthStyle } from '../../utils/styles';

type CampaignAction = 'start' | 'complete';

function isActiveCampaign(status: string): boolean {
  const value = status.toLowerCase();

  return ['active', 'started', 'in_progress', 'in progress', 'running'].some(
    key => value.includes(key),
  );
}

function canStartCampaign(status: string): boolean {
  const value = status.toLowerCase();

  return ['available', 'pending', 'not_started', 'not started', 'draft'].some(
    key => value.includes(key),
  );
}

export function CampaignsScreen() {
  const navigation = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  const [campaigns, setCampaigns] = useState<Campaign[]>([]);
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
      setCampaigns(await endpoints.getCampaigns());
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
    async (campaign: Campaign, action: CampaignAction) => {
      setBusyId(campaign.id);
      setActionError(null);

      try {
        if (action === 'start') {
          await endpoints.startCampaign(campaign.id);
        } else {
          await endpoints.completeCampaign(campaign.id);
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
    async (campaign: Campaign) => {
      setBusyId(campaign.id);
      setActionError(null);

      try {
        await Linking.openURL(campaign.embed_url ?? '').catch(() => undefined);
        await endpoints.markCampaignOpened(campaign.id);
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
    ({ item }: { item: Campaign }) => {
      const active = isActiveCampaign(item.status);
      const canStart = canStartCampaign(item.status);
      const isBusy = busyId === item.id;
      const percent =
        item.total_actions > 0
          ? Math.min(100, (item.completed_actions / item.total_actions) * 100)
          : 0;

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
                Admin fee {formatMoney(item.admin_fee)}
              </Text>
            </View>
          </View>

          <View style={styles.progressHeader}>
            <Text style={styles.progressLabel}>Completed actions</Text>
            <Text style={styles.progressValue}>
              {item.completed_actions} / {item.total_actions}
            </Text>
          </View>
          <View style={styles.progressTrack}>
            <View style={[styles.progressFill, percentWidthStyle(percent)]} />
          </View>
          <Text style={styles.created}>Created {formatDate(item.created_at)}</Text>

          {item.embedded ? (
            <View style={styles.linkRow}>
              <PrimaryButton
                title="Watch video"
                variant="outline"
                onPress={() =>
                  navigation.navigate('TaskRun', {
                    id: item.id,
                    mode: 'campaign',
                    title: item.title,
                    embedUrl: item.embed_url ?? undefined,
                    targetUrl: item.embed_url ?? undefined,
                    reward: item.reward,
                  })
                }
                disabled={busyId !== null && !isBusy}
              />
            </View>
          ) : item.embed_url ? (
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

          {canStart || active ? (
            <View style={styles.actions}>
              {canStart ? (
                <View style={styles.actionButton}>
                  <PrimaryButton
                    title="Start campaign"
                    onPress={() => handleAction(item, 'start')}
                    loading={isBusy}
                    disabled={busyId !== null && !isBusy}
                  />
                </View>
              ) : null}
              {active ? (
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
        empty={campaigns.length === 0}
        emptyText="No campaigns are available right now."
        onRetry={() => load(false)}>
        <FlatList
          data={campaigns}
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
  progressHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 10,
  },
  progressLabel: {
    color: colors.muted,
    fontSize: 12,
  },
  progressValue: {
    color: colors.text,
    fontSize: 12,
    fontWeight: '700',
  },
  progressTrack: {
    height: 8,
    borderRadius: radius.pill,
    backgroundColor: '#e8edf4',
    marginTop: 6,
    overflow: 'hidden',
  },
  progressFill: {
    height: 8,
    borderRadius: radius.pill,
    backgroundColor: colors.mint,
  },
  created: {
    color: colors.muted,
    fontSize: 11,
    marginTop: 8,
  },
  linkRow: {
    marginTop: 10,
  },
  link: {
    color: colors.orange,
    fontSize: 13,
    fontWeight: '700',
  },
  note: {
    color: colors.muted,
    fontSize: 12,
    marginTop: 6,
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
