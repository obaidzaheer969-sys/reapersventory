<?php

namespace Tests\Feature\Api;

use App\Enums\PayoutProvider;
use App\Enums\PayoutStatus;
use App\Enums\UserRole;
use App\Enums\WalletTransactionType;
use App\Models\PaymentMethod;
use App\Models\PayoutAccount;
use App\Models\PayoutRequest;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_returns_the_current_balance_and_recent_activity(): void
    {
        $user = User::factory()->earner()->create(['wallet_balance' => 200]);

        PayoutRequest::factory()->create([
            'user_id' => $user->id,
            'status' => PayoutStatus::Pending,
        ]);

        WalletTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => WalletTransactionType::TaskReward,
            'amount' => 50,
            'description' => 'Task reward: Watch this',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/wallet')
            ->assertOk()
            ->assertJsonPath('wallet_balance', 200)
            ->assertJsonPath('pending_withdrawals', 1)
            ->assertJsonPath('min_payout', 50)
            ->assertJsonPath('max_payout', 250)
            ->assertJsonPath('payout_fee_percent', 10)
            ->assertJsonPath('recent_transactions.0.description', 'Task reward: Watch this');
    }

    public function test_earner_can_save_a_payout_account_and_request_a_withdrawal(): void
    {
        $user = User::factory()->earner()->create(['wallet_balance' => 500]);
        $otherUser = User::factory()->earner()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/payout-accounts', [
                'provider' => PayoutProvider::Easypaisa->value,
                'account_name' => 'Test Account',
                'account_number' => '03001234567',
                'is_default' => true,
                'user_id' => $otherUser->id,
                'role' => UserRole::SuperAdmin->value,
                'wallet_balance' => 9999,
                'is_active' => false,
            ])
            ->assertCreated()
            ->assertJsonPath('provider', PayoutProvider::Easypaisa->value)
            ->assertJsonPath('is_default', true);

        $account = $user->payoutAccounts()->sole();

        $this->assertDatabaseHas('payout_accounts', [
            'id' => $account->id,
            'user_id' => $user->id,
            'account_number' => '03001234567',
        ]);

        $this->assertDatabaseMissing('payout_accounts', ['user_id' => $otherUser->id]);

        $this->assertSame(UserRole::Earner, $user->fresh()->role);
        $this->assertTrue((bool) $user->fresh()->is_active);
        $this->assertSame(500.0, (float) $user->fresh()->wallet_balance);

        $this->postJson('/api/v1/withdrawals', [
            'payout_account_id' => $account->id,
            'amount' => 100,
        ])
            ->assertCreated()
            ->assertJsonPath('amount', 90)
            ->assertJsonPath('fee', 10)
            ->assertJsonPath('status', PayoutStatus::Pending->value);

        $this->assertSame(400.0, (float) $user->fresh()->wallet_balance);

        $this->assertDatabaseHas('payout_requests', [
            'user_id' => $user->id,
            'payout_account_id' => $account->id,
            'gross_amount' => 100,
            'fee' => 10,
            'amount' => 90,
            'status' => PayoutStatus::Pending->value,
        ]);
    }

    public function test_withdrawal_rejects_a_foreign_payout_account(): void
    {
        $user = User::factory()->earner()->create(['wallet_balance' => 500]);
        $foreignAccount = PayoutAccount::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/withdrawals', [
                'payout_account_id' => $foreignAccount->id,
                'amount' => 100,
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('payout_requests', 0);
        $this->assertSame(500.0, (float) $user->fresh()->wallet_balance);
    }

    public function test_withdrawal_amount_is_validated(): void
    {
        $user = User::factory()->earner()->create(['wallet_balance' => 500]);
        $account = PayoutAccount::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/withdrawals', [
                'payout_account_id' => $account->id,
                'amount' => 25,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');
    }

    public function test_only_active_payment_methods_are_returned(): void
    {
        $user = User::factory()->earner()->create();

        PaymentMethod::factory()->create(['name' => 'Active Method', 'is_active' => true]);
        PaymentMethod::factory()->create(['name' => 'Inactive Method', 'is_active' => false]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/payment-methods')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Active Method');
    }

    public function test_deposit_returns_checkout_details_without_gateway_secrets(): void
    {
        SystemSetting::setValue('cashmaal_enabled', true);
        SystemSetting::setValue('cashmaal_web_id', 'public-web-id');
        SystemSetting::setValue('cashmaal_ipn_key', 'super-secret-ipn-key');

        $user = User::factory()->earner()->create();
        $method = PaymentMethod::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/deposits', [
                'payment_method_id' => $method->id,
                'amount' => 100,
            ])
            ->assertCreated()
            ->assertJsonPath('amount', 100)
            ->assertJsonPath('fee', 10)
            ->assertJsonPath('total', 110)
            ->assertJsonPath('checkout.fields.web_id', 'public-web-id');

        $this->assertStringNotContainsString('super-secret-ipn-key', $response->getContent());

        $this->assertDatabaseHas('deposits', [
            'user_id' => $user->id,
            'amount' => 110,
            'credit_amount' => 100,
            'fee' => 10,
            'status' => 'pending',
        ]);
    }
}
