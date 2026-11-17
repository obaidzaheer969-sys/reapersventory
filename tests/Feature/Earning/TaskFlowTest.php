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
        $this->assertNull($attempt->opened_at);

        $this->actingAs($user)
            ->post(route('tasks.complete', $task))
            ->assertSessionHasErrors('task');

        $this->assertSame(0.0, (float) $user->fresh()->wallet_balance);
        $this->assertSame(TaskAttemptStatus::InProgress, $attempt->fresh()->status);

        $this->actingAs($user)
            ->postJson(route('tasks.opened', $task))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertNotNull($attempt->fresh()->opened_at);

        $this->actingAs($user)
            ->post(route('tasks.complete', $task))
            ->assertSessionHasErrors('task');

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

    public function test_the_task_tab_stays_locked_until_the_timer_finishes(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeSubscribe(30, 20)->create();

        $this->actingAs($user)->post(route('tasks.start', $task));

        $this->actingAs($user)
            ->get(route('tasks.run', $task))
            ->assertOk()
            ->assertSee('Timer poora hone tak tab band karna aur back jana band hai.');

        $this->travel(31)->seconds();

        $this->actingAs($user)
            ->get(route('tasks.run', $task))
            ->assertOk()
            ->assertDontSee('Timer poora hone tak tab band karna aur back jana band hai.');
    }

    public function test_the_task_opens_the_real_page_in_a_new_tab(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeSubscribe(30, 20)->create([
            'target_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);

        $this->actingAs($user)->post(route('tasks.start', $task));

        $this->actingAs($user)
            ->get(route('tasks.run', $task))
            ->assertOk()
            ->assertDontSee('<iframe', false)
            ->assertSee('Open real page', false)
            ->assertSee('https://www.youtube.com/watch?v=dQw4w9WgXcQ', false)
            ->assertSee('Timer poora hone tak tab band karna aur back jana band hai.');
    }

    public function test_a_task_cannot_be_claimed_without_opening_the_real_page(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeSubscribe(15, 25)->create();

        $this->actingAs($user)->post(route('tasks.start', $task));

        $this->travel(16)->seconds();

        $this->actingAs($user)
            ->post(route('tasks.complete', $task))
            ->assertSessionHasErrors('task');

        $attempt = TaskAttempt::query()->where('user_id', $user->id)->sole();

        $this->assertSame(TaskAttemptStatus::InProgress, $attempt->status);
        $this->assertSame(0.0, (float) $user->fresh()->wallet_balance);
    }

    public function test_closing_the_real_page_before_the_timer_fails_the_task(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeSubscribe(60, 25)->create();

        $this->actingAs($user)->post(route('tasks.start', $task));
        $this->actingAs($user)->postJson(route('tasks.opened', $task))->assertOk();

        $this->actingAs($user)
            ->postJson(route('tasks.fail', $task))
            ->assertOk()
            ->assertJson(['ok' => true, 'failed' => true]);

        $attempt = TaskAttempt::query()->where('user_id', $user->id)->sole();

        $this->assertSame(TaskAttemptStatus::Failed, $attempt->status);
        $this->assertNotNull($attempt->failed_at);

        $this->travel(61)->seconds();

        $this->actingAs($user)
            ->post(route('tasks.complete', $task))
            ->assertSessionHasErrors('task');

        $this->assertSame(0.0, (float) $user->fresh()->wallet_balance);
        $this->assertSame(TaskAttemptStatus::Failed, $attempt->fresh()->status);

        $this->actingAs($user)
            ->post(route('tasks.start', $task))
            ->assertRedirect(route('tasks.run', $task));

        $fresh = TaskAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', TaskAttemptStatus::InProgress->value)
            ->sole();

        $this->assertNotSame($attempt->id, $fresh->id);
    }

    public function test_closing_the_real_page_after_the_timer_does_not_fail_the_task(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeSubscribe(15, 25)->create();

        $this->actingAs($user)->post(route('tasks.start', $task));
        $this->actingAs($user)->postJson(route('tasks.opened', $task))->assertOk();

        $this->travel(16)->seconds();

        $this->actingAs($user)
            ->postJson(route('tasks.fail', $task))
            ->assertOk()
            ->assertJson(['ok' => true, 'failed' => false]);

        $this->actingAs($user)
            ->post(route('tasks.complete', $task))
            ->assertRedirect(route('tasks.index'));

        $this->assertSame(25.0, (float) $user->fresh()->wallet_balance);
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

    public function test_a_youtube_watch_task_renders_the_official_embed_instead_of_a_new_tab(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeWatch(30)->create();

        $this->actingAs($user)->post(route('tasks.start', $task));

        $this->actingAs($user)
            ->get(route('tasks.run', $task))
            ->assertOk()
            ->assertSee('<iframe', false)
            ->assertSee('https://www.youtube.com/embed/dQw4w9WgXcQ', false)
            ->assertSee('enablejsapi=1', false)
            ->assertDontSee('<button type="button" data-open-real', false)
            ->assertSee('data-watch-seconds', false);
    }

    public function test_a_facebook_watch_task_renders_the_official_facebook_embed(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->facebookWatch(30)->create();

        $this->actingAs($user)->post(route('tasks.start', $task));

        $this->actingAs($user)
            ->get(route('tasks.run', $task))
            ->assertOk()
            ->assertSee('fb-video', false)
            ->assertSee('connect.facebook.net', false)
            ->assertSee('facebook.com/plugins/video.php', false)
            ->assertDontSee('<button type="button" data-open-real', false);
    }

    public function test_an_embedded_watch_task_cannot_be_claimed_after_the_timer_without_playback(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeWatch(15, 2)->create();

        $this->actingAs($user)->post(route('tasks.start', $task));

        $this->travel(16)->seconds();

        $this->actingAs($user)
            ->post(route('tasks.complete', $task))
            ->assertSessionHasErrors('task');

        $attempt = TaskAttempt::query()->where('user_id', $user->id)->sole();

        $this->assertSame(TaskAttemptStatus::InProgress, $attempt->status);
        $this->assertNull($attempt->embed_played_at);
        $this->assertSame(0.0, (float) $user->fresh()->wallet_balance);
    }

    public function test_embedded_watch_time_only_comes_from_server_measured_playback_heartbeats(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeWatch(15, 2)->create();

        $this->actingAs($user)->post(route('tasks.start', $task));

        $this->actingAs($user)
            ->postJson(route('tasks.progress', $task), ['playing' => true])
            ->assertOk()
            ->assertJson(['watched_seconds' => 0, 'required_seconds' => 15]);

        for ($i = 0; $i < 8; $i++) {
            $this->actingAs($user)
                ->postJson(route('tasks.progress', $task), ['playing' => true])
                ->assertOk()
                ->assertJson(['watched_seconds' => 0]);
        }

        $this->travel(4)->seconds();

        $this->actingAs($user)
            ->postJson(route('tasks.progress', $task), ['playing' => true])
            ->assertOk()
            ->assertJson(['watched_seconds' => 4]);

        $this->travel(4)->seconds();

        $this->actingAs($user)
            ->postJson(route('tasks.progress', $task), ['playing' => true])
            ->assertOk()
            ->assertJson(['watched_seconds' => 8]);

        $attempt = TaskAttempt::query()->where('user_id', $user->id)->sole();

        $this->assertNotNull($attempt->embed_played_at);
        $this->assertSame(8, $attempt->watched_seconds);
    }

    public function test_pausing_the_embedded_player_stops_watch_time_credit(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeWatch(30, 2)->create();

        $this->actingAs($user)->post(route('tasks.start', $task));

        $this->actingAs($user)->postJson(route('tasks.progress', $task), ['playing' => true])->assertOk();

        $this->travel(5)->seconds();

        $this->actingAs($user)
            ->postJson(route('tasks.progress', $task), ['playing' => true])
            ->assertJson(['watched_seconds' => 5]);

        $this->actingAs($user)
            ->postJson(route('tasks.progress', $task), ['playing' => false])
            ->assertJson(['watched_seconds' => 5]);

        $this->travel(10)->seconds();

        $this->actingAs($user)
            ->postJson(route('tasks.progress', $task), ['playing' => true])
            ->assertJson(['watched_seconds' => 5]);

        $this->travel(5)->seconds();

        $this->actingAs($user)
            ->postJson(route('tasks.progress', $task), ['playing' => true])
            ->assertJson(['watched_seconds' => 10]);
    }

    public function test_an_embedded_watch_task_pays_after_the_timer_and_required_watch_time(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeWatch(15, 2)->create();

        $this->actingAs($user)->post(route('tasks.start', $task));

        $this->actingAs($user)->postJson(route('tasks.progress', $task), ['playing' => true])->assertOk();

        $this->travel(5)->seconds();
        $this->actingAs($user)->postJson(route('tasks.progress', $task), ['playing' => true])->assertOk();

        $this->travel(5)->seconds();
        $this->actingAs($user)->postJson(route('tasks.progress', $task), ['playing' => true])->assertOk();

        $this->travel(5)->seconds();
        $this->actingAs($user)
            ->postJson(route('tasks.progress', $task), ['playing' => true])
            ->assertOk()
            ->assertJson(['watched_seconds' => 15, 'can_claim' => true]);

        $this->actingAs($user)
            ->post(route('tasks.complete', $task))
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHas('status');

        $attempt = TaskAttempt::query()->where('user_id', $user->id)->sole();

        $this->assertSame(TaskAttemptStatus::Completed, $attempt->status);
        $this->assertSame(15, $attempt->watched_seconds);
        $this->assertSame(2.0, (float) $attempt->reward_amount);
        $this->assertSame(2.0, (float) $user->fresh()->wallet_balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'type' => WalletTransactionType::TaskReward->value,
            'amount' => 2,
        ]);
    }

    public function test_an_embedded_watch_task_can_be_claimed_only_after_the_required_watch_time_even_if_the_timer_is_done(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeWatch(10, 2)->create();

        $this->actingAs($user)->post(route('tasks.start', $task));
        $this->actingAs($user)->postJson(route('tasks.progress', $task), ['playing' => true])->assertOk();

        $this->travel(6)->seconds();
        $this->actingAs($user)
            ->postJson(route('tasks.progress', $task), ['playing' => true])
            ->assertOk()
            ->assertJson(['watched_seconds' => 6]);

        $this->travel(5)->seconds();

        $this->actingAs($user)
            ->post(route('tasks.complete', $task))
            ->assertSessionHasErrors('task');

        $this->assertSame(0.0, (float) $user->fresh()->wallet_balance);

        $this->travel(4)->seconds();
        $this->actingAs($user)
            ->postJson(route('tasks.progress', $task), ['playing' => true])
            ->assertOk()
            ->assertJson(['watched_seconds' => 10]);

        $this->actingAs($user)
            ->post(route('tasks.complete', $task))
            ->assertRedirect(route('tasks.index'));

        $this->assertSame(2.0, (float) $user->fresh()->wallet_balance);
    }

    public function test_a_watch_task_with_an_unsupported_url_keeps_the_real_page_flow(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeWatch(10, 2)->create([
            'target_url' => 'https://www.youtube.com/@earnpk',
        ]);

        $this->actingAs($user)->post(route('tasks.start', $task));

        $this->actingAs($user)
            ->get(route('tasks.run', $task))
            ->assertOk()
            ->assertSee('<button type="button" data-open-real', false)
            ->assertDontSee('youtube.com/embed', false);

        $this->actingAs($user)->post(route('tasks.opened', $task));

        $this->travel(11)->seconds();

        $this->actingAs($user)
            ->post(route('tasks.complete', $task))
            ->assertRedirect(route('tasks.index'));

        $this->assertSame(2.0, (float) $user->fresh()->wallet_balance);
    }

    public function test_a_spoofed_video_host_does_not_render_an_official_embed(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeWatch(10, 2)->create([
            'target_url' => 'https://youtube.com.evil.example/watch?v=dQw4w9WgXcQ',
        ]);

        $this->actingAs($user)->post(route('tasks.start', $task));

        $this->actingAs($user)
            ->get(route('tasks.run', $task))
            ->assertOk()
            ->assertSee('<button type="button" data-open-real', false)
            ->assertDontSee('youtube.com/embed', false);
    }

    public function test_an_embedded_attempt_failed_before_the_timer_cannot_be_claimed(): void
    {
        $user = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeWatch(15, 2)->create();

        $this->actingAs($user)->post(route('tasks.start', $task));
        $this->actingAs($user)->postJson(route('tasks.progress', $task), ['playing' => true])->assertOk();

        $this->actingAs($user)->post(route('tasks.fail', $task));

        $attempt = TaskAttempt::query()->where('user_id', $user->id)->sole();

        $this->assertSame(TaskAttemptStatus::Failed, $attempt->status);
        $this->assertNotNull($attempt->failed_at);

        $this->travel(16)->seconds();

        $this->actingAs($user)
            ->post(route('tasks.complete', $task))
            ->assertSessionHasErrors('task');

        $this->assertSame(0.0, (float) $user->fresh()->wallet_balance);
    }

    public function test_a_user_cannot_credit_watch_time_to_another_users_attempt(): void
    {
        $owner = User::factory()->earner()->create();
        $intruder = User::factory()->earner()->create();
        $task = EarningTask::factory()->youtubeWatch(15, 2)->create();

        $this->actingAs($owner)->post(route('tasks.start', $task));

        $this->actingAs($intruder)
            ->postJson(route('tasks.progress', $task), ['playing' => true])
            ->assertNotFound();

        $attempt = TaskAttempt::query()->where('user_id', $owner->id)->sole();

        $this->assertSame(0, $attempt->watched_seconds);
        $this->assertNull($attempt->embed_played_at);
    }
}
