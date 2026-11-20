<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AdService;
use App\Services\CampaignService;
use App\Services\PayoutService;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaigns,
        private readonly PayoutService $payouts,
        private readonly AdService $ads,
    ) {}

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'campaign_admin_fee_visible' => $this->campaigns->isAdminFeeVisible(),
            'withdrawals_enabled' => $this->payouts->areEnabled(),
            'ads_enabled' => (bool) SystemSetting::getValue('ads_enabled', false),
            'site_approved' => $this->ads->isApproved(),
        ]);
    }
}
