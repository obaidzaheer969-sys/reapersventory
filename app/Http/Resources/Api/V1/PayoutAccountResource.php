<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PayoutAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PayoutAccount */
class PayoutAccountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider?->value,
            'provider_label' => $this->provider?->label(),
            'account_name' => $this->account_name,
            'account_number' => $this->account_number,
            'is_default' => (bool) $this->is_default,
        ];
    }
}
