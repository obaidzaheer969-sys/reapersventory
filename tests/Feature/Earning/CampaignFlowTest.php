<?php

namespace Tests\Feature\Earning;

use App\Enums\CampaignStatus;
use App\Enums\TaskAttemptStatus;
use App\Enums\TaskType;
use App\Enums\WalletTransactionType;
use App\Models\EarningTask;
use App\Models\TaskAttempt;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\CampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CampaignFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_video_campaign_pricing_is_flat_regardless_of_duration(): void
    {
        $campaigns = app(CampaignService::class);

        foreach ([60, 120, 180, 240] as $seconds) {
            $quote = $campaigns->quote(TaskType::YoutubeWatch, 100, $seconds);

            $this->assertSame(2.0, $quote['reward']);
            $this->assertSame(100.0, $quote['fee']);
            $this->assertSame(300.0, $quote['total']);
            $this->assertSame($seconds, $quote['duration_seconds']);
        }
    }

    public function test_quantity_campaign_pricing_includes_one_rupee_admin_fee_per_action(): void
    {
        $campaigns = app(CampaignService::class);

        $quote = $campaigns->quote(TaskType::YoutubeSubscribe, 100);

        $this->assertSame(2.0, $quote['reward']);
        $this->assertSame(100.0, $quote['fee']);
        $this->assertSame(300.0, $quote['total']);

        $bulk = $campaigns->quote(TaskType::InstagramFollow, 200);

        $this->assertSame(2.0, $bulk['reward']);
        $this->assertSame(200.0, $bulk['fee']);
        $this->assertSame(600.0, $bulk['total']);
    }

    public function test_a_campaign_accepts_a_custom_quantity_of_one_action(): void
    {
        $owner = User::factory()->earner()->create(['wallet_balance' => 10]);

        $this->actingAs($owner)
            ->post(route('campaigns.store'), [
                'task_type' => TaskType::YoutubeSubscribe->value,
                'title' => 'One subscriber',
                'target_url' => 'https://www.youtube.com/@earnpk',
                'target_quantity' => 1,
            ])
            ->assertRedirect(route('campaigns.mine'))
            ->assertSessionHasNoErrors();

        $campaign = EarningTask::query()->campaigns()->sole();

        $this->assertSame(1, $campaign->target_quantity);
        $this->assertSame(3.0, (float) $campaign->total_cost);
        $this->assertSame(7.0, (float) $owner->fresh()->wallet_balance);
    }

    public function test_a_campaign_accepts_a_custom_quantity_of_two_actions(): void
    {
        $owner = User::factory()->earner()->create(['wallet_balance' => 10]);

        $this->actingAs($owner)
            ->post(route('campaigns.store'), [
                'task_type' => TaskType::InstagramFollow->value,
                'title' => 'Two followers',
                'target_url' => 'https://www.instagram.com/earnpk/',
                'target_quantity' => 2,
            ])
            ->assertRedirect(route('campaigns.mine'))
            ->assertSessionHasNoErrors();

        $campaign = EarningTask::query()->campaigns()->sole();

        $this->assertSame(2, $campaign->target_quantity);
        $this->assertSame(6.0, (float) $campaign->total_cost);
        $this->assertSame(4.0, (float) $owner->fresh()->wallet_balance);
    }

    public function test_a_user_cannot_choose_their_own_campaign_reward(): void
    {
        $owner = User::factory()->earner()->create(['wallet_balance' => 10]);

        $this->actingAs($owner)
            ->post(route('campaigns.store'), [
                'task_type' => TaskType::YoutubeSubscribe->value,
                'title' => 'Fixed reward only',
                'target_url' => 'https://www.youtube.com/@earnpk',
                'target_quantity' => 1,
                'reward' => 9999,
                'total_cost' => 1,
            ])
            ->assertRedirect(route('campaigns.mine'))
            ->assertSessionHasNoErrors();

        $campaign = EarningTask::query()->campaigns()->sole();

        $this->assertSame(2.0, (float) $campaign->reward);
        $this->assertSame(3.0, (float) $campaign->total_cost);
        $this->assertSame(7.0, (float) $owner->fresh()->wallet_balance);
    }

    public function test_creating_a_campaign_holds_the_payment_and_makes_it_live_immediately(): void
    {
        $owner = User::factory()->earner()->create(['wallet_balance' => 500]);

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
        $this->assertSame(CampaignStatus::Active, $campaign->campaign_status);
        $this->assertTrue($campaign->is_active);
        $this->assertNotNull($campaign->approved_at);
        $this->assertSame(100, $campaign->target_quantity);
        $this->assertSame(0, $campaign->completed_quantity);
        $this->assertSame(2.0, (float) $campaign->reward);
        $this->assertSame(300.0, (float) $campaign->total_cost);
        $this->assertSame(0.0, $campaign->platformFee());
        $this->assertTrue($campaign->isLiveCampaign());

        $this->assertSame(200.0, (float) $owner->fresh()->wallet_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $owner->id,
            'type' => WalletTransactionType::CampaignFunding->value,
            'amount' => 300,
        ]);
    }

    public function test_a_video_campaign_is_charged_a_flat_reward(): void
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
        $this->assertSame(2.0, (float) $campaign->reward);
        $this->assertSame(300.0, (float) $campaign->total_cost);
        $this->assertSame(0.0, $campaign->platformFee());
        $this->assertSame(200.0, (float) $owner->fresh()->wallet_balance);
    }

    public function test_each_completed_campaign_action_credits_one_rupee_to_the_admin(): void
    {
        Notification::fake();

        $admin = User::factory()->superAdmin()->create(['wallet_balance' => 0]);
        $owner = User::factory()->earner()->create(['wallet_balance' => 20]);
        $firstEarner = User::factory()->earner()->create();
        $secondEarner = User::factory()->earner()->create();

        $campaign = app(CampaignService::class)->create($owner, [
            'task_type' => TaskType::YoutubeSubscribe->value,
            'title' => 'Two subscribers',
            'target_url' => 'https://www.youtube.com/@earnpk',
            'target_quantity' => 2,
        ]);

        $this->completeTask($firstEarner, $campaign);
        $this->completeTask($secondEarner, $campaign);

        $campaign->refresh();

        $this->assertSame(2, $campaign->completed_quantity);
        $this->assertSame(2.0, $campaign->platformFee());
        $this->assertSame(0.0, $campaign->budgetRemaining());
        $this->assertSame(2.0, (float) $admin->fresh()->wallet_balance);
        $this->assertSame(2.0, (float) $firstEarner->fresh()->wallet_balance);
        $this->assertSame(2.0, (float) $secondEarner->fresh()->wallet_balance);
        $this->assertSame(2, WalletTransaction::query()
            ->where('user_id', $admin->id)
            ->where('type', WalletTransactionType::CampaignAdminFee)
            ->count());
    }

    public function test_cancelling_a_partially_delivered_campaign_refunds_the_undelivered_amount(): void
    {
        Notification::fake();

        $admin = User::factory()->superAdmin()->create(['wallet_balance' => 0]);
        $owner = User::factory()->earner()->create(['wallet_balance' => 20]);
        $firstEarner = User::factory()->earner()->create();
        $secondEarner = User::factory()->earner()->create();

        $campaign = app(CampaignService::class)->create($owner, [
            'task_type' => TaskType::YoutubeSubscribe->value,
            'title' => 'Four subscribers',
            'target_url' => 'https://www.youtube.com/@earnpk',
            'target_quantity' => 4,
        ]);

        $this->assertSame(8.0, (float) $owner->fresh()->wallet_balance);

        $this->completeTask($firstEarner, $campaign);
        $this->completeTask($secondEarner, $campaign);

        $this->actingAs($owner)
            ->post(route('campaigns.cancel', $campaign))
            ->assertRedirect();

        $campaign->refresh();

        $this->assertSame(CampaignStatus::Cancelled, $campaign->campaign_status);
        $this->assertSame(2.0, $campaign->platformFee());
        $this->assertSame(6.0, $campaign->budgetRemaining());
        $this->assertSame(14.0, (float) $owner->fresh()->wallet_balance);
        $this->assertSame(2.0, (float) $admin->fresh()->wallet_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $owner->id,
            'type' => WalletTransactionType::CampaignRefund->value,
            'amount' => 6,
        ]);
    }

    public function test_cancelling_an_undelivered_campaign_refunds_the_full_amount(): void
    {
        Notification::fake();

        $owner = User::factory()->earner()->create(['wallet_balance' => 20]);

        $campaign = app(CampaignService::class)->create($owner, [
            'task_type' => TaskType::YoutubeSubscribe->value,
            'title' => 'Two subscribers',
            'target_url' => 'https://www.youtube.com/@earnpk',
            'target_quantity' => 2,
        ]);

        $this->actingAs($owner)
            ->post(route('campaigns.cancel', $campaign))
            ->assertRedirect();

        $this->assertSame(20.0, (float) $owner->fresh()->wallet_balance);
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
            'total_cost' => 200,
        ]);

        $rejected = app(CampaignService::class)->reject($campaign, 'Link sahi nahi hai.');

        $this->assertSame(CampaignStatus::Rejected, $rejected->campaign_status);
        $this->assertFalse($rejected->is_active);
        $this->assertSame('Link sahi nahi hai.', $rejected->admin_notes);
        $this->assertSame(200.0, (float) $owner->fresh()->wallet_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $owner->id,
            'type' => WalletTransactionType::CampaignRefund->value,
            'amount' => 200,
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
            'reward' => 2,
            'total_cost' => 4,
        ]);

        $this->completeTask($firstEarner, $campaign);

        $campaign->refresh();

        $this->assertSame(1, $campaign->completed_quantity);
        $this->assertSame(CampaignStatus::Active, $campaign->campaign_status);
        $this->assertTrue($campaign->is_active);
        $this->assertSame(2.0, (float) $firstEarner->fresh()->wallet_balance);

        $this->completeTask($secondEarner, $campaign);

        $campaign->refresh();

        $this->assertSame(2, $campaign->completed_quantity);
        $this->assertSame(CampaignStatus::Completed, $campaign->campaign_status);
        $this->assertFalse($campaign->is_active);
        $this->assertTrue($campaign->isSoldOut());
        $this->assertSame(2.0, (float) $secondEarner->fresh()->wallet_balance);
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
            'total_cost' => 200,
        ]);

        $this->actingAs($owner)
            ->post(route('campaigns.cancel', $campaign))
            ->assertRedirect();

        $this->assertSame(CampaignStatus::Cancelled, $campaign->fresh()->campaign_status);
        $this->assertSame(200.0, (float) $owner->fresh()->wallet_balance);
    }

    public function test_cancelling_a_live_campaign_refunds_only_the_unused_reward_budget(): void
    {
        $owner = User::factory()->earner()->create(['wallet_balance' => 0]);
        $campaign = EarningTask::factory()->liveCampaign(100)->create([
            'user_id' => $owner->id,
            'reward' => 2,
            'total_cost' => 200,
            'completed_quantity' => 40,
        ]);

        $this->actingAs($owner)
            ->post(route('campaigns.cancel', $campaign))
            ->assertRedirect();

        $cancelled = $campaign->fresh();

        $this->assertSame(CampaignStatus::Cancelled, $cancelled->campaign_status);
        $this->assertFalse($cancelled->is_active);

        // 60 remaining actions × 2 PKR. There is no platform fee to retain.
        $this->assertSame(120.0, (float) $owner->fresh()->wallet_balance);
    }

    public function test_a_legacy_pending_campaign_is_hidden_from_other_users(): void
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

    public function test_a_new_campaign_is_immediately_live_for_other_users(): void
    {
        $owner = User::factory()->earner()->create(['wallet_balance' => 500]);
        $viewer = User::factory()->earner()->create();

        $this->actingAs($owner)
            ->post(route('campaigns.store'), [
                'task_type' => TaskType::YoutubeSubscribe->value,
                'title' => 'Instant live campaign',
                'target_url' => 'https://www.youtube.com/@earnpk',
                'target_quantity' => 100,
            ])
            ->assertRedirect(route('campaigns.mine'));

        $campaign = EarningTask::query()->campaigns()->sole();

        $this->assertTrue($campaign->isLiveCampaign());
        $this->assertNotNull($campaign->approved_at);

        $this->actingAs($viewer)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Admin Tasks')
            ->assertSee('Other Users Tasks')
            ->assertSee('Instant live campaign');

        $this->actingAs($viewer)
            ->post(route('tasks.start', $campaign))
            ->assertRedirect(route('tasks.run', $campaign));
    }

    public function test_the_tasks_page_does_not_repeat_the_owners_own_campaigns(): void
    {
        $owner = User::factory()->earner()->create();

        EarningTask::factory()->liveCampaign(100)->create([
            'user_id' => $owner->id,
            'title' => 'Owner only campaign',
        ]);

        $this->actingAs($owner)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertDontSee('Owner only campaign');
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
