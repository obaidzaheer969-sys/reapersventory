<?php

namespace Tests\Feature\Api;

use App\Enums\TaskAttemptStatus;
use App\Enums\WalletTransactionType;
use App\Models\EarningTask;
use App\Models\TaskAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_earner_can_start_open_and_complete_a_task_after_the_timer_finishes(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeSubscribe(15, 25)->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/start", ['opened' => true])
            ->assertOk()
            ->assertJsonPath('attempt.status', TaskAttemptStatus::InProgress->value)
            ->assertJsonPath('status', 'in_progress');

        $this->postJson("/api/v1/tasks/{$task->id}/complete")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('task');

        $this->travel(16)->seconds();

        $this->postJson("/api/v1/tasks/{$task->id}/complete", ['opened' => true])
            ->assertOk()
            ->assertJsonPath('attempt.status', TaskAttemptStatus::Completed->value)
            ->assertJsonPath('status', 'completed');

        $this->assertSame(25.0, (float) $user->fresh()->wallet_balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'type' => WalletTransactionType::TaskReward->value,
            'amount' => 25,
            'reference_id' => $task->id,
        ]);
    }

    public function test_campaigns_cannot_be_started_through_the_system_task_endpoint(): void
    {
        $user = User::factory()->earner()->create();
        $owner = User::factory()->earner()->create();
        $campaign = EarningTask::factory()->liveCampaign()->create(['user_id' => $owner->id]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/tasks/{$campaign->id}/start", ['opened' => true])
            ->assertNotFound();
    }

    public function test_non_embedded_task_waits_for_the_opened_endpoint_before_the_timer_starts(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeSubscribe(15, 25)->create();

        $this->freezeTime();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/start")
            ->assertOk()
            ->assertJsonPath('embedded', false)
            ->assertJsonPath('embed_url', null)
            ->assertJsonPath('platform', null)
            ->assertJsonPath('opened', false)
            ->assertJsonPath('timer_paused', true)
            ->assertJsonPath('can_claim', false)
            ->assertJsonPath('attempt.opened', false)
            ->assertJsonPath('attempt.timer_paused', true)
            ->assertJsonPath('attempt.watched_seconds', 0);

        $this->postJson("/api/v1/tasks/{$task->id}/progress", ['playing' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('task');

        $this->postJson("/api/v1/tasks/{$task->id}/complete")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('task');

        $this->postJson("/api/v1/tasks/{$task->id}/opened")
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'timer_paused' => false,
                'timer_remaining_seconds' => 15,
            ]);

        $this->travel(16)->seconds();

        $this->postJson("/api/v1/tasks/{$task->id}/complete")
            ->assertOk()
            ->assertJsonPath('attempt.status', TaskAttemptStatus::Completed->value)
            ->assertJsonPath('opened', true)
            ->assertJsonPath('attempt.opened', true)
            ->assertJsonPath('attempt.watched_seconds', 15);

        $this->assertSame(25.0, (float) $user->fresh()->wallet_balance);
        $this->assertDatabaseCount('wallet_transactions', 1);
    }

    public function test_embedded_watch_task_requires_playback_progress_before_it_can_be_claimed(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeWatch(15, 2)->create();

        $this->freezeTime();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/start")
            ->assertOk()
            ->assertJsonPath('embedded', true)
            ->assertJsonPath('platform', 'youtube')
            ->assertJsonPath('timer_paused', true);

        $this->postJson("/api/v1/tasks/{$task->id}/progress", ['playing' => true])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'watched_seconds' => 0,
                'required_seconds' => 15,
                'remaining_watch_seconds' => 15,
                'timer_paused' => false,
            ]);

        $this->travel(6)->seconds();

        $this->postJson("/api/v1/tasks/{$task->id}/progress", ['playing' => true])
            ->assertOk()
            ->assertJsonPath('watched_seconds', 6)
            ->assertJsonPath('can_claim', false);

        $this->travel(10)->seconds();

        $this->postJson("/api/v1/tasks/{$task->id}/progress", ['playing' => true])
            ->assertOk()
            ->assertJsonPath('watched_seconds', 15)
            ->assertJsonPath('remaining_watch_seconds', 0)
            ->assertJsonPath('can_claim', true);

        $this->travel(5)->seconds();

        $this->postJson("/api/v1/tasks/{$task->id}/complete")
            ->assertOk()
            ->assertJsonPath('attempt.status', TaskAttemptStatus::Completed->value)
            ->assertJsonPath('attempt.watched_seconds', 15);

        $this->assertSame(2.0, (float) $user->fresh()->wallet_balance);
        $this->assertDatabaseCount('wallet_transactions', 1);
    }

    public function test_embedded_task_asks_for_playback_before_showing_a_paused_timer_error(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeWatch(20, 2)->create();

        $this->freezeTime();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/start", ['opened' => true])
            ->assertOk()
            ->assertJsonPath('timer_paused', true)
            ->assertJsonPath('remaining_seconds', 20);

        $this->postJson("/api/v1/tasks/{$task->id}/complete")
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.task.0',
                'Play the video first — watch time will not count without playback.',
            );
    }

    public function test_embedded_task_countdown_follows_five_second_mobile_heartbeats(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeWatch(20, 2)->create();

        $this->freezeTime();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/start")
            ->assertOk()
            ->assertJsonPath('timer_paused', true)
            ->assertJsonPath('remaining_seconds', 20);

        $this->postJson("/api/v1/tasks/{$task->id}/progress", ['playing' => true])
            ->assertOk()
            ->assertJsonPath('timer_paused', false)
            ->assertJsonPath('timer_remaining_seconds', 20)
            ->assertJsonPath('watched_seconds', 0);

        foreach ([15, 10, 5, 0] as $expectedRemaining) {
            $this->travel(5)->seconds();

            $this->postJson("/api/v1/tasks/{$task->id}/progress", ['playing' => true])
                ->assertOk()
                ->assertJsonPath('timer_remaining_seconds', $expectedRemaining)
                ->assertJsonPath('watched_seconds', 20 - $expectedRemaining)
                ->assertJsonPath('can_claim', $expectedRemaining === 0);
        }

        $this->postJson("/api/v1/tasks/{$task->id}/complete", ['playing' => true])
            ->assertOk()
            ->assertJsonPath('attempt.status', TaskAttemptStatus::Completed->value)
            ->assertJsonPath('attempt.watched_seconds', 20);

        $this->assertSame(2.0, (float) $user->fresh()->wallet_balance);
        $this->assertDatabaseCount('wallet_transactions', 1);
    }

    public function test_embedded_task_paused_timer_error_explains_how_to_resume(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeWatch(20, 2)->create();

        $this->freezeTime();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/start")
            ->assertOk();

        $this->postJson("/api/v1/tasks/{$task->id}/progress", ['playing' => true])
            ->assertOk();

        $this->postJson("/api/v1/tasks/{$task->id}/progress", ['playing' => false])
            ->assertOk()
            ->assertJsonPath('timer_paused', true)
            ->assertJsonPath('timer_remaining_seconds', 20);

        $this->postJson("/api/v1/tasks/{$task->id}/complete")
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.task.0',
                'The timer is paused because playback is paused. Press play to resume it.',
            );
    }

    public function test_proof_endpoints_return_404_when_the_attempt_belongs_to_another_user(): void
    {
        $user = User::factory()->earner()->create();
        $otherUser = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeSubscribe(15, 25)->create();

        TaskAttempt::factory()->inProgress()->create([
            'user_id' => $otherUser->id,
            'earning_task_id' => $task->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/tasks/{$task->id}/opened")
            ->assertNotFound()
            ->assertJsonPath('message', 'Task attempt not found.');

        $this->postJson("/api/v1/tasks/{$task->id}/progress", ['playing' => true])
            ->assertNotFound()
            ->assertJsonPath('message', 'Task attempt not found.');
    }
}
