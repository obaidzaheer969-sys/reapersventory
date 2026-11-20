<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Deposit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Deposit */
class DepositResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->credit_amount,
            'fee' => (float) $this->fee,
            'total' => (float) $this->amount,
            'credit_amount' => (float) $this->credit_amount,
            'currency' => $this->currency,
            'status' => $this->status?->value,
            'order_id' => $this->order_id,
            'payment_method_id' => $this->api_payment_method_id ?? null,
            'created_at' => $this->created_at?->toISOString(),
            'quote' => $this->when(isset($this->api_quote), fn (): array => $this->api_quote),
            'checkout' => $this->when(isset($this->api_checkout), fn (): array => $this->api_checkout),
        ];
    }
}
