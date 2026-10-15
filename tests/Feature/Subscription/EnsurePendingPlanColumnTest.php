<?php

namespace Tests\Feature\Subscription;

use App\Support\EnsuresPendingPlanColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EnsurePendingPlanColumnTest extends TestCase
{
    use RefreshDatabase;

    public function test_ensure_helper_reports_pending_plan_column_ready(): void
    {
        $this->assertTrue(Schema::hasColumn('subscriptions', 'pending_plan_id'));
        $this->assertTrue(EnsuresPendingPlanColumn::run());
    }
}
