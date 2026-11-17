<?php

namespace App\Models;

use App\Services\AnnouncementNotificationService;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'title',
    'body',
    'is_active',
    'is_pinned',
    'published_at',
    'expires_at',
    'created_by',
])]
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::created(function (Announcement $announcement): void {
            if ($announcement->isVisibleNow()) {
                app(AnnouncementNotificationService::class)->publish($announcement);
            }
        });

        static::updated(function (Announcement $announcement): void {
            $visibilityChanged = $announcement->wasChanged(['is_active', 'published_at', 'expires_at']);

            if ($visibilityChanged && $announcement->isVisibleNow()) {
                app(AnnouncementNotificationService::class)->publish($announcement);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_pinned' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isVisibleNow(): bool
    {
        return $this->is_active
            && ($this->published_at === null || $this->published_at->lessThanOrEqualTo(now()))
            && ($this->expires_at === null || $this->expires_at->greaterThan(now()));
    }

    /**
     * @param  Builder<Announcement>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /**
     * @param  Builder<Announcement>  $query
     */
    public function scopeLatestFirst(Builder $query): void
    {
        $query->orderByDesc('is_pinned')->latest('published_at')->latest('id');
    }
}
