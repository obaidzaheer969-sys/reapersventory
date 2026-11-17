<?php

namespace Tests\Feature\Earning;

use App\Enums\DepositStatus;
use App\Enums\WalletTransactionType;
use App\Models\Deposit;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\DepositService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DepositFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_page_shows_a_notice_when_cashmaal_is_not_configured(): void
    {
        $user = User::factory()->earner()->create();

        $this->actingAs($user)
            ->get(route('deposits.index'))
            ->assertOk()
            ->assertSee('Cashmaal deposits filhal available nahi hain.');
    }

    public function test_deposit_cannot_be_created_when_gateway_is_not_configured(): void
    {
        $user = User::factory()->earner()->create();

        $this->actingAs($user)
            ->post(route('deposits.store'), ['amount' => 500])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('deposits', 0);
    }

    public function test_deposit_redirects_to_cashmaal_with_the_expected_payload(): void
    {
        $this->configureCashmaal();
        $user = User::factory()->earner()->create();

        $response = $this->actingAs($user)
            ->post(route('deposits.store'), ['amount' => 250])
            ->assertOk()
            ->assertViewIs('earning.deposits.redirect')
            ->assertSee('Open Cashmaal payment');

        $deposit = Deposit::query()->sole();
        $fields = $response->viewData('fields');

        $this->assertSame(DepositStatus::Pending, $deposit->status);
        $this->assertSame(250.0, (float) $deposit->amount);
        $this->assertSame('WEB-12345', $fields['web_id']);
        $this->assertSame($deposit->order_id, $fields['order_id']);
        $this->assertSame('250.00', $fields['amount']);
        $this->assertSame(route('deposits.cashmaal.success'), $fields['succes_url']);
        $this->assertSame(route('deposits.cashmaal.cancel'), $fields['cancel_url']);
    }

    public function test_deposit_succeeds_when_a_stale_setting_object_is_cached(): void
    {
        $this->configureCashmaal();
        $user = User::factory()->earner()->create();

        foreach (['cashmaal_enabled', 'cashmaal_web_id', 'cashmaal_ipn_key'] as $key) {
            Cache::put("system_setting.{$key}", new \stdClass, 300);
        }

        $response = $this->actingAs($user)
            ->post(route('deposits.store'), ['amount' => 500])
            ->assertOk()
            ->assertViewIs('earning.deposits.redirect');

        $this->assertSame('WEB-12345', $response->viewData('fields')['web_id']);
        $this->assertDatabaseCount('deposits', 1);
    }

    public function test_a_successful_ipn_credits_the_wallet_and_acknowledges(): void
    {
        $this->configureCashmaal();
        $user = User::factory()->earner()->create(['wallet_balance' => 100]);
        $deposit = app(DepositService::class)->create($user, 500);

        $payload = [
            'status' => '1',
            'ipn_key' => 'IPN-SECRET',
            'order_id' => $deposit->order_id,
            'Amount' => '500.00',
            'CM_TID' => 'CM-TXN-1',
            'fee' => '0',
            'currency' => 'PKR',
            'client_email' => $user->email,
        ];

        foreach ([1, 2] as $attempt) {
            $response = $this->post(route('deposits.cashmaal.ipn'), $payload);

            $response->assertOk();
            $this->assertSame('**OK**', $response->getContent());
        }

        $deposit->refresh();

        $this->assertSame(DepositStatus::Completed, $deposit->status);
        $this->assertSame('CM-TXN-1', $deposit->cm_tid);
        $this->assertNotNull($deposit->paid_at);
        $this->assertSame(600.0, (float) $user->fresh()->wallet_balance);
        $this->assertSame(1, $user->fresh()->deposits()->completed()->count());
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'type' => WalletTransactionType::Deposit->value,
            'amount' => 500,
        ]);
    }

    public function test_an_ipn_with_an_invalid_key_is_rejected(): void
    {
        $this->configureCashmaal();
        $user = User::factory()->earner()->create();
        $deposit = app(DepositService::class)->create($user, 500);

        $response = $this->post(route('deposits.cashmaal.ipn'), [
            'status' => '1',
            'ipn_key' => 'WRONG-KEY',
            'order_id' => $deposit->order_id,
            'Amount' => '500.00',
        ]);

        $response->assertOk();
        $this->assertSame('Invalid IPN key', $response->getContent());
        $this->assertSame(DepositStatus::Pending, $deposit->fresh()->status);
        $this->assertSame(0.0, (float) $user->fresh()->wallet_balance);
    }

    public function test_an_ipn_with_a_lower_amount_fails_the_deposit(): void
    {
        $this->configureCashmaal();
        $user = User::factory()->earner()->create();
        $deposit = app(DepositService::class)->create($user, 500);

        $this->post(route('deposits.cashmaal.ipn'), [
            'status' => '1',
            'ipn_key' => 'IPN-SECRET',
            'order_id' => $deposit->order_id,
            'Amount' => '100.00',
            'CM_TID' => 'CM-LOW',
        ])->assertOk();

        $deposit->refresh();

        $this->assertSame(DepositStatus::Failed, $deposit->status);
        $this->assertStringContainsString('kam amount', (string) $deposit->admin_notes);
        $this->assertSame(0.0, (float) $user->fresh()->wallet_balance);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_the_cancel_callback_cancels_a_pending_deposit(): void
    {
        $this->configureCashmaal();
        $user = User::factory()->earner()->create();
        $deposit = app(DepositService::class)->create($user, 500);

        $this->get(route('deposits.cashmaal.cancel', ['order_id' => $deposit->order_id]))
            ->assertOk();

        $this->assertSame(DepositStatus::Cancelled, $deposit->fresh()->status);
        $this->assertSame(0.0, (float) $user->fresh()->wallet_balance);
    }

    public function test_the_success_callback_verifies_the_payment_with_cashmaal(): void
    {
        $this->configureCashmaal();
        $user = User::factory()->earner()->create();
        $deposit = app(DepositService::class)->create($user, 500);

        Http::fake([
            'api.cmaal.com/*' => Http::response([
                'status' => 1,
                'order_id' => $deposit->order_id,
                'PKR_amount' => 500,
                'fee_in_PKR' => 0,
            ]),
        ]);

        $this->get(route('deposits.cashmaal.success', [
            'CM_TID' => 'CM-VERIFIED',
            'order_id' => $deposit->order_id,
        ]))->assertOk();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'CM_TID=CM-VERIFIED'));

        $deposit->refresh();

        $this->assertSame(DepositStatus::Completed, $deposit->status);
        $this->assertSame('CM-VERIFIED', $deposit->cm_tid);
        $this->assertSame(500.0, (float) $user->fresh()->wallet_balance);
    }

    public function test_a_user_cannot_pay_another_users_deposit(): void
    {
        $this->configureCashmaal();

        $owner = User::factory()->earner()->create();
        $otherUser = User::factory()->earner()->create();
        $deposit = Deposit::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($otherUser)
            ->get(route('deposits.pay', $deposit))
            ->assertForbidden();

        $this->assertSame(DepositStatus::Pending, $deposit->fresh()->status);
    }

    private function configureCashmaal(): void
    {
        SystemSetting::setValue('cashmaal_enabled', true);
        SystemSetting::setValue('cashmaal_web_id', 'WEB-12345');
        SystemSetting::setValue('cashmaal_ipn_key', 'IPN-SECRET');
    }
}
