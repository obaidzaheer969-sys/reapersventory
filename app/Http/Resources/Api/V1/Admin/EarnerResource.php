<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class EarnerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'wallet_balance' => (float) $this->wallet_balance,
            'total_earned' => (float) $this->total_earned,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
