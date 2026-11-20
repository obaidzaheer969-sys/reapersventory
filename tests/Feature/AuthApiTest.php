<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_through_api(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'first_name' => 'Test',
            'last_name' => 'Earner',
            'email' => 'test.earner@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => true,
            'device_name' => 'Android Test',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'test.earner@example.com')
            ->assertJsonPath('user.role', UserRole::Earner->value)
            ->assertJsonStructure(['token', 'token_type', 'user']);

        $this->assertDatabaseHas('users', [
            'email' => 'test.earner@example.com',
            'role' => UserRole::Earner->value,
            'is_active' => true,
        ]);
    }

    public function test_user_can_login_and_access_protected_api(): void
    {
        $user = User::factory()->earner()->create([
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $login = $this->postJson('/api/v1/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
            'device_name' => 'iPhone Test',
        ]);

        $login->assertOk()->assertJsonStructure(['token', 'token_type', 'user']);

        $this->withToken($login->json('token'))
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    }

    public function test_invalid_login_credentials_are_rejected(): void
    {
        User::factory()->earner()->create([
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'login@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_protected_api_requires_authentication(): void
    {
        $this->getJson('/api/v1/user')->assertUnauthorized();
    }

    public function test_user_can_logout_and_token_is_revoked(): void
    {
        $user = User::factory()->earner()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        auth()->forgetGuards();

        $this->withToken($token)->getJson('/api/v1/user')->assertUnauthorized();
    }

    public function test_deactivated_user_cannot_use_protected_api(): void
    {
        $user = User::factory()->earner()->create(['is_active' => false]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user')
            ->assertForbidden()
            ->assertJsonPath('message', 'Your account has been deactivated.');
    }
}
