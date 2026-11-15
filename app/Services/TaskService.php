<?php

namespace App\Services;

use App\Enums\TaskAttemptStatus;
use App\Enums\WalletTransactionType;
use App\Models\EarningTask;
use App\Models\TaskAttempt;
use App\Models\User;
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
     * @return array{0: Carbon, 1: Carbon}
     */
    public function todayRangeUtc(): array
    {
        $start = Carbon::now($this->timezone())->startOfDay()->utc();
        $end = Carbon::now($this->timezone())->endOfDay()->utc();

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
     * @return Collection<int, int>
     */
    public function completedTaskIdsToday(User $user): Collection
    {
        [$start, $end] = $this->todayRangeUtc();

        return TaskAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', TaskAttemptStatus::Completed->value)
            ->whereBetween('completed_at', [$start, $end])
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
        $activeIds = EarningTask::query()->active()->pluck('id');

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

        if ($this->completedToday($user, $task)) {
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
        return DB::transaction(function () use ($user, $attempt) {
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
                    'task' => 'Task fail ho gaya tha — real page timer poora hone se pehle band hua. Task dobara start karein.',
                ]);
            }

            if (! $attempt->hasOpenedRealPage()) {
                throw ValidationException::withMessages([
                    'task' => 'Pehle task ka real page kholein — bina task dekhe reward nahi milega.',
                ]);
            }

            if (now()->lt($attempt->expires_at)) {
                throw ValidationException::withMessages([
                    'task' => 'Timer abhi poora nahi hua. '.$attempt->remainingSeconds().' seconds baaki hain.',
                ]);
            }

            $task = $attempt->task;

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

            return $attempt->refresh();
        });
    }
}
