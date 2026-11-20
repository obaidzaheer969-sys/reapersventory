<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TaskAttemptStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CompleteTaskRequest;
use App\Http\Requests\Api\V1\ProgressTaskRequest;
use App\Http\Requests\Api\V1\StartTaskRequest;
use App\Http\Resources\Api\V1\TaskResource;
use App\Models\EarningTask;
use App\Models\TaskAttempt;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $tasks,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $tasks = EarningTask::query()
            ->system()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $inProgress = $this->tasks->inProgressAttempts($user);
        $completedToday = $this->tasks->completedTaskIdsToday($user);

        foreach ($tasks as $task) {
            $this->decorateTask($task, $inProgress->get($task->id), $completedToday->contains($task->id));
        }

        return TaskResource::collection($tasks)
            ->additional([
                'meta' => [
                    'next_reset_at' => $this->tasks->nextResetAt()->toISOString(),
                    'completed_today' => $completedToday->count(),
                ],
            ])
            ->response();
    }

    public function start(StartTaskRequest $request, EarningTask $task): TaskResource
    {
        abort_unless(! $task->isCampaign(), 404);

        /** @var User $user */
        $user = $request->user();

        $attempt = $this->tasks->startAttempt($user, $task, $request->ip());
        $attempt = $this->applyProof($request, $user, $task, $attempt);

        return $this->attemptResponse($task, $attempt);
    }

    public function opened(Request $request, EarningTask $task): JsonResponse
    {
        abort_unless(! $task->isCampaign(), 404);

        /** @var User $user */
        $user = $request->user();

        $attempt = $this->currentAttempt($user, $task);

        abort_unless($attempt !== null, 404, 'Task attempt not found.');

        $attempt = $this->tasks->markRealPageOpened($user, $attempt);

        if (! $task->hasEmbeddedPlayer()) {
            $attempt = $this->tasks->resumeTimer($user, $attempt);
        }

        return response()->json([
            'ok' => true,
            'timer_paused' => $attempt->isTimerPaused(),
            'timer_remaining_seconds' => $attempt->remainingSeconds(),
        ]);
    }

    public function progress(ProgressTaskRequest $request, EarningTask $task): JsonResponse
    {
        abort_unless(! $task->isCampaign(), 404);

        /** @var User $user */
        $user = $request->user();

        $attempt = $this->currentAttempt($user, $task);

        abort_unless($attempt !== null, 404, 'Task attempt not found.');

        if (! $task->hasEmbeddedPlayer()) {
            throw ValidationException::withMessages([
                'task' => 'This task does not support an embedded player.',
            ]);
        }

        $playing = (bool) $request->boolean('playing');

        $attempt = $playing
            ? $this->tasks->resumeTimer($user, $attempt)
            : $this->tasks->pauseTimer($user, $attempt);

        $attempt = $this->tasks->recordEmbedHeartbeat($user, $attempt, $playing);

        $required = (int) $task->duration_seconds;
        $watched = (int) $attempt->watched_seconds;

        return response()->json([
            'ok' => true,
            'watched_seconds' => $watched,
            'required_seconds' => $required,
            'remaining_watch_seconds' => max(0, $required - $watched),
            'timer_remaining_seconds' => $attempt->remainingSeconds(),
            'timer_paused' => $attempt->isTimerPaused(),
            'can_claim' => $attempt->canClaim($task),
        ]);
    }

    public function complete(CompleteTaskRequest $request, EarningTask $task): TaskResource
    {
        abort_unless(! $task->isCampaign(), 404);

        /** @var User $user */
        $user = $request->user();

        $attempt = TaskAttempt::query()
            ->where('user_id', $user->id)
            ->where('earning_task_id', $task->id)
            ->where('status', TaskAttemptStatus::InProgress->value)
            ->latest('id')
            ->firstOrFail();

        $attempt = $this->applyProof($request, $user, $task, $attempt);
        $attempt = $this->tasks->completeAttempt($user, $attempt);

        return $this->attemptResponse($task, $attempt);
    }

    private function applyProof(Request $request, User $user, EarningTask $task, TaskAttempt $attempt): TaskAttempt
    {
        if ($task->hasEmbeddedPlayer()) {
            $playing = (bool) $request->boolean('playing');

            $attempt = $playing
                ? $this->tasks->resumeTimer($user, $attempt)
                : $this->tasks->pauseTimer($user, $attempt);

            return $this->tasks->recordEmbedHeartbeat($user, $attempt, $playing);
        }

        if ($request->boolean('opened')) {
            $attempt = $this->tasks->markRealPageOpened($user, $attempt);

            return $this->tasks->resumeTimer($user, $attempt);
        }

        return $attempt;
    }

    private function attemptResponse(EarningTask $task, TaskAttempt $attempt): TaskResource
    {
        $completedToday = $this->tasks->completedTaskIdsToday($attempt->user);
        $this->decorateTask($task, $attempt, $completedToday->contains($task->id));

        $task->setAttribute('api_attempt', [
            'id' => $attempt->id,
            'status' => $attempt->status?->value,
            'remaining_seconds' => $attempt->remainingSeconds(),
            'timer_paused' => $attempt->isTimerPaused(),
            'can_claim' => $attempt->canClaim($task),
            'opened' => $attempt->hasOpenedRealPage(),
            'watched_seconds' => (int) $attempt->watched_seconds,
        ]);

        return new TaskResource($task);
    }

    private function currentAttempt(User $user, EarningTask $task): ?TaskAttempt
    {
        return TaskAttempt::query()
            ->where('user_id', $user->id)
            ->where('earning_task_id', $task->id)
            ->where('status', TaskAttemptStatus::InProgress->value)
            ->latest('id')
            ->first();
    }

    private function decorateTask(EarningTask $task, ?TaskAttempt $attempt, bool $completedToday): void
    {
        $status = match (true) {
            $completedToday => 'completed',
            $attempt !== null => 'in_progress',
            ! $task->is_active => 'unavailable',
            default => 'available',
        };

        $task->setAttribute('api_status', $status);
        $task->setAttribute('api_remaining_seconds', $attempt?->remainingSeconds() ?? $task->duration_seconds);
        $task->setAttribute('api_can_start', ! $completedToday && $task->is_active);
    }
}
