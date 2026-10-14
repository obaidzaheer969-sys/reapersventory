<?php

namespace Tests\Feature\Subscription;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_owner_cannot_access_filament_admin_panel(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_super_admin_can_access_filament_admin_panel(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }

    public function test_pricing_page_loads_seeded_style_plans(): void
    {
        \App\Models\Plan::factory()->create([
            'name' => 'Starter',
            'is_active' => true,
        ]);

        $this->get(route('pricing'))
            ->assertOk()
            ->assertSee('Starter');
    }
}
