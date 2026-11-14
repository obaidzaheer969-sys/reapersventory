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

    public function test_new_users_register_as_earners(): void
    {
        $response = $this->post(route('register'), [
            'first_name' => 'Ali',
            'last_name' => 'Khan',
            'email' => 'ali@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'ali@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('Ali Khan', $user->name);
        $this->assertSame(UserRole::Earner, $user->role);
        $this->assertNull($user->business_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame(0.0, (float) $user->wallet_balance);
    }

    public function test_registration_requires_terms_acceptance(): void
    {
        $this->post(route('register'), [
            'first_name' => 'Ali',
            'last_name' => 'Khan',
            'email' => 'ali@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertSessionHasErrors('terms');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'ali@example.com']);
    }

    public function test_public_registration_never_grants_super_admin_even_if_role_is_injected(): void
    {
        $response = $this->post(route('register'), [
            'first_name' => 'Hacker',
            'last_name' => 'Ali',
            'email' => 'hacker@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
            'role' => UserRole::SuperAdmin->value,
            'business_id' => 999,
        ]);

        $response->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'hacker@example.com')->firstOrFail();
        $this->assertSame(UserRole::Earner, $user->role);
        $this->assertNull($user->business_id);
        $this->assertSame(0, User::query()->where('role', UserRole::SuperAdmin)->count());
    }

    public function test_registration_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post(route('register'), [
            'first_name' => 'Some',
            'last_name' => 'One',
            'email' => 'taken@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ])->assertSessionHasErrors('email');
    }
}
