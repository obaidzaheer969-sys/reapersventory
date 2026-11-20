<?php

namespace Tests\Feature\Api;

use App\Enums\CampaignStatus;
use App\Enums\DepositStatus;
use App\Enums\PayoutStatus;
use App\Enums\UserRole;
use App\Enums\WalletTransactionType;
use App\Models\Deposit;
use App\Models\EarningTask;
use App\Models\PayoutRequest;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: UserRole}>
     */
    public static function nonAdminRoles(): array
    {
        return [
            'earner' => [UserRole::Earner],
            'business owner' => [UserRole::BusinessOwner],
        ];
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_roles_cannot_access_admin_api(UserRole $role): void
    {
        $user = $role === UserRole::Earner
            ? User::factory()->earner()->create()
            : User::factory()->businessOwner()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/settings')
            ->assertForbidden();
    }

    public function test_super_admin_can_list_earners_and_read_public_safe_settings(): void
    {
        $admin = User::factory()->superAdmin()->create();
        User::factory()->earner()->create(['name' => 'Alice Earner']);
        User::factory()->earner()->create(['name' => 'Bob Earner']);
        User::factory()->businessOwner()->create(['name' => 'Business Owner']);

        SystemSetting::setValue('campaign_admin_fee_visible', false);
        SystemSetting::setValue('withdrawals_enabled', false);
        SystemSetting::setValue('ads_enabled', true);
        SystemSetting::setValue('site_approved', true);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/earners')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['name' => 'Alice Earner'])
            ->assertJsonFragment(['name' => 'Bob Earner']);

        $this->getJson('/api/v1/admin/settings')
            ->assertOk()
            ->assertJsonPath('campaign_admin_fee_visible', false)
            ->assertJsonPath('withdrawals_enabled', false)
            ->assertJsonPath('ads_enabled', true)
            ->assertJsonPath('site_approved', true);
    }

    public function test_super_admin_can_approve_a_payout_request(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $earner = User::factory()->earner()->create();
        $payout = PayoutRequest::factory()->create([
            'user_id' => $earner->id,
            'status' => PayoutStatus::Pending,
            'gross_amount' => 100,
            'fee' => 10,
            'amount' => 90,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/payout-requests/{$payout->id}/approve", [
                'notes' => 'Paid via Easypaisa',
            ])
            ->assertOk()
            ->assertJsonPath('status', PayoutStatus::Paid->value)
            ->assertJsonPath('amount', 90);

        $payout->refresh();

        $this->assertSame(PayoutStatus::Paid, $payout->status);
        $this->assertSame($admin->id, (int) $payout->processed_by);
        $this->assertSame('Paid via Easypaisa', $payout->admin_notes);
    }

    public function test_super_admin_can_reject_a_campaign_and_refund_its_owner(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->earner()->create(['wallet_balance' => 0]);
        $campaign = EarningTask::factory()->campaign(100)->create([
            'user_id' => $owner->id,
            'total_cost' => 300,
            'campaign_status' => CampaignStatus::Pending,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/campaigns/{$campaign->id}/reject", [
                'admin_notes' => 'Please review the target URL.',
            ])
            ->assertOk()
            ->assertJsonPath('status', CampaignStatus::Rejected->value);

        $this->assertSame(CampaignStatus::Rejected, $campaign->fresh()->campaign_status);
        $this->assertSame('Please review the target URL.', $campaign->fresh()->admin_notes);
        $this->assertSame(300.0, (float) $owner->fresh()->wallet_balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $owner->id,
            'type' => WalletTransactionType::CampaignRefund->value,
            'amount' => 300,
        ]);
    }

    public function test_super_admin_can_complete_a_deposit(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $earner = User::factory()->earner()->create(['wallet_balance' => 0]);
        $deposit = Deposit::factory()->create([
            'user_id' => $earner->id,
            'status' => DepositStatus::Pending,
            'amount' => 110,
            'credit_amount' => 100,
            'fee' => 10,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/deposits/{$deposit->id}/complete", [
                'cm_tid' => 'TID-123',
                'notes' => 'Verified manually',
            ])
            ->assertOk()
            ->assertJsonPath('status', DepositStatus::Completed->value)
            ->assertJsonPath('credit_amount', 100);

        $this->assertSame(DepositStatus::Completed, $deposit->fresh()->status);
        $this->assertSame('TID-123', $deposit->fresh()->cm_tid);
        $this->assertSame('Verified manually', $deposit->fresh()->admin_notes);
        $this->assertSame(100.0, (float) $earner->fresh()->wallet_balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $earner->id,
            'type' => WalletTransactionType::Deposit->value,
            'amount' => 100,
        ]);
    }

    public function test_super_admin_can_fail_a_deposit(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $earner = User::factory()->earner()->create(['wallet_balance' => 0]);
        $deposit = Deposit::factory()->create([
            'user_id' => $earner->id,
            'status' => DepositStatus::Pending,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/deposits/{$deposit->id}/fail", [
                'notes' => 'Payment was not received.',
            ])
            ->assertOk()
            ->assertJsonPath('status', DepositStatus::Failed->value);

        $this->assertSame(DepositStatus::Failed, $deposit->fresh()->status);
        $this->assertSame('Payment was not received.', $deposit->fresh()->admin_notes);
        $this->assertSame(0.0, (float) $earner->fresh()->wallet_balance);
    }

    public function test_super_admin_can_list_and_revoke_api_tokens(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $earner = User::factory()->earner()->create();
        $earner->createToken('Android phone');

        $token = $earner->tokens()->sole();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/tokens')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $token->id)
            ->assertJsonPath('data.0.user_name', $earner->name);

        $this->deleteJson("/api/v1/admin/tokens/{$token->id}")
            ->assertOk()
            ->assertJsonPath('message', 'API token revoked.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
