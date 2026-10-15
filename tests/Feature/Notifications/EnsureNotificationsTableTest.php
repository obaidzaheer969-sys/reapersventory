<?php

namespace Tests\Feature\Notifications;

use App\Support\EnsuresNotificationsTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EnsureNotificationsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_ensure_helper_creates_notifications_table_when_missing(): void
    {
        Schema::dropIfExists('notifications');
        $this->assertFalse(Schema::hasTable('notifications'));

        $this->assertTrue(EnsuresNotificationsTable::run());
        $this->assertTrue(Schema::hasTable('notifications'));
        $this->assertTrue(EnsuresNotificationsTable::run());
    }
}
