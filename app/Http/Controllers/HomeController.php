<?php

namespace App\Http\Controllers;

use App\Enums\PayoutStatus;
use App\Enums\TaskAttemptStatus;
use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\EarningTask;
use App\Models\PayoutRequest;
use App\Models\TaskAttempt;
use App\Models\User;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'earnersCount' => User::query()->where('role', UserRole::Earner->value)->count(),
            'tasksCompleted' => TaskAttempt::query()->where('status', TaskAttemptStatus::Completed->value)->count(),
            'totalPaid' => (float) PayoutRequest::query()->where('status', PayoutStatus::Paid->value)->sum('amount'),
            'activeTasks' => EarningTask::query()->active()->count(),
            'announcements' => Announcement::query()->visible()->latestFirst()->limit(3)->get(),
        ]);
    }
}
