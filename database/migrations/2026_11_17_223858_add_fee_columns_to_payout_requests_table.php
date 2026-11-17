<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payout_requests')) {
            return;
        }

        Schema::table('payout_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('payout_requests', 'gross_amount')) {
                $table->decimal('gross_amount', 12, 2)->nullable()->after('amount');
            }

            if (! Schema::hasColumn('payout_requests', 'fee')) {
                $table->decimal('fee', 12, 2)->default(0)->after('gross_amount');
            }
        });

        DB::table('payout_requests')
            ->whereNull('gross_amount')
            ->update(['gross_amount' => DB::raw('amount')]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('payout_requests')) {
            return;
        }

        Schema::table('payout_requests', function (Blueprint $table): void {
            $table->dropColumn(['gross_amount', 'fee']);
        });
    }
};
