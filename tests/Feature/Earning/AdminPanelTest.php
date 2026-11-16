<?php

namespace Tests\Feature\Earning;

use App\Enums\WalletTransactionType;
use App\Filament\Resources\Campaigns\Pages\ListCampaigns;
use App\Filament\Resources\Earners\Pages\ListEarners;
use App\Filament\Resources\EarningTasks\Pages\ListEarningTasks;
use App\Models\EarningTask;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_only_the_earning_system(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('Earners');
        $response->assertSee('Pending payouts');
        $response->assertSee('Campaigns');
        $response->assertSee('Deposits');
        $response->assertSee('Cashmaal settings');
        $response->assertDontSee('Businesses');
        $response->assertDontSee('Subscriptions');
        $response->assertDontSee('Payment methods');
    }

    public function test_admin_task_table_only_lists_system_tasks(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $systemTask = EarningTask::factory()->youtubeSubscribe()->create();
        $campaign = EarningTask::factory()->campaign(100)->create();

        Livewire::actingAs($admin)
            ->test(ListEarningTasks::class)
            ->assertCanSeeTableRecords([$systemTask])
            ->assertCanNotSeeTableRecords([$campaign]);
    }

    public function test_admin_campaign_table_only_lists_user_campaigns(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $systemTask = EarningTask::factory()->youtubeSubscribe()->create();
        $campaign = EarningTask::factory()->campaign(100)->create();

        Livewire::actingAs($admin)
            ->test(ListCampaigns::class)
            ->assertCanSeeTableRecords([$campaign])
            ->assertCanNotSeeTableRecords([$systemTask]);
    }

    public function test_admin_can_add_balance_to_a_users_wallet(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $earner = User::factory()->earner()->create(['wallet_balance' => 0]);

        $this->actingAs($admin)->get('/admin/earners')->assertOk();

        Livewire::actingAs($admin)
            ->test(ListEarners::class)
            ->callAction(
                TestAction::make('addWallet')->table($earner),
                data: ['amount' => 500, 'description' => 'Manual top-up'],
            )
            ->assertHasNoActionErrors();

        $this->assertSame(500.0, (float) $earner->fresh()->wallet_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $earner->id,
            'type' => WalletTransactionType::AdminCredit->value,
            'amount' => 500,
            'description' => 'Manual top-up',
        ]);
    }
}
