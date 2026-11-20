<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_earner_sees_only_their_notifications_and_can_mark_one_read(): void
    {
        $user = User::factory()->earner()->create();
        $otherUser = User::factory()->earner()->create();

        $ownNotification = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test',
            'data' => ['title' => 'Task complete', 'body' => 'Your reward was credited.'],
            'created_at' => now(),
        ]);

        $foreignNotification = $otherUser->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test',
            'data' => ['title' => 'Private notice', 'body' => 'Not yours.'],
            'created_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownNotification->id)
            ->assertJsonPath('data.0.title', 'Task complete')
            ->assertJsonPath('meta.unread_count', 1);

        $this->postJson("/api/v1/notifications/{$ownNotification->id}/read")
            ->assertOk()
            ->assertJsonPath('id', $ownNotification->id);

        $this->assertNotNull($ownNotification->fresh()->read_at);
        $this->assertNull($foreignNotification->fresh()->read_at);
    }

    public function test_earner_cannot_mark_another_users_notification_as_read(): void
    {
        $user = User::factory()->earner()->create();
        $otherUser = User::factory()->earner()->create();

        $foreignNotification = $otherUser->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test',
            'data' => ['title' => 'Private notice', 'body' => 'Not yours.'],
            'created_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/notifications/{$foreignNotification->id}/read")
            ->assertNotFound();

        $this->assertNull($foreignNotification->fresh()->read_at);
    }
}
