<?php

namespace Tests\Feature\Api;

use App\Enums\PayoutStatus;
use App\Enums\WalletTransactionType;
use App\Models\Announcement;
use App\Models\EarningTask;
use App\Models\PayoutRequest;
use App\Models\TaskAttempt;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_the_earners_current_summary(): void
    {
        $user = User::factory()->earner()->create(['wallet_balance' => 125.5]);

        $completedTask = EarningTask::factory()->youtubeSubscribe(15, 25)->create();
        $availableTask = EarningTask::factory()->youtubeSubscribe(15, 10)->create();
        $campaignOwner = User::factory()->earner()->create();
        EarningTask::factory()->liveCampaign()->create(['user_id' => $campaignOwner->id]);

        TaskAttempt::factory()->create([
            'user_id' => $user->id,
            'earning_task_id' => $completedTask->id,
            'reward_amount' => 25,
            'completed_at' => now(),
        ]);

        PayoutRequest::factory()->create([
            'user_id' => $user->id,
            'status' => PayoutStatus::Pending,
        ]);

        WalletTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => WalletTransactionType::TaskReward,
            'amount' => 25,
            'description' => 'Task reward: Watch this',
        ]);

        Announcement::factory()->create(['title' => 'New tasks are live']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('wallet_balance', 125.5)
            ->assertJsonPath('today_earned', 25)
            ->assertJsonPath('total_earned', 25)
            ->assertJsonPath('available_tasks', 1)
            ->assertJsonPath('active_campaigns', 1)
            ->assertJsonPath('pending_withdrawals', 1)
            ->assertJsonPath('announcements.0.title', 'New tasks are live')
            ->assertJsonPath('recent_transactions.0.description', 'Task reward: Watch this');
    }

    public function test_earnings_returns_only_earning_transactions_with_a_summary(): void
    {
        $user = User::factory()->earner()->create(['wallet_balance' => 75]);

        WalletTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => WalletTransactionType::TaskReward,
            'amount' => 30,
        ]);
        WalletTransaction::factory()->create([
            'user_id' => $user->id,
            'type' => WalletTransactionType::Deposit,
            'amount' => 500,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/earnings')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.wallet_balance', 75)
            ->assertJsonPath('meta.total_earned', 0);
    }
}
