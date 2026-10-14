<?php

namespace Tests\Feature\Business;

use App\Enums\SubscriptionStatus;
use App\Livewire\Business\UpdateBranding;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreatesSubscribedBusiness;
use Tests\TestCase;

class BusinessBrandingTest extends TestCase
{
    use CreatesSubscribedBusiness;
    use RefreshDatabase;

    public function test_paid_owner_can_save_brand_name_and_logo(): void
    {
        Storage::fake('public');

        $owner = $this->subscribedOwner();

        Livewire::actingAs($owner)
            ->test(UpdateBranding::class)
            ->set('brandName', 'Acme Traders')
            ->set('logo', UploadedFile::fake()->image('logo.png', 120, 80))
            ->call('save')
            ->assertHasNoErrors();

        $owner->business->refresh();

        $this->assertSame('Acme Traders', $owner->business->brand_name);
        $this->assertNotNull($owner->business->logo_path);
        Storage::disk('public')->assertExists($owner->business->logo_path);
    }

    public function test_trial_owner_cannot_save_branding(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $plan = Plan::factory()->create();

        Subscription::factory()->create([
            'business_id' => $owner->business_id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Trial,
            'ends_at' => now()->addDays(7),
            'trial_ends_at' => now()->addDays(7),
        ]);

        Livewire::actingAs($owner)
            ->test(UpdateBranding::class)
            ->set('brandName', 'Should Fail')
            ->call('save')
            ->assertHasErrors(['brandName']);

        $this->assertNull($owner->business->fresh()->brand_name);
    }

    public function test_dashboard_shows_paid_brand_name(): void
    {
        $owner = $this->subscribedOwner();
        $owner->business->update(['brand_name' => 'Paid Brand Co']);

        $this->actingAs($owner)
            ->get(route('business.dashboard'))
            ->assertOk()
            ->assertSee('Paid Brand Co');
    }
}
