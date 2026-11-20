<?php

namespace App\Http\Resources\Api\V1;

use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WalletTransaction */
class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $amount = (float) $this->amount;
        $fee = round((float) ($this->api_fee ?? 0), 2);

        return [
            'id' => $this->id,
            'type' => $this->type?->value,
            'amount' => $amount,
            'fee' => $fee,
            'net_amount' => round(max(0, $amount - $fee), 2),
            'description' => $this->description,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
