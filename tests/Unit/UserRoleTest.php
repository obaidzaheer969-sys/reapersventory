<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_helpers(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->superAdmin()->create();

        $this->assertTrue($owner->isBusinessOwner());
        $this->assertFalse($owner->isSuperAdmin());
        $this->assertTrue($admin->isSuperAdmin());
        $this->assertFalse($admin->isBusinessOwner());
        $this->assertNull($admin->business_id);
        $this->assertSame(UserRole::SuperAdmin, $admin->role);
    }
}
