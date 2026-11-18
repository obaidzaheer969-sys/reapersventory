<?php

namespace App\Http\Controllers\Earning;

use App\Enums\TaskAttemptStatus;
use App\Http\Controllers\Controller;
use App\Models\EarningTask;
use App\Models\TaskAttempt;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $tasks,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $tasks = EarningTask::query()
            ->system()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $campaigns = EarningTask::query()
            ->liveCampaigns()
            ->with('owner')
            ->where('user_id', '!=', $user->id)
            ->orderByDesc('approved_at')
            ->orderByDesc('id')
            ->limit(12)
            ->get();

        $inProgress = $this->tasks->inProgressAttempts($user);

        return view('earning.tasks.index', [
            'tasks' => $tasks,
            'campaigns' => $campaigns,
            'completedIds' => $this->tasks->completedTaskIdsToday($user),
            'completedCampaignIds' => $this->tasks->completedCampaignIds($user),
            'inProgress' => $inProgress,
            'nextResetAt' => $this->tasks->nextResetAt(),
            'walletBalance' => $user->wallet_balance,
        ]);
    }

    public function start(Request $request, EarningTask $task): RedirectResponse
    {
        $attempt = $this->tasks->startAttempt($request->user(), $task, $request->ip());

        $message = $task->hasEmbeddedPlayer()
            ? 'Task started. Play the video on this page — claim the reward after the required watch time and timer finish.'
            : 'Task started. Click "Open real page" — the real page opens in a new tab and the timer starts only then. Closing this task tab before the timer finishes will fail the task.';

        return redirect()
            ->route('tasks.run', $task)
            ->with('status', $message);
    }

    public function run(Request $request, EarningTask $task): View|RedirectResponse
    {
        $user = $request->user();

        $attempt = $this->currentAttempt($user, $task);

        if (! $attempt) {
            return redirect()
                ->route('tasks.index')
                ->withErrors(['task' => 'Start the task first.']);
        }

        $embedUrl = $task->embedUrl($request->getSchemeAndHttpHost());

        $isEmbedded = $embedUrl !== null;

        return view('earning.tasks.run', [
            'task' => $task,
            'attempt' => $attempt,
            'remainingSeconds' => $attempt->remainingSeconds(),
            'realPageOpened' => $attempt->hasOpenedRealPage(),
            'embedded' => $isEmbedded,
            'embedUrl' => $embedUrl,
            'embedPlatform' => $task->embedPlatform(),
            'embedPlayed' => $attempt->hasStartedEmbeddedPlayback(),
            'timerPaused' => $attempt->isTimerPaused(),
            'watchedSeconds' => $isEmbedded ? (int) $attempt->watched_seconds : 0,
            'requiredSeconds' => $isEmbedded ? (int) $task->duration_seconds : 0,
            'expectedReward' => $task->reward,
        ]);
    }

    public function progress(Request $request, EarningTask $task): JsonResponse
    {
        $attempt = $this->currentAttempt($request->user(), $task);

        if (! $attempt) {
            return response()->json(['ok' => false, 'message' => 'Task attempt not found.'], 404);
        }

        $validated = $request->validate([
            'playing' => ['required', 'boolean'],
        ]);

        $playing = (bool) $validated['playing'];

        if ($task->hasEmbeddedPlayer()) {
            $attempt = $playing
                ? $this->tasks->resumeTimer($request->user(), $attempt)
                : $this->tasks->pauseTimer($request->user(), $attempt);
        }

        $attempt = $this->tasks->recordEmbedHeartbeat(
            $request->user(),
            $attempt,
            $playing,
        );

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

    public function opened(Request $request, EarningTask $task): JsonResponse
    {
        $attempt = $this->currentAttempt($request->user(), $task);

        if (! $attempt) {
            return response()->json(['ok' => false, 'message' => 'Task attempt not found.'], 404);
        }

        $attempt = $this->tasks->markRealPageOpened($request->user(), $attempt);

        // Embedded players start the timer from their first playback heartbeat.
        // Every other task starts counting only once the real page is reached.
        if (! $task->hasEmbeddedPlayer()) {
            $attempt = $this->tasks->resumeTimer($request->user(), $attempt);
        }

        return response()->json([
            'ok' => true,
            'timer_paused' => $attempt->isTimerPaused(),
            'timer_remaining_seconds' => $attempt->remainingSeconds(),
        ]);
    }

    public function fail(Request $request, EarningTask $task): JsonResponse
    {
        $attempt = $this->currentAttempt($request->user(), $task);

        if (! $attempt) {
            return response()->json(['ok' => false, 'message' => 'Task attempt not found.'], 404);
        }

        $failed = $this->tasks->failAttempt($request->user(), $attempt);

        return response()->json(['ok' => true, 'failed' => $failed->isFailed()]);
    }

    public function complete(Request $request, EarningTask $task): RedirectResponse
    {
        $user = $request->user();

        $attempt = $this->currentAttempt($user, $task);

        if (! $attempt) {
            return redirect()
                ->route('tasks.index')
                ->withErrors(['task' => 'Task attempt not found. Start again.']);
        }

        $this->tasks->completeAttempt($user, $attempt);

        return redirect()
            ->route('tasks.index')
            ->with('status', 'Task complete! Coins '.number_format((float) $task->reward, 2).' has been added to your wallet.');
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
}
