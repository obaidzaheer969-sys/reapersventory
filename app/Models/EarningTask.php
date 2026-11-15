<?php

namespace App\Models;

use App\Enums\TaskType;
use Database\Factories\EarningTaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'task_type',
    'target_url',
    'duration_seconds',
    'reward',
    'instructions',
    'is_active',
    'sort_order',
])]
class EarningTask extends Model
{
    /** @use HasFactory<EarningTaskFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'task_type' => TaskType::class,
            'duration_seconds' => 'integer',
            'reward' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(TaskAttempt::class);
    }

    /**
     * @param  Builder<EarningTask>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function platform(): string
    {
        return $this->task_type->platform();
    }

    public function actionLabel(): string
    {
        return $this->task_type->actionText();
    }

    public function durationLabel(): string
    {
        return $this->duration_seconds.' sec';
    }

    /**
     * Return a URL that can be shown inside the locked task tab.
     *
     * YouTube watch/short links are converted to their embeddable player URL;
     * everything else is returned unchanged and rendered as-is in the frame.
     */
    public function embedUrl(): string
    {
        $url = (string) $this->target_url;

        $patterns = [
            '~^https?://(?:www\.|m\.)?youtube\.com/watch\?(?:[^#]*&)?v=([A-Za-z0-9_-]{6,})~i',
            '~^https?://(?:www\.)?youtube\.com/shorts/([A-Za-z0-9_-]{6,})~i',
            '~^https?://(?:www\.)?youtu\.be/([A-Za-z0-9_-]{6,})~i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches) === 1) {
                return 'https://www.youtube.com/embed/'.$matches[1].'?rel=0';
            }
        }

        return $url;
    }
}
