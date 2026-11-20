<?php

namespace Tests\Feature\Api;

use App\Enums\TaskAttemptStatus;
use App\Enums\TaskType;
use App\Enums\WalletTransactionType;
use App\Models\EarningTask;
use App\Models\SystemSetting;
use App\Models\TaskAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_hidden_admin_fees_are_never_exposed_to_earners(): void
    {
        SystemSetting::setValue('campaign_admin_fee_visible', false);

        $earner = User::factory()->earner()->create();
        $owner = User::factory()->earner()->create();
        $campaign = EarningTask::factory()->liveCampaign()->create([
            'user_id' => $owner->id,
            'task_type' => TaskType::YoutubeSubscribe,
            'reward' => 2,
            'target_quantity' => 100,
            'completed_quantity' => 0,
            'total_cost' => 300,
        ]);

        $this->actingAs($earner, 'sanctum')
            ->getJson('/api/v1/campaigns')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $campaign->id)
            ->assertJsonPath('data.0.admin_fee', 0)
            ->assertJsonPath('data.0.total_actions', 100)
            ->assertJsonPath('data.0.completed_actions', 0);
    }

    public function test_earner_cannot_work_on_their_own_campaign(): void
    {
        $owner = User::factory()->earner()->create();
        $campaign = EarningTask::factory()->liveCampaign()->create(['user_id' => $owner->id]);

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/campaigns')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->postJson("/api/v1/campaigns/{$campaign->id}/start", ['opened' => true])
            ->assertNotFound();
    }

    public function test_campaign_can_be_completed_once_and_credits_the_earner(): void
    {
        $earner = User::factory()->earner()->create();
        $owner = User::factory()->earner()->create(['wallet_balance' => 100]);
        $campaign = EarningTask::factory()->liveCampaign()->create([
            'user_id' => $owner->id,
            'task_type' => TaskType::YoutubeSubscribe,
            'reward' => 2,
            'target_quantity' => 100,
            'completed_quantity' => 0,
            'total_cost' => 300,
        ]);

        $this->actingAs($earner, 'sanctum')
            ->postJson("/api/v1/campaigns/{$campaign->id}/start", ['opened' => true])
            ->assertOk()
            ->assertJsonPath('attempt.status', TaskAttemptStatus::InProgress->value);

        $this->travel(16)->seconds();

        $this->postJson("/api/v1/campaigns/{$campaign->id}/complete", ['opened' => true])
            ->assertOk()
            ->assertJsonPath('attempt.status', TaskAttemptStatus::Completed->value);

        $this->assertSame(2.0, (float) $earner->fresh()->wallet_balance);
        $this->assertSame(1, (int) $campaign->fresh()->completed_quantity);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $earner->id,
            'type' => WalletTransactionType::TaskReward->value,
            'amount' => 2,
        ]);

        $this->postJson("/api/v1/campaigns/{$campaign->id}/start", ['opened' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('task');
    }

    public function test_non_embedded_campaign_waits_for_the_opened_endpoint_before_the_timer_starts(): void
    {
        $earner = User::factory()->earner()->create();
        $owner = User::factory()->earner()->create();
        $campaign = EarningTask::factory()->liveCampaign()->create([
            'user_id' => $owner->id,
            'task_type' => TaskType::YoutubeSubscribe,
            'duration_seconds' => 15,
            'reward' => 2,
            'target_quantity' => 100,
            'completed_quantity' => 0,
            'total_cost' => 300,
        ]);

        $this->freezeTime();

        $this->actingAs($earner, 'sanctum')
            ->postJson("/api/v1/campaigns/{$campaign->id}/start")
            ->assertOk()
            ->assertJsonPath('embedded', false)
            ->assertJsonPath('opened', false)
            ->assertJsonPath('timer_paused', true)
            ->assertJsonPath('can_claim', false)
            ->assertJsonPath('attempt.opened', false);

        $this->postJson("/api/v1/campaigns/{$campaign->id}/complete")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('task');

        $this->postJson("/api/v1/campaigns/{$campaign->id}/opened")
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'timer_paused' => false,
                'timer_remaining_seconds' => 15,
            ]);

        $this->travel(16)->seconds();

        $this->postJson("/api/v1/campaigns/{$campaign->id}/complete")
            ->assertOk()
            ->assertJsonPath('attempt.status', TaskAttemptStatus::Completed->value)
            ->assertJsonPath('opened', true)
            ->assertJsonPath('attempt.opened', true)
            ->assertJsonPath('attempt.watched_seconds', 15);

        $this->assertSame(2.0, (float) $earner->fresh()->wallet_balance);
        $this->assertSame(1, (int) $campaign->fresh()->completed_quantity);
        $this->assertDatabaseCount('wallet_transactions', 1);
    }

    public function test_embedded_campaign_requires_playback_progress_before_it_can_be_claimed(): void
    {
        $earner = User::factory()->earner()->create();
        $owner = User::factory()->earner()->create();
        $campaign = EarningTask::factory()->liveCampaign()->youtubeWatch(15, 2)->create([
            'user_id' => $owner->id,
            'target_quantity' => 100,
            'completed_quantity' => 0,
            'total_cost' => 300,
        ]);

        $this->freezeTime();

        $this->actingAs($earner, 'sanctum')
            ->postJson("/api/v1/campaigns/{$campaign->id}/start")
            ->assertOk()
            ->assertJsonPath('embedded', true)
            ->assertJsonPath('platform', 'youtube')
            ->assertJsonPath('timer_paused', true);

        $this->postJson("/api/v1/campaigns/{$campaign->id}/progress", ['playing' => true])
            ->assertOk()
            ->assertJsonPath('watched_seconds', 0)
            ->assertJsonPath('required_seconds', 15);

        $this->travel(6)->seconds();

        $this->postJson("/api/v1/campaigns/{$campaign->id}/progress", ['playing' => true])
            ->assertOk()
            ->assertJsonPath('watched_seconds', 6);

        $this->travel(10)->seconds();

        $this->postJson("/api/v1/campaigns/{$campaign->id}/progress", ['playing' => true])
            ->assertOk()
            ->assertJsonPath('watched_seconds', 15)
            ->assertJsonPath('can_claim', true);

        $this->travel(5)->seconds();

        $this->postJson("/api/v1/campaigns/{$campaign->id}/complete")
            ->assertOk()
            ->assertJsonPath('attempt.status', TaskAttemptStatus::Completed->value)
            ->assertJsonPath('attempt.watched_seconds', 15);

        $this->assertSame(2.0, (float) $earner->fresh()->wallet_balance);
        $this->assertSame(1, (int) $campaign->fresh()->completed_quantity);
        $this->assertDatabaseCount('wallet_transactions', 1);
    }

    public function test_campaign_resources_expose_timer_fields_for_the_mobile_player(): void
    {
        $earner = User::factory()->earner()->create();
        $owner = User::factory()->earner()->create();
        $campaign = EarningTask::factory()->liveCampaign()->youtubeWatch(20, 2)->create([
            'user_id' => $owner->id,
            'target_quantity' => 100,
            'completed_quantity' => 0,
            'total_cost' => 300,
        ]);

        $this->actingAs($earner, 'sanctum')
            ->getJson('/api/v1/campaigns')
            ->assertOk()
            ->assertJsonPath('data.0.id', $campaign->id)
            ->assertJsonPath('data.0.duration_seconds', 20)
            ->assertJsonPath('data.0.remaining_seconds', 20);

        $this->postJson("/api/v1/campaigns/{$campaign->id}/start")
            ->assertOk()
            ->assertJsonPath('timer_paused', true)
            ->assertJsonPath('remaining_seconds', 20);
    }

    public function test_campaign_proof_endpoints_return_404_for_another_users_attempt(): void
    {
        $earner = User::factory()->earner()->create();
        $otherUser = User::factory()->earner()->create();
        $owner = User::factory()->earner()->create();
        $campaign = EarningTask::factory()->liveCampaign()->create(['user_id' => $owner->id]);

        TaskAttempt::factory()->inProgress()->create([
            'user_id' => $otherUser->id,
            'earning_task_id' => $campaign->id,
        ]);

        $this->actingAs($earner, 'sanctum')
            ->postJson("/api/v1/campaigns/{$campaign->id}/opened")
            ->assertNotFound()
            ->assertJsonPath('message', 'Task attempt not found.');

        $this->postJson("/api/v1/campaigns/{$campaign->id}/progress", ['playing' => true])
            ->assertNotFound()
            ->assertJsonPath('message', 'Task attempt not found.');
    }

    public function test_own_campaign_proof_endpoints_return_404(): void
    {
        $owner = User::factory()->earner()->create();
        $campaign = EarningTask::factory()->liveCampaign()->create(['user_id' => $owner->id]);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/campaigns/{$campaign->id}/opened")
            ->assertNotFound();

        $this->postJson("/api/v1/campaigns/{$campaign->id}/progress", ['playing' => true])
            ->assertNotFound();
    }
}
