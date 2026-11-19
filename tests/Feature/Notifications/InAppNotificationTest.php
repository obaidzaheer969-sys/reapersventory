<?php

namespace Tests\Feature\Notifications;

use App\Notifications\WelcomeBusinessNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSubscribedBusiness;
use Tests\TestCase;

class InAppNotificationTest extends TestCase
{
    use CreatesSubscribedBusiness;
    use RefreshDatabase;

    public function test_owner_can_view_notifications_page(): void
    {
        $owner = $this->subscribedOwner();
        $owner->notify(new WelcomeBusinessNotification('Demo Biz'));

        $this->actingAs($owner)
            ->get(route('business.notifications.index'))
            ->assertOk()
            ->assertSee('Welcome to Reapers Growth')
            ->assertSee('Demo Biz');
    }

    public function test_owner_can_mark_notification_read(): void
    {
        $owner = $this->subscribedOwner();
        $owner->notify(new WelcomeBusinessNotification('Demo Biz'));
        $notification = $owner->notifications()->first();

        $this->actingAs($owner)
            ->get(route('business.notifications.read', $notification->id))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_nav_alerts_shows_unread_badge_count(): void
    {
        $owner = $this->subscribedOwner();
        $owner->notify(new WelcomeBusinessNotification('Demo Biz'));

        $response = $this->actingAs($owner)->get(route('business.dashboard'));

        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '/data-testid="nav-alerts-count"[^>]*>1<\/span>/',
            $response->getContent(),
        );
    }
}
