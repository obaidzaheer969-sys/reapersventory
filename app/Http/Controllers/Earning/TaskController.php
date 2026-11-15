<?php

namespace App\Http\Controllers\Earning;

use App\Enums\TaskAttemptStatus;
use App\Http\Controllers\Controller;
use App\Models\EarningTask;
use App\Models\TaskAttempt;
use App\Services\TaskService;
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
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('earning.tasks.index', [
            'tasks' => $tasks,
            'completedIds' => $this->tasks->completedTaskIdsToday($user),
            'inProgress' => $this->tasks->inProgressAttempts($user),
            'nextResetAt' => $this->tasks->nextResetAt(),
            'walletBalance' => $user->wallet_balance,
        ]);
    }

    public function start(Request $request, EarningTask $task): RedirectResponse
    {
        $attempt = $this->tasks->startAttempt($request->user(), $task, $request->ip());

        return redirect()
            ->route('tasks.run', $task)
            ->with('status', 'Task shuru ho gaya. Naye tab me task ka link khul gaya hai — time poora hone tak us tab ko band na karein.');
    }

    public function run(Request $request, EarningTask $task): View|RedirectResponse
    {
        $user = $request->user();

        $attempt = TaskAttempt::query()
            ->where('user_id', $user->id)
            ->where('earning_task_id', $task->id)
            ->where('status', TaskAttemptStatus::InProgress->value)
            ->latest('id')
            ->first();

        if (! $attempt) {
            return redirect()
                ->route('tasks.index')
                ->withErrors(['task' => 'Pehle task start karein.']);
        }

        return view('earning.tasks.run', [
            'task' => $task,
            'attempt' => $attempt,
            'remainingSeconds' => $attempt->remainingSeconds(),
            'expectedReward' => $task->reward,
        ]);
    }

    public function complete(Request $request, EarningTask $task): RedirectResponse
    {
        $user = $request->user();

        $attempt = TaskAttempt::query()
            ->where('user_id', $user->id)
            ->where('earning_task_id', $task->id)
            ->where('status', TaskAttemptStatus::InProgress->value)
            ->latest('id')
            ->first();

        if (! $attempt) {
            return redirect()
                ->route('tasks.index')
                ->withErrors(['task' => 'Task attempt nahi mila. Dobara start karein.']);
        }

        $this->tasks->completeAttempt($user, $attempt);

        return redirect()
            ->route('tasks.index')
            ->with('status', 'Task complete! PKR '.number_format((float) $task->reward, 2).' aap ke wallet me add ho gaye.');
    }
}
