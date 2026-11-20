<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_manage_api_tokens_from_filament(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $earner = User::factory()->earner()->create();
        $earner->createToken('Android phone');

        $this->actingAs($admin)
            ->get('/admin/api-tokens')
            ->assertOk()
            ->assertSee('Android phone');
    }

    public function test_non_admin_cannot_open_api_token_resource(): void
    {
        $user = User::factory()->earner()->create();

        $this->actingAs($user)
            ->get('/admin/api-tokens')
            ->assertForbidden();
    }
}
