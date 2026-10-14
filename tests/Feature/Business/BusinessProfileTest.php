<?php

namespace Tests\Feature\Business;

use App\Livewire\Business\EditProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BusinessProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_owner_can_view_profile_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('business.profile.edit'))
            ->assertOk()
            ->assertSeeLivewire(EditProfile::class);
    }

    public function test_owner_can_update_own_business_profile_via_livewire(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->set('name', 'Updated Traders')
            ->set('email', 'ops@updated.test')
            ->set('city', 'Lahore')
            ->set('currency', 'PKR')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('businesses', [
            'id' => $user->business_id,
            'name' => 'Updated Traders',
            'email' => 'ops@updated.test',
            'city' => 'Lahore',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'business.profile_updated',
            'business_id' => $user->business_id,
            'user_id' => $user->id,
        ]);
    }
}
