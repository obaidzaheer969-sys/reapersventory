<?php

namespace App\Http\Controllers\Earning;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\EarningTask;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, TaskService $tasks): View
    {
        $user = $request->user();

        $activeTasks = EarningTask::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $completedIds = $tasks->completedTaskIdsToday($user);

        $announcements = Announcement::query()
            ->visible()
            ->latestFirst()
            ->limit(5)
            ->get();

        $recentAttempts = $user->completedTaskAttempts()
            ->with('task')
            ->latest('completed_at')
            ->limit(5)
            ->get();

        $recentTransactions = $user->walletTransactions()
            ->latest()
            ->limit(6)
            ->get();

        return view('earning.dashboard', [
            'user' => $user,
            'activeTasks' => $activeTasks,
            'completedIds' => $completedIds,
            'completedTodayCount' => $completedIds->intersect($activeTasks->pluck('id'))->count(),
            'remainingTodayCount' => $tasks->remainingTodayCount($user),
            'nextResetAt' => $tasks->nextResetAt(),
            'announcements' => $announcements,
            'recentAttempts' => $recentAttempts,
            'recentTransactions' => $recentTransactions,
        ]);
    }
}
