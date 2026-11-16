<?php

namespace App\Http\Controllers\Earning;

use App\Enums\CampaignStatus;
use App\Enums\TaskAttemptStatus;
use App\Enums\TaskType;
use App\Http\Controllers\Controller;
use App\Models\EarningTask;
use App\Services\CampaignService;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaigns,
        private readonly TaskService $tasks,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('earning.campaigns.index', [
            'campaigns' => EarningTask::query()
                ->liveCampaigns()
                ->with('owner')
                ->orderByDesc('approved_at')
                ->orderByDesc('id')
                ->paginate(12),
            'completedIds' => $this->tasks->completedCampaignIds($user),
            'inProgress' => $this->tasks->inProgressAttempts($user),
            'walletBalance' => $user->wallet_balance,
        ]);
    }

    public function mine(Request $request): View
    {
        $user = $request->user();

        return view('earning.campaigns.mine', [
            'campaigns' => $user->campaigns()->latest()->paginate(10),
            'walletBalance' => (float) $user->wallet_balance,
            'totalSpend' => $user->totalCampaignSpend(),
            'liveCount' => $user->campaigns()->liveCampaigns()->count(),
            'pendingCount' => $user->campaigns()->where('campaign_status', 'pending')->count(),
            'deliveredTotal' => (int) $user->campaigns()->sum('completed_quantity'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('earning.campaigns.create', [
            'types' => TaskType::campaignOptions(),
            'walletBalance' => (float) $request->user()->wallet_balance,
            'campaignConfig' => config('earning.campaign'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $campaignConfig = config('earning.campaign');

        $data = $request->validate([
            'task_type' => ['required', Rule::in(array_keys(TaskType::campaignOptions()))],
            'title' => ['required', 'string', 'max:255'],
            'target_url' => ['required', 'url', 'max:2048'],
            'target_quantity' => [
                'required',
                'integer',
                'min:'.(int) $campaignConfig['min_quantity'],
                'max:'.(int) $campaignConfig['max_quantity'],
            ],
            'duration_seconds' => ['nullable', 'integer'],
            'instructions' => ['nullable', 'string', 'max:1000'],
        ]);

        $campaign = $this->campaigns->create($request->user(), $data);

        return redirect()
            ->route('campaigns.mine')
            ->with('status', 'Campaign #'.$campaign->id.' admin approval ke liye bhej di gayi hai. Payment wallet se hold ho gayi hai.');
    }

    public function show(Request $request, EarningTask $campaign): View
    {
        abort_unless($campaign->isCampaign(), 404);

        $user = $request->user();
        $isOwner = $campaign->user_id === $user->id;

        abort_unless($isOwner || $campaign->campaign_status === CampaignStatus::Active, 404);

        return view('earning.campaigns.show', [
            'campaign' => $campaign->loadMissing('owner'),
            'isOwner' => $isOwner,
            'attempts' => $campaign->attempts()
                ->with('user')
                ->where('status', TaskAttemptStatus::Completed->value)
                ->latest('completed_at')
                ->paginate(20),
            'alreadyCompleted' => $this->tasks->hasCompletedEver($user, $campaign),
            'inProgress' => $this->tasks->inProgressAttempts($user)->get($campaign->id),
        ]);
    }

    public function cancel(Request $request, EarningTask $campaign): RedirectResponse
    {
        abort_unless($campaign->isCampaign(), 404);

        $campaign = $this->campaigns->cancel($request->user(), $campaign);

        return back()->with('status', 'Campaign cancel ho gayi. PKR '
            .number_format($campaign->budgetRemaining(), 2)
            .' ka unused budget wallet me wapas aa gaya hai.');
    }
}
