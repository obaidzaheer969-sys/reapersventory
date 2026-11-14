<?php

namespace Tests\Feature\Earning;

use App\Enums\PayoutStatus;
use App\Enums\WalletTransactionType;
use App\Models\PayoutAccount;
use App\Models\PayoutRequest;
use App\Models\User;
use App\Notifications\PayoutRequestedAdminNotification;
use App\Notifications\PayoutStatusNotification;
use App\Services\PayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PayoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_payout_request_holds_the_amount_and_notifies_admins(): void
    {
        Notification::fake();

        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->earner()->create(['wallet_balance' => 1000]);
        $account = PayoutAccount::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('wallet.payouts.store'), [
                'payout_account_id' => $account->id,
                'amount' => 500,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(500.0, (float) $user->fresh()->wallet_balance);

        $payout = PayoutRequest::query()->sole();

        $this->assertSame(PayoutStatus::Pending, $payout->status);
        $this->assertSame(500.0, (float) $payout->amount);
        $this->assertSame(500.0, $user->fresh()->pendingPayoutTotal());
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'type' => WalletTransactionType::PayoutRequest->value,
            'amount' => 500,
        ]);

        Notification::assertSentTo($admin, PayoutRequestedAdminNotification::class);
    }

    public function test_payout_below_the_minimum_is_rejected(): void
    {
        $user = User::factory()->earner()->create(['wallet_balance' => 1000]);
        $account = PayoutAccount::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('wallet.payouts.store'), [
                'payout_account_id' => $account->id,
                'amount' => 100,
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(1000.0, (float) $user->fresh()->wallet_balance);
        $this->assertDatabaseCount('payout_requests', 0);
    }

    public function test_a_payout_larger_than_the_wallet_balance_is_rejected(): void
    {
        $user = User::factory()->earner()->create(['wallet_balance' => 1000]);
        $account = PayoutAccount::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('wallet.payouts.store'), [
                'payout_account_id' => $account->id,
                'amount' => 2000,
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(1000.0, (float) $user->fresh()->wallet_balance);
        $this->assertDatabaseCount('payout_requests', 0);
    }

    public function test_rejecting_a_payout_refunds_the_user_wallet(): void
    {
        Notification::fake();

        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->earner()->create(['wallet_balance' => 1000]);
        $account = PayoutAccount::factory()->create(['user_id' => $user->id]);

        $service = app(PayoutService::class);
        $payout = $service->requestPayout($user, $account, 500);

        $this->assertSame(500.0, (float) $user->fresh()->wallet_balance);

        $service->reject($payout, $admin, 'Account number mismatch.');

        $payout->refresh();

        $this->assertSame(PayoutStatus::Rejected, $payout->status);
        $this->assertSame('Account number mismatch.', $payout->admin_notes);
        $this->assertSame($admin->id, $payout->processed_by);
        $this->assertSame(1000.0, (float) $user->fresh()->wallet_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'type' => WalletTransactionType::PayoutRefund->value,
            'amount' => 500,
        ]);

        Notification::assertSentTo($user, PayoutStatusNotification::class);
    }

    public function test_marking_a_payout_paid_updates_withdrawn_totals_without_refunding(): void
    {
        Notification::fake();

        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->earner()->create(['wallet_balance' => 1000]);
        $account = PayoutAccount::factory()->create(['user_id' => $user->id]);

        $service = app(PayoutService::class);
        $payout = $service->requestPayout($user, $account, 500);
        $service->markPaid($payout, $admin, 'Sent via Easypaisa.');

        $payout->refresh();

        $this->assertSame(PayoutStatus::Paid, $payout->status);
        $this->assertSame($admin->id, $payout->processed_by);
        $this->assertNotNull($payout->processed_at);
        $this->assertSame(500.0, (float) $user->fresh()->wallet_balance);
        $this->assertSame(500.0, $user->fresh()->totalWithdrawn());
        $this->assertSame(0.0, $user->fresh()->pendingPayoutTotal());

        Notification::assertSentTo($user, PayoutStatusNotification::class);
    }
}
