<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_notifications_table_and_data_survive_the_migration(): void
    {
        $notificationId = '00000000-0000-4000-8000-000000000001';

        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'test',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => 1,
            'data' => '{}',
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_10_15_014232_create_notifications_table.php');
        $migration->up();
        $migration->down();

        $this->assertDatabaseHas('notifications', [
            'id' => $notificationId,
            'data' => '{}',
        ]);
    }
}
