<?php

namespace Tests\Feature\Earning;

use App\Enums\CampaignStatus;
use App\Enums\TaskAttemptStatus;
use App\Enums\TaskType;
use App\Enums\WalletTransactionType;
use App\Models\EarningTask;
use App\Models\TaskAttempt;
use App\Models\User;
use App\Services\CampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_video_campaign_pricing_scales_with_duration(): void
    {
        $campaigns = app(CampaignService::class);

        foreach ([60 => 2.0, 120 => 4.0, 180 => 6.0, 240 => 8.0] as $seconds => $reward) {
            $quote = $campaigns->quote(TaskType::YoutubeWatch, 100, $seconds);

            $this->assertSame($reward, $quote['reward']);
            $this->assertSame(0.0, $quote['fee']);
            $this->assertSame(round($reward * 100, 2), $quote['total']);
        }
    }

    public function test_quantity_campaign_pricing_charges_a_one_pkr_platform_fee(): void
    {
        $campaigns = app(CampaignService::class);

        $quote = $campaigns->quote(TaskType::YoutubeSubscribe, 100);

        $this->assertSame(0.02, $quote['reward']);
        $this->assertSame(1.0, $quote['fee']);
        $this->assertSame(3.0, $quote['total']);

        $bulk = $campaigns->quote(TaskType::InstagramFollow, 200);

        $this->assertSame(0.02, $bulk['reward']);
        $this->assertSame(1.0, $bulk['fee']);
        $this->assertSame(5.0, $bulk['total']);
    }

    public function test_creating_a_campaign_holds_the_payment_from_the_wallet(): void
    {
        $owner = User::factory()->earner()->create(['wallet_balance' => 10]);

        $this->actingAs($owner)
            ->post(route('campaigns.store'), [
                'task_type' => TaskType::YoutubeSubscribe->value,
                'title' => 'Grow my channel',
                'target_url' => 'https://www.youtube.com/@earnpk',
                'target_quantity' => 100,
            ])
            ->assertRedirect(route('campaigns.mine'))
            ->assertSessionHas('status');

        $campaign = EarningTask::query()->campaigns()->sole();

        $this->assertSame($owner->id, $campaign->user_id);
        $this->assertSame(CampaignStatus::Pending, $campaign->campaign_status);
        $this->assertFalse($campaign->is_active);
        $this->assertSame(100, $campaign->target_quantity);
        $this->assertSame(0, $campaign->completed_quantity);
        $this->assertSame(0.02, (float) $campaign->reward);
        $this->assertSame(3.0, (float) $campaign->total_cost);
        $this->assertSame(1.0, $campaign->platformFee());

        $this->assertSame(7.0, (float) $owner->fresh()->wallet_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $owner->id,
            'type' => WalletTransactionType::CampaignFunding->value,
            'amount' => 3,
        ]);
    }

    public function test_a_video_campaign_is_charged_by_its_duration(): void
    {
        $owner = User::factory()->earner()->create(['wallet_balance' => 500]);

        $this->actingAs($owner)
            ->post(route('campaigns.store'), [
                'task_type' => TaskType::YoutubeWatch->value,
                'title' => 'Watch my review video',
                'target_url' => 'https://www.youtube.com/watch?v=abc123',
                'target_quantity' => 100,
                'duration_seconds' => 120,
            ])
            ->assertRedirect(route('campaigns.mine'));

        $campaign = EarningTask::query()->campaigns()->sole();

        $this->assertSame(120, $campaign->duration_seconds);
        $this->assertSame(4.0, (float) $campaign->reward);
        $this->assertSame(400.0, (float) $campaign->total_cost);
        $this->assertSame(0.0, $campaign->platformFee());
        $this->assertSame(100.0, (float) $owner->fresh()->wallet_balance);
    }

    public function test_a_campaign_cannot_be_created_without_enough_wallet_balance(): void
    {
        $owner = User::factory()->earner()->create(['wallet_balance' => 1]);

        $this->actingAs($owner)
            ->post(route('campaigns.store'), [
                'task_type' => TaskType::YoutubeSubscribe->value,
                'title' => 'Too expensive',
                'target_url' => 'https://www.youtube.com/@earnpk',
                'target_quantity' => 100,
            ])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('earning_tasks', 0);
        $this->assertSame(1.0, (float) $owner->fresh()->wallet_balance);
    }

    public function test_approving_a_campaign_makes_it_live_for_other_users(): void
    {
        $owner = User::factory()->earner()->create();
        $campaign = EarningTask::factory()->campaign(100)->create(['user_id' => $owner->id]);

        $approved = app(CampaignService::class)->approve($campaign);

        $this->assertSame(CampaignStatus::Active, $approved->campaign_status);
        $this->assertTrue($approved->is_active);
        $this->assertNotNull($approved->approved_at);

        $this->assertTrue(
            EarningTask::query()->liveCampaigns()->whereKey($campaign->id)->exists()
        );
    }

    public function test_rejecting_a_campaign_refunds_the_full_payment(): void
    {
        $owner = User::factory()->earner()->create(['wallet_balance' => 0]);
        $campaign = EarningTask::factory()->campaign(100)->create([
            'user_id' => $owner->id,
            'total_cost' => 3,
        ]);

        $rejected = app(CampaignService::class)->reject($campaign, 'Link sahi nahi hai.');

        $this->assertSame(CampaignStatus::Rejected, $rejected->campaign_status);
        $this->assertFalse($rejected->is_active);
        $this->assertSame('Link sahi nahi hai.', $rejected->admin_notes);
        $this->assertSame(3.0, (float) $owner->fresh()->wallet_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $owner->id,
            'type' => WalletTransactionType::CampaignRefund->value,
            'amount' => 3,
        ]);
    }

    public function test_completing_a_campaign_increments_quantity_and_hides_it_when_full(): void
    {
        $owner = User::factory()->earner()->create();
        $firstEarner = User::factory()->earner()->create();
        $secondEarner = User::factory()->earner()->create();

        $campaign = EarningTask::factory()->liveCampaign(2)->create([
            'user_id' => $owner->id,
            'duration_seconds' => 15,
            'reward' => 0.02,
            'total_cost' => 1.04,
        ]);

        $this->completeTask($firstEarner, $campaign);

        $campaign->refresh();

        $this->assertSame(1, $campaign->completed_quantity);
        $this->assertSame(CampaignStatus::Active, $campaign->campaign_status);
        $this->assertTrue($campaign->is_active);
        $this->assertSame(0.02, (float) $firstEarner->fresh()->wallet_balance);

        $this->completeTask($secondEarner, $campaign);

        $campaign->refresh();

        $this->assertSame(2, $campaign->completed_quantity);
        $this->assertSame(CampaignStatus::Completed, $campaign->campaign_status);
        $this->assertFalse($campaign->is_active);
        $this->assertTrue($campaign->isSoldOut());
        $this->assertSame(0.02, (float) $secondEarner->fresh()->wallet_balance);
        $this->assertFalse(
            EarningTask::query()->liveCampaigns()->whereKey($campaign->id)->exists()
        );
    }

    public function test_an_owner_cannot_complete_their_own_campaign(): void
    {
        $owner = User::factory()->earner()->create();
        $campaign = EarningTask::factory()->liveCampaign(100)->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->post(route('tasks.start', $campaign))
            ->assertSessionHasErrors('task');

        $this->assertDatabaseCount('task_attempts', 0);
    }

    public function test_a_campaign_can_only_be_completed_once_per_earner(): void
    {
        $owner = User::factory()->earner()->create();
        $earner = User::factory()->earner()->create();
        $campaign = EarningTask::factory()->liveCampaign(100)->create(['user_id' => $owner->id]);

        TaskAttempt::factory()->create([
            'user_id' => $earner->id,
            'earning_task_id' => $campaign->id,
            'status' => TaskAttemptStatus::Completed,
            'started_at' => now()->subDays(3)->subMinutes(5),
            'expires_at' => now()->subDays(3),
            'completed_at' => now()->subDays(3),
        ]);

        $this->actingAs($earner)
            ->post(route('tasks.start', $campaign))
            ->assertSessionHasErrors('task');

        $this->assertSame(1, TaskAttempt::query()->where('user_id', $earner->id)->count());
    }

    public function test_cancelling_a_pending_campaign_refunds_the_full_payment(): void
    {
        $owner = User::factory()->earner()->create(['wallet_balance' => 0]);
        $campaign = EarningTask::factory()->campaign(100)->create([
            'user_id' => $owner->id,
            'total_cost' => 3,
        ]);

        $this->actingAs($owner)
            ->post(route('campaigns.cancel', $campaign))
            ->assertRedirect();

        $this->assertSame(CampaignStatus::Cancelled, $campaign->fresh()->campaign_status);
        $this->assertSame(3.0, (float) $owner->fresh()->wallet_balance);
    }

    public function test_cancelling_a_live_campaign_refunds_only_the_unused_reward_budget(): void
    {
        $owner = User::factory()->earner()->create(['wallet_balance' => 0]);
        $campaign = EarningTask::factory()->liveCampaign(100)->create([
            'user_id' => $owner->id,
            'reward' => 0.02,
            'total_cost' => 3,
            'completed_quantity' => 40,
        ]);

        $this->actingAs($owner)
            ->post(route('campaigns.cancel', $campaign))
            ->assertRedirect();

        $cancelled = $campaign->fresh();

        $this->assertSame(CampaignStatus::Cancelled, $cancelled->campaign_status);
        $this->assertFalse($cancelled->is_active);

        // 60 remaining actions × 0.02 PKR. The 1 PKR platform fee is retained.
        $this->assertSame(1.2, (float) $owner->fresh()->wallet_balance);
    }

    public function test_a_pending_campaign_is_hidden_from_other_users(): void
    {
        $owner = User::factory()->earner()->create();
        $viewer = User::factory()->earner()->create();

        $pending = EarningTask::factory()->campaign(100)->create([
            'user_id' => $owner->id,
            'title' => 'Pending campaign title',
        ]);

        EarningTask::factory()->liveCampaign(100)->create([
            'user_id' => $owner->id,
            'title' => 'Live campaign title',
        ]);

        $this->actingAs($viewer)
            ->get(route('campaigns.index'))
            ->assertOk()
            ->assertSee('Live campaign title')
            ->assertDontSee('Pending campaign title');

        $this->actingAs($viewer)
            ->post(route('tasks.start', $pending))
            ->assertSessionHasErrors('task');

        $this->actingAs($viewer)
            ->get(route('campaigns.show', $pending))
            ->assertNotFound();
    }

    private function completeTask(User $earner, EarningTask $campaign): void
    {
        $this->actingAs($earner)->post(route('tasks.start', $campaign))->assertRedirect();
        $this->actingAs($earner)->postJson(route('tasks.opened', $campaign))->assertOk();

        $this->travel(16)->seconds();

        $this->actingAs($earner)
            ->post(route('tasks.complete', $campaign))
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHas('status');
    }
}
