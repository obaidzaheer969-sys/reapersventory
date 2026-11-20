<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\PersonalAccessToken;

/** @mixin PersonalAccessToken */
class TokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<int, string> $abilities */
        $abilities = $this->abilities ?? [];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'user_id' => $this->tokenable_id,
            'user_name' => $this->tokenable?->name,
            'user_email' => $this->tokenable?->email,
            'abilities' => $abilities,
            'last_used_at' => $this->last_used_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
