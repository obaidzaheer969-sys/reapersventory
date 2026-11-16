<?php

namespace Database\Seeders;

use App\Enums\TaskType;
use App\Models\EarningTask;
use Illuminate\Database\Seeder;

class EarningTaskSeeder extends Seeder
{
    public function run(): void
    {
        $tasks = [
            [
                'title' => 'Subscribe to our YouTube channel',
                'task_type' => TaskType::YoutubeSubscribe,
                'target_url' => 'https://www.youtube.com/@EarnPK',
                'duration_seconds' => TaskType::YoutubeSubscribe->defaultSeconds(),
                'reward' => 2,
                'instructions' => 'Open the channel, tap Subscribe, then return here to claim your reward.',
                'sort_order' => 1,
            ],
            [
                'title' => 'Watch a YouTube video',
                'task_type' => TaskType::YoutubeWatch,
                'target_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'duration_seconds' => 30,
                'reward' => 2,
                'instructions' => 'Watch the video until the timer finishes, then claim your reward.',
                'sort_order' => 2,
            ],
            [
                'title' => 'Follow our Facebook page',
                'task_type' => TaskType::FacebookFollow,
                'target_url' => 'https://www.facebook.com/EarnPK',
                'duration_seconds' => TaskType::FacebookFollow->defaultSeconds(),
                'reward' => 2,
                'instructions' => 'Open the page, tap Follow, then return here to claim your reward.',
                'sort_order' => 3,
            ],
            [
                'title' => 'Watch a Facebook video',
                'task_type' => TaskType::FacebookWatch,
                'target_url' => 'https://www.facebook.com/watch',
                'duration_seconds' => 30,
                'reward' => 2,
                'instructions' => 'Watch the video until the timer finishes, then claim your reward.',
                'sort_order' => 4,
            ],
            [
                'title' => 'Follow us on Instagram',
                'task_type' => TaskType::InstagramFollow,
                'target_url' => 'https://www.instagram.com/earnpk',
                'duration_seconds' => TaskType::InstagramFollow->defaultSeconds(),
                'reward' => 2,
                'instructions' => 'Open the profile, tap Follow, then return here to claim your reward.',
                'sort_order' => 5,
            ],
            [
                'title' => 'Watch an Instagram video',
                'task_type' => TaskType::InstagramWatch,
                'target_url' => 'https://www.instagram.com/reels',
                'duration_seconds' => 30,
                'reward' => 2,
                'instructions' => 'Watch the video until the timer finishes, then claim your reward.',
                'sort_order' => 6,
            ],
            [
                'title' => 'Follow our WhatsApp channel',
                'task_type' => TaskType::WhatsappChannel,
                'target_url' => 'https://whatsapp.com/channel/earnpk',
                'duration_seconds' => TaskType::WhatsappChannel->defaultSeconds(),
                'reward' => 2,
                'instructions' => 'Open the channel, tap Follow, then return here to claim your reward.',
                'sort_order' => 7,
            ],
            [
                'title' => 'Watch a sponsored ad',
                'task_type' => TaskType::AdWatch,
                'target_url' => 'https://example.com/sponsored',
                'duration_seconds' => 30,
                'reward' => 2,
                'instructions' => 'Watch the ad until the timer finishes, then claim your reward.',
                'sort_order' => 8,
            ],
        ];

        foreach ($tasks as $task) {
            EarningTask::query()->updateOrCreate(
                ['title' => $task['title']],
                array_merge($task, ['is_active' => true]),
            );
        }
    }
}
