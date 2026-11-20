<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: UserRole}>
     */
    public static function nonEarnerRoles(): array
    {
        return [
            'business owner' => [UserRole::BusinessOwner],
            'super admin' => [UserRole::SuperAdmin],
        ];
    }

    #[DataProvider('nonEarnerRoles')]
    public function test_non_earner_roles_cannot_use_earner_endpoints(UserRole $role): void
    {
        $user = $role === UserRole::SuperAdmin
            ? User::factory()->superAdmin()->create()
            : User::factory()->businessOwner()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/dashboard')
            ->assertForbidden()
            ->assertJsonPath('message', 'This app is only available to earner accounts.');
    }

    public function test_inactive_earner_cannot_use_earner_endpoints(): void
    {
        $user = User::factory()->earner()->inactive()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/tasks')
            ->assertForbidden()
            ->assertJsonPath('message', 'Your account has been deactivated.');
    }

    public function test_authentication_is_required_for_earner_and_admin_endpoints(): void
    {
        $this->getJson('/api/v1/wallet')
            ->assertUnauthorized();

        $this->getJson('/api/v1/admin/settings')
            ->assertUnauthorized();
    }
}
