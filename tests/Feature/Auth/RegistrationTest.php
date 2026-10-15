<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get(route('register'))->assertOk();
    }

    public function test_new_users_can_register_as_business_owners_with_a_business(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Ali Khan',
            'email' => 'ali@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'business_name' => 'Ali Traders',
            'phone' => '03001234567',
        ]);

        $response->assertRedirect(route('business.dashboard'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'ali@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(UserRole::BusinessOwner, $user->role);
        $this->assertNotNull($user->business_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('businesses', [
            'id' => $user->business_id,
            'name' => 'Ali Traders',
            'email' => 'ali@example.com',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'business.registered',
            'business_id' => $user->business_id,
            'user_id' => $user->id,
        ]);
    }

    public function test_public_registration_never_grants_super_admin_even_if_role_is_injected(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'business_name' => 'Evil Corp',
            'role' => UserRole::SuperAdmin->value,
            'business_id' => 999,
        ]);

        $response->assertRedirect(route('business.dashboard'));

        $user = User::query()->where('email', 'hacker@example.com')->firstOrFail();
        $this->assertSame(UserRole::BusinessOwner, $user->role);
        $this->assertNotNull($user->business_id);
        $this->assertNotSame(999, $user->business_id);
        $this->assertSame(0, User::query()->where('role', UserRole::SuperAdmin)->count());
    }

    public function test_registration_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post(route('register'), [
            'name' => 'Someone',
            'email' => 'taken@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'business_name' => 'Another Biz',
        ])->assertSessionHasErrors('email');
    }
}
