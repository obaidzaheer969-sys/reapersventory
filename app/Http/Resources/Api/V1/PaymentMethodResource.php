<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PaymentMethod */
class PaymentMethodResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'instructions' => $this->instructions,
            'account_name' => $this->account_name,
            'account_number' => $this->account_number,
            'additional_info' => $this->additional_info,
        ];
    }
}
