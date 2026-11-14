<?php

namespace Tests\Feature\Earning;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_visible_announcements_are_shown_to_earners(): void
    {
        $user = User::factory()->earner()->create();

        Announcement::factory()->create([
            'title' => 'Instant payout bonus',
            'is_pinned' => true,
        ]);
        Announcement::factory()->inactive()->create(['title' => 'Hidden draft announcement']);
        Announcement::factory()->expired()->create(['title' => 'Hidden expired announcement']);
        Announcement::factory()->create([
            'title' => 'Hidden future announcement',
            'published_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Instant payout bonus')
            ->assertDontSee('Hidden draft announcement')
            ->assertDontSee('Hidden expired announcement')
            ->assertDontSee('Hidden future announcement');

        $this->actingAs($user)
            ->get(route('announcements.index'))
            ->assertOk()
            ->assertSee('Instant payout bonus')
            ->assertDontSee('Hidden draft announcement')
            ->assertDontSee('Hidden expired announcement')
            ->assertDontSee('Hidden future announcement');
    }

    public function test_pinned_announcements_are_listed_first(): void
    {
        $user = User::factory()->earner()->create();

        Announcement::factory()->create([
            'title' => 'Older regular update',
            'published_at' => now()->subDays(2),
        ]);
        Announcement::factory()->create([
            'title' => 'Important pinned update',
            'is_pinned' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->get(route('announcements.index'))
            ->assertOk()
            ->assertSeeInOrder(['Important pinned update', 'Older regular update']);
    }
}
