<?php

namespace App\Services;

use App\Enums\CampaignStatus;
use App\Enums\TaskAttemptStatus;
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
                'task' => 'Yeh task filhal available nahi hai.',
            ]);
        }

        if ($task->isCampaign()) {
            $this->assertCampaignCanBeWorkedOn($user, $task);
        } elseif ($this->completedToday($user, $task)) {
            throw ValidationException::withMessages([
                'task' => 'Yeh task aaj complete ho chuka hai. Kal raat 12:00 baje dobara unlock hoga.',
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
                    'task' => 'Yeh task embedded player support nahi karta.',
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

            if (! now()->lt($attempt->expires_at)) {
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
                    'task' => 'Task fail ho gaya tha — timer poora hone se pehle task chhor diya gaya. Task dobara start karein.',
                ]);
            }

            $task = $attempt->task;

            if (now()->lt($attempt->expires_at)) {
                throw ValidationException::withMessages([
                    'task' => 'Timer abhi poora nahi hua. '.$attempt->remainingSeconds().' seconds baaki hain.',
                ]);
            }

            if ($task->hasEmbeddedPlayer()) {
                if (! $attempt->hasStartedEmbeddedPlayback()) {
                    throw ValidationException::withMessages([
                        'task' => 'Pehle video play karein — play ke bagair watch time count nahi hoga.',
                    ]);
                }

                $missingSeconds = max(0, (int) $task->duration_seconds - (int) $attempt->watched_seconds);

                if ($missingSeconds > 0) {
                    throw ValidationException::withMessages([
                        'task' => 'Video abhi poora nahi chala. '.$missingSeconds.' seconds aur play karein, phir claim karein.',
                    ]);
                }
            } elseif (! $attempt->hasOpenedRealPage()) {
                throw ValidationException::withMessages([
                    'task' => 'Pehle task ka real page kholein — bina task dekhe reward nahi milega.',
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
                'task' => 'Yeh task aaj pehle hi complete ho chuka hai.',
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
                'task' => 'Yeh campaign filhal available nahi hai ya poori ho chuki hai.',
            ]);
        }

        if ($locked->user_id === $user->id) {
            throw ValidationException::withMessages([
                'task' => 'Apni campaign khud complete nahi kar sakte.',
            ]);
        }

        if ($this->hasCompletedEver($user, $locked)) {
            throw ValidationException::withMessages([
                'task' => 'Aap yeh campaign task pehle complete kar chuke hain.',
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

        return $finished ? $locked->refresh() : null;
    }

    private function assertCampaignCanBeWorkedOn(User $user, EarningTask $task): void
    {
        if ($task->campaign_status !== CampaignStatus::Active) {
            throw ValidationException::withMessages([
                'task' => 'Yeh campaign filhal live nahi hai.',
            ]);
        }

        if ($task->user_id === $user->id) {
            throw ValidationException::withMessages([
                'task' => 'Apni campaign khud complete nahi kar sakte.',
            ]);
        }

        if ($task->isSoldOut()) {
            throw ValidationException::withMessages([
                'task' => 'Yeh campaign ki required quantity poori ho chuki hai.',
            ]);
        }

        if ($this->hasCompletedEver($user, $task)) {
            throw ValidationException::withMessages([
                'task' => 'Aap yeh campaign task pehle complete kar chuke hain.',
            ]);
        }
    }
}
