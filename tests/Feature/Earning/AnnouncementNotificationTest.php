<?php

namespace Tests\Feature\Earning;

use App\Models\Announcement;
use App\Models\User;
use App\Notifications\NewAnnouncementNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AnnouncementNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishing_visible_announcement_notifies_active_non_admin_users(): void
    {
        $earner = User::factory()->earner()->create();
        $owner = User::factory()->businessOwner()->create();
        $inactiveEarner = User::factory()->earner()->inactive()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        Notification::fake();

        $announcement = Announcement::factory()->create([
            'title' => 'Double rewards this weekend',
            'created_by' => $superAdmin->id,
        ]);

        Notification::assertSentTo(
            $earner,
            NewAnnouncementNotification::class,
            fn (NewAnnouncementNotification $notification): bool => data_get($notification->toArray($earner), 'announcement_id') === $announcement->id,
        );
        Notification::assertSentTo($owner, NewAnnouncementNotification::class);
        Notification::assertNotSentTo($inactiveEarner, NewAnnouncementNotification::class);
        Notification::assertNotSentTo($superAdmin, NewAnnouncementNotification::class);
    }

    public function test_hidden_announcement_does_not_notify_users(): void
    {
        User::factory()->earner()->create();

        Notification::fake();

        Announcement::factory()->inactive()->create(['title' => 'Hidden draft']);
        Announcement::factory()->expired()->create(['title' => 'Hidden expired']);
        Announcement::factory()->create([
            'title' => 'Hidden future',
            'published_at' => now()->addDay(),
        ]);

        Notification::assertNothingSent();
    }

    public function test_activating_announcement_notifies_users(): void
    {
        $earner = User::factory()->earner()->create();

        Notification::fake();

        $announcement = Announcement::factory()->inactive()->create(['title' => 'Payout window extended']);
        Notification::assertNotSentTo($earner, NewAnnouncementNotification::class);

        $announcement->update(['is_active' => true]);

        Notification::assertSentTo($earner, NewAnnouncementNotification::class);
    }

    public function test_new_announcement_appears_in_bell_and_notification_inbox(): void
    {
        $earner = User::factory()->earner()->create();

        $announcement = Announcement::factory()->create(['title' => 'Weekend bonus unlocked']);

        $this->actingAs($earner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas(
                'unreadAnnouncementIds',
                fn ($ids): bool => $ids->contains($announcement->id),
            )
            ->assertSee('New</span>', false);

        $this->actingAs($earner)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('New announcement')
            ->assertSee('Weekend bonus unlocked');

        $this->assertSame(1, $earner->unreadNotifications()->count());
    }

    public function test_opening_announcements_marks_notification_read_and_removes_new_badge(): void
    {
        $earner = User::factory()->earner()->create();

        $announcement = Announcement::factory()->create(['title' => 'New payout window']);

        $this->actingAs($earner)
            ->get(route('announcements.index'))
            ->assertOk()
            ->assertViewHas(
                'unreadAnnouncementIds',
                fn ($ids): bool => $ids->contains($announcement->id),
            )
            ->assertSee('New</span>', false);

        $this->assertSame(0, $earner->unreadNotifications()->count());

        $this->actingAs($earner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('New</span>', false);
    }
}
