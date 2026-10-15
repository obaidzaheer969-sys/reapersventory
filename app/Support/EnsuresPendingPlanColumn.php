<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class EnsuresPendingPlanColumn
{
    public static function run(): bool
    {
        try {
            if (! Schema::hasTable('subscriptions')) {
                return false;
            }

            if (Schema::hasColumn('subscriptions', 'pending_plan_id')) {
                return true;
            }

            // Prefer a plain nullable column first (FK can fail on some hosts).
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->unsignedBigInteger('pending_plan_id')->nullable();
            });

            try {
                Schema::table('subscriptions', function (Blueprint $table) {
                    $table->foreign('pending_plan_id')
                        ->references('id')
                        ->on('plans')
                        ->nullOnDelete();
                });
            } catch (Throwable) {
                // Column without FK is enough for package-change flow.
            }

            return Schema::hasColumn('subscriptions', 'pending_plan_id');
        } catch (Throwable) {
            return false;
        }
    }
}
