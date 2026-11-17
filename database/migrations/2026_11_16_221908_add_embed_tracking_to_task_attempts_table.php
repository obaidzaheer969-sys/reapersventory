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

        if (! Schema::hasColumn('task_attempts', 'embed_played_at')) {
            Schema::table('task_attempts', function (Blueprint $table) {
                $table->timestamp('embed_played_at')->nullable()->after('opened_at');
            });
        }

        if (! Schema::hasColumn('task_attempts', 'embed_seen_at')) {
            Schema::table('task_attempts', function (Blueprint $table) {
                $table->timestamp('embed_seen_at')->nullable()->after('embed_played_at');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('task_attempts')) {
            return;
        }

        if (Schema::hasColumn('task_attempts', 'embed_seen_at')) {
            Schema::table('task_attempts', function (Blueprint $table) {
                $table->dropColumn('embed_seen_at');
            });
        }

        if (Schema::hasColumn('task_attempts', 'embed_played_at')) {
            Schema::table('task_attempts', function (Blueprint $table) {
                $table->dropColumn('embed_played_at');
            });
        }
    }
};
