<?php

namespace App\Http\Controllers\Earning;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\EarningTask;
use App\Models\User;
use App\Services\AnnouncementNotificationService;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        TaskService $tasks,
        AnnouncementNotificationService $announcementNotifications,
    ): View {
        /** @var User $user */
        $user = $request->user();

        $activeTasks = EarningTask::query()
            ->system()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $communityTasks = EarningTask::query()
            ->liveCampaigns()
            ->with('owner')
            ->orderByDesc('approved_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $completedIds = $tasks->completedTaskIdsToday($user);
        $completedCampaignIds = $tasks->completedCampaignIds($user);

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
            'communityTasks' => $communityTasks,
            'completedIds' => $completedIds,
            'completedCampaignIds' => $completedCampaignIds,
            'completedTodayCount' => $completedIds->intersect($activeTasks->pluck('id'))->count(),
            'remainingTodayCount' => $tasks->remainingTodayCount($user),
            'nextResetAt' => $tasks->nextResetAt(),
            'announcements' => $announcements,
            'unreadAnnouncementIds' => $announcementNotifications->unreadAnnouncementIds($user),
            'recentAttempts' => $recentAttempts,
            'recentTransactions' => $recentTransactions,
            'myCampaignsCount' => $user->liveCampaigns()->count(),
            'totalCampaignSpend' => $user->totalCampaignSpend(),
            'totalDeposited' => $user->totalDeposited(),
        ]);
    }
}
