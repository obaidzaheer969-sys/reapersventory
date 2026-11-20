import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Linking, StyleSheet, Text, View } from 'react-native';
import { WebView, type WebViewMessageEvent } from 'react-native-webview';
import { getErrorMessage } from '../api/client';
import { endpoints } from '../api/endpoints';
import { PrimaryButton } from '../components/PrimaryButton';
import { Screen } from '../components/Screen';
import { StateView } from '../components/StateView';
import type { RootStackParamList } from '../navigation/types';
import { colors, radius } from '../theme';
import type { TaskProgress } from '../types';
import { formatDuration, formatMoney } from '../utils/format';
import {
  parsePlayerMessage,
  PLAYER_BRIDGE_SCRIPT,
  stripEmbedOrigin,
} from '../utils/player';
import { countdownFromDeadline, deadlineFromRemaining } from '../utils/timer';

type TaskRunProps = NativeStackScreenProps<RootStackParamList, 'TaskRun'>;

const PROGRESS_INTERVAL_MS = 5000;
const TICK_INTERVAL_MS = 1000;

function hasStarted(status: string): boolean {
  const value = status.toLowerCase();

  return ['active', 'started', 'in_progress', 'in progress', 'running'].some(
    key => value.includes(key),
  );
}

function buildInitialProgress(
  watchedSeconds: number,
  requiredSeconds: number,
  timerRemainingSeconds: number,
  timerPaused: boolean,
  canClaim: boolean,
): TaskProgress {
  return {
    ok: true,
    watched_seconds: watchedSeconds,
    required_seconds: requiredSeconds,
    remaining_watch_seconds: Math.max(0, requiredSeconds - watchedSeconds),
    timer_remaining_seconds: timerRemainingSeconds,
    timer_paused: timerPaused,
    can_claim: canClaim,
  };
}

export function TaskRunScreen({ route, navigation }: TaskRunProps) {
  const { id, mode, title, reward } = route.params;
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);
  const [progress, setProgress] = useState<TaskProgress | null>(null);
  const [displayRemaining, setDisplayRemaining] = useState(0);
  const [timerDeadline, setTimerDeadline] = useState<number | null>(null);
  const [playing, setPlaying] = useState(false);
  const [claiming, setClaiming] = useState(false);
  const [itemEmbedUrl, setItemEmbedUrl] = useState<string | null>(null);
  const [itemUrl, setItemUrl] = useState<string | null>(null);
  const isMountedRef = useRef(true);
  const playingRef = useRef(false);

  const applyProgress = useCallback((result: TaskProgress) => {
    if (!isMountedRef.current) {
      return;
    }

    setProgress(result);
    setDisplayRemaining(result.timer_remaining_seconds);
    setTimerDeadline(
      result.timer_paused
        ? null
        : deadlineFromRemaining(result.timer_remaining_seconds),
    );
    setActionError(null);
  }, []);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    setActionError(null);

    try {
      const items =
        mode === 'task'
          ? await endpoints.getTasks()
          : await endpoints.getCampaigns();
      const item = items.find(entry => String(entry.id) === String(id));

      if (!item) {
        if (isMountedRef.current) {
          setError('This item is no longer available.');
        }
        return;
      }

      if (!hasStarted(item.status)) {
        if (mode === 'task') {
          await endpoints.startTask(id, { opened: true });
        } else {
          await endpoints.startCampaign(id, { opened: true });
        }
      }

      const requiredSeconds =
        'duration_seconds' in item ? item.duration_seconds : 0;
      const timerRemainingSeconds =
        'remaining_seconds' in item ? item.remaining_seconds : 0;
      const directUrl = 'url' in item ? item.url : null;
      const initialProgress = buildInitialProgress(
        item.watched_seconds,
        requiredSeconds,
        timerRemainingSeconds,
        item.timer_paused,
        item.can_claim,
      );

      if (isMountedRef.current) {
        setItemEmbedUrl(item.embed_url);
        setItemUrl(directUrl || item.embed_url);
        setProgress(initialProgress);
        setDisplayRemaining(initialProgress.timer_remaining_seconds);
        setTimerDeadline(
          initialProgress.timer_paused
            ? null
            : deadlineFromRemaining(initialProgress.timer_remaining_seconds),
        );
      }
    } catch (err) {
      if (isMountedRef.current) {
        setError(getErrorMessage(err));
      }
    } finally {
      if (isMountedRef.current) {
        setLoading(false);
      }
    }
  }, [id, mode]);

  useEffect(() => {
    isMountedRef.current = true;
    load();

    return () => {
      isMountedRef.current = false;
      playingRef.current = false;
    };
  }, [load]);

  const reportProgress = useCallback(
    async (nextPlaying: boolean): Promise<TaskProgress | null> => {
      try {
        const result =
          mode === 'task'
            ? await endpoints.sendTaskProgress(id, nextPlaying)
            : await endpoints.sendCampaignProgress(id, nextPlaying);

        applyProgress(result);

        return result;
      } catch (err) {
        if (isMountedRef.current) {
          setActionError(getErrorMessage(err));
        }

        return null;
      }
    },
    [applyProgress, id, mode],
  );

  useEffect(() => {
    if (!playing) {
      return;
    }

    const timer = setInterval(() => {
      reportProgress(true);
    }, PROGRESS_INTERVAL_MS);

    return () => clearInterval(timer);
  }, [playing, reportProgress]);

  useEffect(() => {
    if (timerDeadline === null) {
      return;
    }

    const update = () => setDisplayRemaining(countdownFromDeadline(timerDeadline));

    update();

    const timer = setInterval(update, TICK_INTERVAL_MS);

    return () => clearInterval(timer);
  }, [timerDeadline]);

  const handlePlaybackStarted = useCallback(async () => {
    if (playingRef.current || claiming) {
      return;
    }

    playingRef.current = true;
    setPlaying(true);
    setActionError(null);
    await reportProgress(true);
  }, [claiming, reportProgress]);

  const handlePaused = useCallback(async () => {
    if (!playingRef.current || claiming) {
      return;
    }

    playingRef.current = false;
    setPlaying(false);
    await reportProgress(false);
  }, [claiming, reportProgress]);

  const handleWebViewMessage = useCallback(
    (event: WebViewMessageEvent) => {
      const playerEvent = parsePlayerMessage(event.nativeEvent.data);

      if (playerEvent === 'playing') {
        handlePlaybackStarted();
      } else if (playerEvent === 'paused') {
        handlePaused();
      }
    },
    [handlePaused, handlePlaybackStarted],
  );

  const handleClaim = useCallback(async () => {
    if (claiming) {
      return;
    }

    setClaiming(true);
    setActionError(null);

    try {
      await reportProgress(true);

      if (mode === 'task') {
        await endpoints.completeTask(id, { playing: true });
      } else {
        await endpoints.completeCampaign(id, { playing: true });
      }

      playingRef.current = false;

      if (isMountedRef.current) {
        navigation.goBack();
      }
    } catch (err) {
      if (isMountedRef.current) {
        setActionError(getErrorMessage(err));
      }
    } finally {
      if (isMountedRef.current) {
        setClaiming(false);
      }
    }
  }, [claiming, id, mode, navigation, reportProgress]);

  const resolvedEmbedUrl = route.params.embedUrl ?? itemEmbedUrl;
  const webViewUrl = stripEmbedOrigin(resolvedEmbedUrl);
  const resolvedTargetUrl = route.params.targetUrl ?? itemUrl;

  const handleOpenExternal = useCallback(() => {
    if (!resolvedTargetUrl) {
      setActionError('No external link is available.');
      return;
    }

    Linking.openURL(resolvedTargetUrl).catch(() => {
      if (isMountedRef.current) {
        setActionError('Could not open the external page.');
      }
    });
  }, [resolvedTargetUrl]);

  const canClaim = progress?.can_claim === true;
  const watchedSeconds = progress?.watched_seconds ?? 0;
  const requiredSeconds = progress?.required_seconds ?? 0;

  return (
    <Screen>
      <StateView loading={loading} error={error} onRetry={() => load()}>
        <View style={styles.content}>
          <Text style={styles.title}>{title}</Text>
          {reward !== undefined ? (
            <Text style={styles.reward}>Reward {formatMoney(reward)}</Text>
          ) : null}

          {webViewUrl ? (
            <View style={styles.player}>
              <WebView
                source={{ uri: webViewUrl }}
                style={styles.webView}
                allowsFullscreenVideo
                javaScriptEnabled
                domStorageEnabled
                injectedJavaScriptBeforeContentLoaded={PLAYER_BRIDGE_SCRIPT}
                onMessage={handleWebViewMessage}
              />
            </View>
          ) : (
            <View style={styles.fallback}>
              <Text style={styles.fallbackText}>
                This video cannot be played here.
              </Text>
              <PrimaryButton
                title="Open external page"
                variant="outline"
                onPress={handleOpenExternal}
              />
            </View>
          )}

          <Text style={styles.playerHint}>
            The timer starts when the video plays. If it does not start, tap
            "Playback started".
          </Text>

          <View style={styles.stats}>
            <View style={styles.statRow}>
              <Text style={styles.statLabel}>Watch progress</Text>
              <Text style={styles.statValue}>
                {formatDuration(watchedSeconds)} / {formatDuration(requiredSeconds)}
              </Text>
            </View>
            <View style={styles.statRow}>
              <Text style={styles.statLabel}>Timer remaining</Text>
              <Text style={styles.statValue}>
                {formatDuration(displayRemaining)}
              </Text>
            </View>
            {progress?.timer_paused ? (
              <Text style={styles.pauseNote}>
                Timer paused — start the video playback to resume.
              </Text>
            ) : null}
          </View>

          <View style={styles.controls}>
            <View style={styles.control}>
              <PrimaryButton
                title="Playback started"
                onPress={handlePlaybackStarted}
                disabled={playing || claiming}
              />
            </View>
            <View style={styles.control}>
              <PrimaryButton
                title="Playback paused"
                variant="secondary"
                onPress={handlePaused}
                disabled={!playing || claiming}
              />
            </View>
          </View>

          <View style={styles.claim}>
            <PrimaryButton
              title="Claim reward"
              onPress={handleClaim}
              loading={claiming}
              disabled={!canClaim || claiming}
            />
          </View>

          {actionError ? (
            <Text style={styles.actionError}>{actionError}</Text>
          ) : null}
        </View>
      </StateView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  content: {
    flex: 1,
  },
  title: {
    color: colors.text,
    fontSize: 18,
    fontWeight: '700',
  },
  reward: {
    color: colors.muted,
    fontSize: 13,
    marginTop: 4,
  },
  player: {
    height: 260,
    borderRadius: radius.md,
    overflow: 'hidden',
    backgroundColor: colors.navy,
    marginTop: 16,
  },
  webView: {
    flex: 1,
  },
  fallback: {
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 16,
    marginTop: 16,
  },
  fallbackText: {
    color: colors.muted,
    fontSize: 13,
    marginBottom: 12,
  },
  playerHint: {
    color: colors.muted,
    fontSize: 12,
    lineHeight: 18,
    marginTop: 10,
  },
  stats: {
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.border,
    padding: 14,
    marginTop: 16,
  },
  statRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 4,
  },
  statLabel: {
    color: colors.muted,
    fontSize: 13,
  },
  statValue: {
    color: colors.text,
    fontSize: 13,
    fontWeight: '700',
  },
  pauseNote: {
    color: colors.orange,
    fontSize: 12,
    marginTop: 8,
  },
  controls: {
    flexDirection: 'row',
    marginTop: 16,
  },
  control: {
    flex: 1,
    marginRight: 8,
  },
  claim: {
    marginTop: 16,
  },
  actionError: {
    color: colors.danger,
    fontSize: 13,
    marginTop: 12,
  },
});
