<?php

namespace App\Models;

use App\Enums\PlanFeatureKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['plan_id', 'feature_key', 'limit_value', 'is_enabled'])]
class PlanFeature extends Model
{
    protected function casts(): array
    {
        return [
            'feature_key' => PlanFeatureKey::class,
            'limit_value' => 'integer',
            'is_enabled' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
