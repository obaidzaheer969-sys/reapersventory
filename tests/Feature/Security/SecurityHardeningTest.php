<?php

namespace Tests\Feature\Security;

use App\Models\PaymentProof;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_present(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_health_command_passes(): void
    {
        Storage::fake('local');

        $exit = Artisan::call('reapersventory:health');

        $this->assertSame(0, $exit);
    }

    public function test_payment_proof_download_requires_super_admin(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('proofs/secret.pdf', 'secret');

        $owner = User::factory()->create();
        $payment = SubscriptionPayment::factory()->create([
            'business_id' => $owner->business_id,
            'subscription_id' => \App\Models\Subscription::factory()->create([
                'business_id' => $owner->business_id,
            ])->id,
            'submitted_by' => $owner->id,
        ]);

        $proof = PaymentProof::withoutGlobalScopes()->create([
            'business_id' => $owner->business_id,
            'subscription_payment_id' => $payment->id,
            'disk' => 'local',
            'path' => 'proofs/secret.pdf',
            'original_name' => 'secret.pdf',
            'mime_type' => 'application/pdf',
            'size' => 6,
            'uploaded_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->get(route('admin.payment-proofs.download', $proof))
            ->assertForbidden();

        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('admin.payment-proofs.download', $proof))
            ->assertOk();
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }
}
