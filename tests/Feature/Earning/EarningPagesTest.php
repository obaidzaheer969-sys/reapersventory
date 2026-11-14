<?php

namespace Tests\Feature\Earning;

use App\Models\Announcement;
use App\Models\EarningTask;
use App\Models\PayoutAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EarningPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_earner_can_view_all_earning_pages(): void
    {
        $user = User::factory()->earner()->create(['wallet_balance' => 750]);

        EarningTask::factory()->youtubeSubscribe()->create(['title' => 'Subscribe to EarnPK channel']);
        PayoutAccount::factory()->create(['user_id' => $user->id]);
        Announcement::factory()->create(['title' => 'Welcome to EarnPK']);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Welcome to EarnPK');
        $this->actingAs($user)->get(route('tasks.index'))->assertOk()->assertSee('Subscribe to EarnPK channel');
        $this->actingAs($user)->get(route('earnings.index'))->assertOk()->assertSee('Earnings history');
        $this->actingAs($user)->get(route('wallet.index'))->assertOk()->assertSee('Wallet &amp; payouts', false);
        $this->actingAs($user)->get(route('announcements.index'))->assertOk()->assertSee('Welcome to EarnPK');
        $this->actingAs($user)->get(route('notifications.index'))->assertOk()->assertSee('Notifications');
        $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee($user->email);
    }
}
