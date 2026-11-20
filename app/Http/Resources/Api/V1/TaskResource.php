<?php

namespace App\Http\Resources\Api\V1;

use App\Models\EarningTask;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EarningTask */
class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->instructions,
            'type' => $this->task_type?->value,
            'reward' => (float) $this->reward,
            'duration_seconds' => (int) $this->duration_seconds,
            'status' => $this->api_status ?? 'available',
            'remaining_seconds' => (int) ($this->api_remaining_seconds ?? $this->duration_seconds),
            'can_start' => (bool) ($this->api_can_start ?? true),
            'proof_required' => ! $this->hasEmbeddedPlayer(),
            'url' => $this->target_url,
            'embedded' => (bool) $this->hasEmbeddedPlayer(),
            'embed_url' => $this->embedUrl(config('app.url')),
            'platform' => $this->embedPlatform(),
            'opened' => (bool) ($this->api_attempt['opened'] ?? false),
            'watched_seconds' => (int) ($this->api_attempt['watched_seconds'] ?? 0),
            'timer_paused' => (bool) ($this->api_attempt['timer_paused'] ?? false),
            'can_claim' => (bool) ($this->api_attempt['can_claim'] ?? false),
            'created_at' => $this->created_at?->toISOString(),
            'attempt' => $this->when(isset($this->api_attempt), fn (): array => $this->api_attempt),
        ];
    }
}
