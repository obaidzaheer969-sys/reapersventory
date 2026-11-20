<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TaskAttemptStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CompleteTaskRequest;
use App\Http\Requests\Api\V1\ProgressTaskRequest;
use App\Http\Requests\Api\V1\StartTaskRequest;
use App\Http\Resources\Api\V1\CampaignResource;
use App\Models\EarningTask;
use App\Models\TaskAttempt;
use App\Models\User;
use App\Services\CampaignService;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CampaignController extends Controller
{
    public function __construct(
        private readonly TaskService $tasks,
        private readonly CampaignService $campaigns,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $campaigns = EarningTask::query()
            ->liveCampaigns()
            ->where('user_id', '!=', $user->id)
            ->orderByDesc('approved_at')
            ->orderByDesc('id')
            ->paginate(12);

        $feeVisible = $this->campaigns->isAdminFeeVisible();

        foreach ($campaigns as $campaign) {
            $this->decorateCampaign($campaign, $feeVisible);
        }

        return CampaignResource::collection($campaigns)->response();
    }

    public function start(StartTaskRequest $request, EarningTask $campaign): CampaignResource
    {
        abort_unless($campaign->isCampaign(), 404);

        /** @var User $user */
        $user = $request->user();

        abort_unless($campaign->user_id !== $user->id && $campaign->isLiveCampaign(), 404);

        $attempt = $this->tasks->startAttempt($user, $campaign, $request->ip());
        $attempt = $this->applyProof($request, $user, $campaign, $attempt);

        return $this->attemptResponse($campaign, $attempt);
    }

    public function opened(Request $request, EarningTask $campaign): JsonResponse
    {
        abort_unless($campaign->isCampaign(), 404);

        /** @var User $user */
        $user = $request->user();

        abort_unless($campaign->user_id !== $user->id && $campaign->isLiveCampaign(), 404);

        $attempt = $this->currentAttempt($user, $campaign);

        abort_unless($attempt !== null, 404, 'Task attempt not found.');

        $attempt = $this->tasks->markRealPageOpened($user, $attempt);

        if (! $campaign->hasEmbeddedPlayer()) {
            $attempt = $this->tasks->resumeTimer($user, $attempt);
        }

        return response()->json([
            'ok' => true,
            'timer_paused' => $attempt->isTimerPaused(),
            'timer_remaining_seconds' => $attempt->remainingSeconds(),
        ]);
    }

    public function progress(ProgressTaskRequest $request, EarningTask $campaign): JsonResponse
    {
        abort_unless($campaign->isCampaign(), 404);

        /** @var User $user */
        $user = $request->user();

        abort_unless($campaign->user_id !== $user->id && $campaign->isLiveCampaign(), 404);

        $attempt = $this->currentAttempt($user, $campaign);

        abort_unless($attempt !== null, 404, 'Task attempt not found.');

        if (! $campaign->hasEmbeddedPlayer()) {
            throw ValidationException::withMessages([
                'task' => 'This task does not support an embedded player.',
            ]);
        }

        $playing = (bool) $request->boolean('playing');

        $attempt = $playing
            ? $this->tasks->resumeTimer($user, $attempt)
            : $this->tasks->pauseTimer($user, $attempt);

        $attempt = $this->tasks->recordEmbedHeartbeat($user, $attempt, $playing);

        $required = (int) $campaign->duration_seconds;
        $watched = (int) $attempt->watched_seconds;

        return response()->json([
            'ok' => true,
            'watched_seconds' => $watched,
            'required_seconds' => $required,
            'remaining_watch_seconds' => max(0, $required - $watched),
            'timer_remaining_seconds' => $attempt->remainingSeconds(),
            'timer_paused' => $attempt->isTimerPaused(),
            'can_claim' => $attempt->canClaim($campaign),
        ]);
    }

    public function complete(CompleteTaskRequest $request, EarningTask $campaign): CampaignResource
    {
        abort_unless($campaign->isCampaign(), 404);

        /** @var User $user */
        $user = $request->user();

        $attempt = TaskAttempt::query()
            ->where('user_id', $user->id)
            ->where('earning_task_id', $campaign->id)
            ->where('status', TaskAttemptStatus::InProgress->value)
            ->latest('id')
            ->firstOrFail();

        $attempt = $this->applyProof($request, $user, $campaign, $attempt);
        $attempt = $this->tasks->completeAttempt($user, $attempt);

        return $this->attemptResponse($campaign, $attempt);
    }

    private function applyProof(Request $request, User $user, EarningTask $campaign, TaskAttempt $attempt): TaskAttempt
    {
        if ($campaign->hasEmbeddedPlayer()) {
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

    private function attemptResponse(EarningTask $campaign, TaskAttempt $attempt): CampaignResource
    {
        $this->decorateCampaign($campaign, $this->campaigns->isAdminFeeVisible());

        $campaign->setAttribute('api_attempt', [
            'id' => $attempt->id,
            'status' => $attempt->status?->value,
            'remaining_seconds' => $attempt->remainingSeconds(),
            'timer_paused' => $attempt->isTimerPaused(),
            'can_claim' => $attempt->canClaim($campaign),
            'opened' => $attempt->hasOpenedRealPage(),
            'watched_seconds' => (int) $attempt->watched_seconds,
        ]);

        return new CampaignResource($campaign);
    }

    private function currentAttempt(User $user, EarningTask $campaign): ?TaskAttempt
    {
        return TaskAttempt::query()
            ->where('user_id', $user->id)
            ->where('earning_task_id', $campaign->id)
            ->where('status', TaskAttemptStatus::InProgress->value)
            ->latest('id')
            ->first();
    }

    private function decorateCampaign(EarningTask $campaign, bool $feeVisible): void
    {
        $campaign->setAttribute(
            'api_admin_fee',
            $feeVisible ? $campaign->feePerAction() : 0.0,
        );
    }
}
