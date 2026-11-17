<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\NewAnnouncementNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Throwable;

class AnnouncementNotificationService
{
    public function publish(Announcement $announcement): void
    {
        if (! $announcement->isVisibleNow()) {
            return;
        }

        $notification = new NewAnnouncementNotification($announcement);

        User::query()
            ->where('is_active', true)
            ->where('role', '!=', UserRole::SuperAdmin)
            ->when($announcement->created_by, function (Builder $query) use ($announcement): void {
                $query->whereKeyNot($announcement->created_by);
            })
            ->each(function (User $user) use ($notification): void {
                $this->safeNotify($user, $notification);
            });
    }

    /**
     * @return Collection<int, int>
     */
    public function unreadAnnouncementIds(User $user): Collection
    {
        return $user->notifications()
            ->where('type', NewAnnouncementNotification::class)
            ->whereNull('read_at')
            ->get()
            ->pluck('data.announcement_id')
            ->filter(fn (mixed $id): bool => filled($id))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();
    }

    public function markRead(User $user): void
    {
        $user->notifications()
            ->where('type', NewAnnouncementNotification::class)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    private function safeNotify(User $user, NewAnnouncementNotification $notification): void
    {
        try {
            $user->notify($notification);
        } catch (Throwable) {
            // A failed notification must not prevent the announcement from publishing.
        }
    }
}
