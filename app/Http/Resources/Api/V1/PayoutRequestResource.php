<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PayoutRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PayoutRequest */
class PayoutRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'fee' => $this->feeAmount(),
            'net_amount' => (float) $this->amount,
            'status' => $this->status?->value,
            'provider' => $this->provider?->value,
            'account_number' => $this->account_number,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
