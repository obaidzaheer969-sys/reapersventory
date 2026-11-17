<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('task_attempts')) {
            return;
        }

        if (! Schema::hasColumn('task_attempts', 'timer_paused_at')) {
            Schema::table('task_attempts', function (Blueprint $table) {
                $table->timestamp('timer_paused_at')->nullable()->after('expires_at');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('task_attempts')) {
            return;
        }

        if (Schema::hasColumn('task_attempts', 'timer_paused_at')) {
            Schema::table('task_attempts', function (Blueprint $table) {
                $table->dropColumn('timer_paused_at');
            });
        }
    }
};
