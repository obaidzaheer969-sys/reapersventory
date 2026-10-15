<?php

namespace Tests\Feature\Notifications;

use App\Notifications\WelcomeBusinessNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesSubscribedBusiness;
use Tests\TestCase;

class InAppNotificationTest extends TestCase
{
    use CreatesSubscribedBusiness;
    use RefreshDatabase;

    public function test_registration_sends_welcome_database_notification(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'name' => 'Sara Ali',
            'email' => 'sara@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'business_name' => 'Sara Shop',
        ])->assertRedirect(route('business.dashboard'));

        $user = User::query()->where('email', 'sara@example.com')->firstOrFail();
        Notification::assertSentTo($user, WelcomeBusinessNotification::class);
    }

    public function test_owner_can_view_notifications_page(): void
    {
        $owner = $this->subscribedOwner();
        $owner->notify(new WelcomeBusinessNotification('Demo Biz'));

        $this->actingAs($owner)
            ->get(route('business.notifications.index'))
            ->assertOk()
            ->assertSee('Welcome to ReapersVentory')
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
}
