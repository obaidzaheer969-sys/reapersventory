<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\RejectCampaignRequest;
use App\Http\Resources\Api\V1\CampaignResource;
use App\Models\EarningTask;
use App\Services\CampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaigns,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::enum(CampaignStatus::class)],
            'search' => ['sometimes', 'string', 'max:127'],
        ]);

        $search = isset($validated['search']) ? trim((string) $validated['search']) : '';

        $campaigns = EarningTask::query()
            ->campaigns()
            ->with('owner:id,name,email')
            ->when(isset($validated['status']), fn ($query) => $query
                ->where('campaign_status', $validated['status']))
            ->when($search !== '', fn ($query) => $query
                ->where('title', 'like', '%'.$search.'%'))
            ->latest()
            ->paginate(15);

        foreach ($campaigns as $campaign) {
            $this->decorateCampaign($campaign);
        }

        return CampaignResource::collection($campaigns)->response();
    }

    public function approve(EarningTask $campaign): CampaignResource
    {
        abort_unless($campaign->isCampaign(), 404);

        $campaign = $this->campaigns->approve($campaign);
        $this->decorateCampaign($campaign);

        return new CampaignResource($campaign);
    }

    public function reject(RejectCampaignRequest $request, EarningTask $campaign): CampaignResource
    {
        abort_unless($campaign->isCampaign(), 404);

        $campaign = $this->campaigns->reject(
            $campaign,
            $request->string('admin_notes')->trim()->toString() ?: null,
        );
        $this->decorateCampaign($campaign);

        return new CampaignResource($campaign);
    }

    private function decorateCampaign(EarningTask $campaign): void
    {
        $campaign->setAttribute('api_admin_fee', $campaign->feePerAction());
    }
}
