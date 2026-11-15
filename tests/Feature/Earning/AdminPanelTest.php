<?php

namespace Tests\Feature\Earning;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_only_the_earning_system(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('Earners');
        $response->assertSee('Pending payouts');
        $response->assertDontSee('Businesses');
        $response->assertDontSee('Subscriptions');
        $response->assertDontSee('Payment methods');
    }
}
