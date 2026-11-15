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

        if (! Schema::hasColumn('task_attempts', 'opened_at')) {
            Schema::table('task_attempts', function (Blueprint $table) {
                $table->timestamp('opened_at')->nullable()->after('started_at');
            });
        }

        if (! Schema::hasColumn('task_attempts', 'failed_at')) {
            Schema::table('task_attempts', function (Blueprint $table) {
                $table->timestamp('failed_at')->nullable()->after('completed_at');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('task_attempts')) {
            return;
        }

        if (Schema::hasColumn('task_attempts', 'opened_at')) {
            Schema::table('task_attempts', function (Blueprint $table) {
                $table->dropColumn('opened_at');
            });
        }

        if (Schema::hasColumn('task_attempts', 'failed_at')) {
            Schema::table('task_attempts', function (Blueprint $table) {
                $table->dropColumn('failed_at');
            });
        }
    }
};
