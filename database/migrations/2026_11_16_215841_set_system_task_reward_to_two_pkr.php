<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Every admin (system) task pays a flat 2 PKR reward.
     */
    public function up(): void
    {
        DB::table('earning_tasks')
            ->whereNull('campaign_status')
            ->update(['reward' => 2]);
    }

    /**
     * The previous reward values are not recoverable, so nothing to roll back.
     */
    public function down(): void
    {
        //
    }
};
