<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('earning_tasks')) {
            return;
        }

        if (! Schema::hasColumn('earning_tasks', 'user_id')) {
            Schema::table('earning_tasks', function (Blueprint $table) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('earning_tasks', 'campaign_status')) {
            Schema::table('earning_tasks', function (Blueprint $table) {
                $table->string('campaign_status', 20)->nullable()->after('is_active')->index();
            });
        }

        if (! Schema::hasColumn('earning_tasks', 'target_quantity')) {
            Schema::table('earning_tasks', function (Blueprint $table) {
                $table->unsignedInteger('target_quantity')->nullable()->after('campaign_status');
            });
        }

        if (! Schema::hasColumn('earning_tasks', 'completed_quantity')) {
            Schema::table('earning_tasks', function (Blueprint $table) {
                $table->unsignedInteger('completed_quantity')->default(0)->after('target_quantity');
            });
        }

        if (! Schema::hasColumn('earning_tasks', 'total_cost')) {
            Schema::table('earning_tasks', function (Blueprint $table) {
                $table->decimal('total_cost', 12, 2)->default(0)->after('completed_quantity');
            });
        }

        if (! Schema::hasColumn('earning_tasks', 'admin_notes')) {
            Schema::table('earning_tasks', function (Blueprint $table) {
                $table->text('admin_notes')->nullable()->after('total_cost');
            });
        }

        if (! Schema::hasColumn('earning_tasks', 'approved_at')) {
            Schema::table('earning_tasks', function (Blueprint $table) {
                $table->timestamp('approved_at')->nullable()->after('admin_notes');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('earning_tasks')) {
            return;
        }

        $columns = [
            'approved_at',
            'admin_notes',
            'total_cost',
            'completed_quantity',
            'target_quantity',
            'campaign_status',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn('earning_tasks', $column)) {
                Schema::table('earning_tasks', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        if (Schema::hasColumn('earning_tasks', 'user_id')) {
            Schema::table('earning_tasks', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }
    }
};
