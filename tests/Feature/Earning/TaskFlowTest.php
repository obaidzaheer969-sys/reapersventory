<?php

namespace Tests\Feature\Earning;

use App\Enums\TaskAttemptStatus;
use App\Enums\WalletTransactionType;
use App\Models\EarningTask;
use App\Models\TaskAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_reward_is_only_credited_after_the_timer_finishes(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeSubscribe(15, 25)->create();

        $this->actingAs($user)
            ->post(route('tasks.start', $task))
            ->assertRedirect(route('tasks.run', $task));

        $attempt = TaskAttempt::query()->where('user_id', $user->id)->sole();

        $this->assertSame(TaskAttemptStatus::InProgress, $attempt->status);
        $this->assertNull($attempt->completed_at);

        $this->actingAs($user)
            ->post(route('tasks.complete', $task))
            ->assertSessionHasErrors('task');

        $this->assertSame(0.0, (float) $user->fresh()->wallet_balance);
        $this->assertSame(TaskAttemptStatus::InProgress, $attempt->fresh()->status);

        $this->travel(16)->seconds();

        $this->actingAs($user)
            ->post(route('tasks.complete', $task))
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHas('status');

        $attempt->refresh();

        $this->assertSame(TaskAttemptStatus::Completed, $attempt->status);
        $this->assertSame(15, $attempt->watched_seconds);
        $this->assertSame(25.0, (float) $attempt->reward_amount);
        $this->assertSame(25.0, (float) $user->fresh()->wallet_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'type' => WalletTransactionType::TaskReward->value,
            'amount' => 25,
        ]);
    }

    public function test_a_task_cannot_be_started_twice_on_the_same_day(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeSubscribe()->create();

        TaskAttempt::factory()->create([
            'user_id' => $user->id,
            'earning_task_id' => $task->id,
            'completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('tasks.start', $task))
            ->assertSessionHasErrors('task');

        $this->assertSame(1, TaskAttempt::query()->where('user_id', $user->id)->count());
    }

    public function test_a_task_unlocks_again_after_the_daily_reset(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeSubscribe()->create();

        TaskAttempt::factory()->create([
            'user_id' => $user->id,
            'earning_task_id' => $task->id,
            'completed_at' => now()->subDay(),
            'started_at' => now()->subDay()->subMinutes(5),
            'expires_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->post(route('tasks.start', $task))
            ->assertRedirect(route('tasks.run', $task));

        $attempts = TaskAttempt::query()->where('user_id', $user->id)->get();

        $this->assertCount(2, $attempts);
        $this->assertSame(1, $attempts->where('status', TaskAttemptStatus::InProgress)->count());
    }

    public function test_an_inactive_task_cannot_be_started(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->inactive()->create();

        $this->actingAs($user)
            ->post(route('tasks.start', $task))
            ->assertSessionHasErrors('task');

        $this->assertDatabaseCount('task_attempts', 0);
    }
}
