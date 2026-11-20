<?php

namespace App\Services;

use App\Enums\CampaignStatus;
use App\Enums\TaskAttemptStatus;
use App\Enums\UserRole;
use App\Enums\WalletTransactionType;
use App\Models\EarningTask;
use App\Models\TaskAttempt;
use App\Models\User;
use App\Notifications\CampaignCompletedNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskService
{
    public function __construct(
        private readonly WalletService $wallet,
    ) {}

    public function timezone(): string
    {
        return (string) config('earning.timezone', 'Asia/Karachi');
    }

    public function nextResetAt(): Carbon
    {
        return Carbon::now($this->timezone())->addDay()->startOfDay();
    }

    /**
     * The UTC instant range is converted back to the application timezone,
     * because Eloquent stores datetime columns using the application timezone
     * (config app.timezone), not UTC.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function todayRangeUtc(): array
    {
        $appTimezone = (string) config('app.timezone', 'UTC');

        $start = Carbon::now($this->timezone())->startOfDay()->setTimezone($appTimezone);
        $end = Carbon::now($this->timezone())->endOfDay()->setTimezone($appTimezone);

        return [$start, $end];
    }

    public function completedToday(User $user, EarningTask $task): bool
    {
        [$start, $end] = $this->todayRangeUtc();

        return TaskAttempt::query()
            ->where('user_id', $user->id)
            ->where('earning_task_id', $task->id)
            ->where('status', TaskAttemptStatus::Completed->value)
            ->whereBetween('completed_at', [$start, $end])
            ->exists();
    }

    /**
     * Campaign tasks can only be completed once per earner, not once per day.
     */
    public function hasCompletedEver(User $user, EarningTask $task): bool
    {
        return TaskAttempt::query()
            ->where('user_id', $user->id)
            ->where('earning_task_id', $task->id)
            ->where('status', TaskAttemptStatus::Completed->value)
            ->exists();
    }

    /**
     * @return Collection<int, int>
     */
    public function completedTaskIdsToday(User $user): Collection
    {
        [$start, $end] = $this->todayRangeUtc();

        return TaskAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', TaskAttemptStatus::Completed->value)
            ->whereBetween('completed_at', [$start, $end])
            ->whereIn('earning_task_id', EarningTask::query()->system()->select('id'))
            ->pluck('earning_task_id')
            ->unique()
            ->values();
    }

    /**
     * @return Collection<int, int>
     */
    public function completedCampaignIds(User $user): Collection
    {
        return TaskAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', TaskAttemptStatus::Completed->value)
            ->whereIn('earning_task_id', EarningTask::query()->campaigns()->select('id'))
            ->pluck('earning_task_id')
            ->unique()
            ->values();
    }

    /**
     * @return Collection<int, TaskAttempt>
     */
    public function inProgressAttempts(User $user): Collection
    {
        return TaskAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', TaskAttemptStatus::InProgress->value)
            ->latest('id')
            ->get()
            ->keyBy('earning_task_id');
    }

    public function completedTodayCount(User $user): int
    {
        return $this->completedTaskIdsToday($user)->count();
    }

    public function remainingTodayCount(User $user): int
    {
        $activeIds = EarningTask::query()->system()->active()->pluck('id');

        if ($activeIds->isEmpty()) {
            return 0;
        }

        $completed = $this->completedTaskIdsToday($user)->intersect($activeIds)->count();

        return max(0, $activeIds->count() - $completed);
    }

    public function startAttempt(User $user, EarningTask $task, ?string $ipAddress = null): TaskAttempt
    {
        if (! $task->is_active) {
            throw ValidationException::withMessages([
                'task' => 'This task is not available right now.',
            ]);
        }

        if ($task->isCampaign()) {
            $this->assertCampaignCanBeWorkedOn($user, $task);
        } elseif ($this->completedToday($user, $task)) {
            throw ValidationException::withMessages([
                'task' => 'You have already completed this task today. It unlocks again at 12:00 midnight.',
            ]);
        }

        return DB::transaction(function () use ($user, $task, $ipAddress) {
            $existing = TaskAttempt::query()
                ->where('user_id', $user->id)
                ->where('earning_task_id', $task->id)
                ->where('status', TaskAttemptStatus::InProgress->value)
                ->latest('id')
                ->first();

            if ($existing) {
                $timeoutHours = (int) config('earning.attempt_timeout_hours', 6);

                if ($existing->started_at->gt(now()->subHours($timeoutHours))) {
                    return $existing;
                }

                $existing->delete();
            }

            $startedAt = now();

            return TaskAttempt::query()->create([
                'user_id' => $user->id,
                'earning_task_id' => $task->id,
                'status' => TaskAttemptStatus::InProgress,
                'started_at' => $startedAt,
                'expires_at' => $startedAt->copy()->addSeconds($task->duration_seconds),
                'timer_paused_at' => $startedAt,
                'ip_address' => $ipAddress,
            ]);
        });
    }

    public function markRealPageOpened(User $user, TaskAttempt $attempt): TaskAttempt
    {
        if ($attempt->user_id !== $user->id) {
            abort(403, 'This task does not belong to you.');
        }

        if ($attempt->opened_at === null && ! $attempt->isCompleted() && ! $attempt->isFailed()) {
            $attempt->forceFill(['opened_at' => now()])->save();
        }

        return $attempt->refresh();
    }

    public function pauseTimer(User $user, TaskAttempt $attempt): TaskAttempt
    {
        return DB::transaction(function () use ($user, $attempt) {
            /** @var TaskAttempt $attempt */
            $attempt = TaskAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if ($attempt->user_id !== $user->id) {
                abort(403, 'This task does not belong to you.');
            }

            if ($attempt->isCompleted() || $attempt->isFailed() || $attempt->isTimerPaused()) {
                return $attempt;
            }

            if (! now()->lt($attempt->expires_at)) {
                return $attempt;
            }

            $attempt->forceFill(['timer_paused_at' => now()])->save();

            return $attempt->refresh();
        });
    }

    public function resumeTimer(User $user, TaskAttempt $attempt): TaskAttempt
    {
        return DB::transaction(function () use ($user, $attempt) {
            /** @var TaskAttempt $attempt */
            $attempt = TaskAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if ($attempt->user_id !== $user->id) {
                abort(403, 'This task does not belong to you.');
            }

            if ($attempt->isCompleted() || $attempt->isFailed() || ! $attempt->isTimerPaused()) {
                return $attempt;
            }

            $pausedSeconds = max(0, now()->getTimestamp() - $attempt->timer_paused_at->getTimestamp());

            $attempt->forceFill([
                'expires_at' => $attempt->expires_at->copy()->addSeconds($pausedSeconds),
                'timer_paused_at' => null,
            ])->save();

            return $attempt->refresh();
        });
    }

    /**
     * Credit embedded watch time from a playback heartbeat.
     *
     * The client is never trusted with a number: only wall-clock time between
     * two nearby heartbeats is counted, so seeking or spamming the endpoint
     * cannot skip the required watch time.
     */
    public function recordEmbedHeartbeat(User $user, TaskAttempt $attempt, bool $playing): TaskAttempt
    {
        return DB::transaction(function () use ($user, $attempt, $playing) {
            /** @var TaskAttempt $attempt */
            $attempt = TaskAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if ($attempt->user_id !== $user->id) {
                abort(403, 'This task does not belong to you.');
            }

            /** @var EarningTask $task */
            $task = $attempt->task;

            if (! $task->hasEmbeddedPlayer()) {
                throw ValidationException::withMessages([
                    'task' => 'This task does not support an embedded player.',
                ]);
            }

            if ($attempt->isCompleted() || $attempt->isFailed()) {
                return $attempt;
            }

            $now = now();
            $credited = 0;

            if ($playing && $attempt->embed_seen_at !== null) {
                $gap = $now->getTimestamp() - $attempt->embed_seen_at->getTimestamp();

                if ($gap >= 1 && $gap <= 15) {
                    $credited = min(10, $gap);
                }
            }

            $watched = (int) $attempt->watched_seconds;

            if ($playing) {
                $watched = min((int) $task->duration_seconds, $watched + $credited);
            }

            $attempt->forceFill([
                'opened_at' => $attempt->opened_at ?? ($playing ? $now : null),
                'embed_played_at' => $attempt->embed_played_at ?? ($playing ? $now : null),
                'embed_seen_at' => $playing ? $now : null,
                'watched_seconds' => $watched,
            ])->save();

            return $attempt->refresh();
        });
    }

    public function failAttempt(User $user, TaskAttempt $attempt): TaskAttempt
    {
        return DB::transaction(function () use ($user, $attempt) {
            /** @var TaskAttempt $attempt */
            $attempt = TaskAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if ($attempt->user_id !== $user->id) {
                abort(403, 'This task does not belong to you.');
            }

            if ($attempt->isCompleted() || $attempt->isFailed()) {
                return $attempt;
            }

            if (! now()->lt($attempt->effectiveExpiresAt())) {
                return $attempt;
            }

            $attempt->forceFill([
                'status' => TaskAttemptStatus::Failed,
                'failed_at' => now(),
            ])->save();

            return $attempt->refresh();
        });
    }

    public function completeAttempt(User $user, TaskAttempt $attempt): TaskAttempt
    {
        $completedCampaign = null;

        $attempt = DB::transaction(function () use ($user, $attempt, &$completedCampaign) {
            /** @var TaskAttempt $attempt */
            $attempt = TaskAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if ($attempt->user_id !== $user->id) {
                abort(403, 'This task does not belong to you.');
            }

            if ($attempt->isCompleted()) {
                return $attempt;
            }

            if ($attempt->isFailed() || $attempt->failed_at !== null) {
                throw ValidationException::withMessages([
                    'task' => 'The task failed — it was left before the timer finished. Start the task again.',
                ]);
            }

            $task = $attempt->task;

            if ($task->hasEmbeddedPlayer() && $attempt->isTimerPaused() && ! $attempt->hasStartedEmbeddedPlayback()) {
                throw ValidationException::withMessages([
                    'task' => 'Play the video first — watch time will not count without playback.',
                ]);
            }

            if (! $attempt->isReadyToClaim()) {
                $message = $attempt->isTimerPaused() && $task->hasEmbeddedPlayer()
                    ? 'The timer is paused because playback is paused. Press play to resume it.'
                    : 'The timer has not finished yet. '.$attempt->remainingSeconds().' seconds remaining.';

                throw ValidationException::withMessages([
                    'task' => $message,
                ]);
            }

            if ($task->hasEmbeddedPlayer()) {
                if (! $attempt->hasStartedEmbeddedPlayback()) {
                    throw ValidationException::withMessages([
                        'task' => 'Play the video first — watch time will not count without playback.',
                    ]);
                }

                $missingSeconds = max(0, (int) $task->duration_seconds - (int) $attempt->watched_seconds);

                if ($missingSeconds > 0) {
                    throw ValidationException::withMessages([
                        'task' => 'The video has not played long enough. '.$missingSeconds.' more seconds of playback, then claim.',
                    ]);
                }
            } elseif (! $attempt->hasOpenedRealPage()) {
                throw ValidationException::withMessages([
                    'task' => 'Open the task\'s real page first — you will not receive a reward without viewing the task.',
                ]);
            }

            if ($task->isCampaign()) {
                $completedCampaign = $this->completeCampaignAttempt($user, $attempt, $task);
            } else {
                $this->completeSystemAttempt($user, $attempt, $task);
            }

            return $attempt->refresh();
        });

        if ($completedCampaign) {
            $completedCampaign->owner?->notify(new CampaignCompletedNotification($completedCampaign));
        }

        return $attempt;
    }

    private function completeSystemAttempt(User $user, TaskAttempt $attempt, EarningTask $task): void
    {
        if ($this->completedToday($user, $task)) {
            throw ValidationException::withMessages([
                'task' => 'You have already completed this task today.',
            ]);
        }

        $attempt->forceFill([
            'status' => TaskAttemptStatus::Completed,
            'completed_at' => now(),
            'watched_seconds' => $task->duration_seconds,
            'reward_amount' => $task->reward,
        ])->save();

        $this->wallet->credit(
            $user,
            (float) $task->reward,
            WalletTransactionType::TaskReward,
            'Task reward: '.$task->title,
            $attempt,
        );
    }

    private function completeCampaignAttempt(User $user, TaskAttempt $attempt, EarningTask $task): ?EarningTask
    {
        /** @var EarningTask $locked */
        $locked = EarningTask::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();

        if (! $locked->isLiveCampaign()) {
            throw ValidationException::withMessages([
                'task' => 'This campaign is not available right now or is already complete.',
            ]);
        }

        if ($locked->user_id === $user->id) {
            throw ValidationException::withMessages([
                'task' => 'You cannot complete your own campaign.',
            ]);
        }

        if ($this->hasCompletedEver($user, $locked)) {
            throw ValidationException::withMessages([
                'task' => 'You have already completed this campaign task.',
            ]);
        }

        $attempt->forceFill([
            'status' => TaskAttemptStatus::Completed,
            'completed_at' => now(),
            'watched_seconds' => $locked->duration_seconds,
            'reward_amount' => $locked->reward,
        ])->save();

        $this->wallet->credit(
            $user,
            (float) $locked->reward,
            WalletTransactionType::TaskReward,
            'Campaign task reward: '.$locked->title,
            $attempt,
        );

        $locked->completed_quantity = (int) $locked->completed_quantity + 1;

        $finished = $locked->target_quantity !== null
            && $locked->completed_quantity >= $locked->target_quantity;

        if ($finished) {
            $locked->campaign_status = CampaignStatus::Completed;
            $locked->is_active = false;
        }

        $locked->save();

        $this->creditAdminFee($locked, $attempt);

        return $finished ? $locked->refresh() : null;
    }

    private function creditAdminFee(EarningTask $campaign, TaskAttempt $attempt): void
    {
        $fee = $campaign->feePerAction();

        if ($fee <= 0) {
            return;
        }

        /** @var User|null $admin */
        $admin = User::query()
            ->where('role', UserRole::SuperAdmin->value)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if ($admin === null) {
            return;
        }

        $this->wallet->credit(
            $admin,
            $fee,
            WalletTransactionType::CampaignAdminFee,
            'Campaign admin fee: '.$campaign->title.' (#'.$campaign->id.')',
            $attempt,
        );
    }

    private function assertCampaignCanBeWorkedOn(User $user, EarningTask $task): void
    {
        if ($task->campaign_status !== CampaignStatus::Active) {
            throw ValidationException::withMessages([
                'task' => 'This campaign is not live right now.',
            ]);
        }

        if ($task->user_id === $user->id) {
            throw ValidationException::withMessages([
                'task' => 'You cannot complete your own campaign.',
            ]);
        }

        if ($task->isSoldOut()) {
            throw ValidationException::withMessages([
                'task' => 'This campaign has reached its required quantity.',
            ]);
        }

        if ($this->hasCompletedEver($user, $task)) {
            throw ValidationException::withMessages([
                'task' => 'You have already completed this campaign task.',
            ]);
        }
    }
}
