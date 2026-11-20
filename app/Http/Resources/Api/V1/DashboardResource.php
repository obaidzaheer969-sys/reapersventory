<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'wallet_balance' => (float) $this['wallet_balance'],
            'today_earned' => (float) $this['today_earned'],
            'total_earned' => (float) $this['total_earned'],
            'available_tasks' => (int) $this['available_tasks'],
            'active_campaigns' => (int) $this['active_campaigns'],
            'pending_withdrawals' => (int) $this['pending_withdrawals'],
            'announcements' => AnnouncementResource::collection($this['announcements']),
            'recent_transactions' => TransactionResource::collection($this['recent_transactions']),
        ];
    }
}
