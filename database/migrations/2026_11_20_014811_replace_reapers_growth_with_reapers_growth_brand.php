<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('name', 'like', '%Reapers Growth%')
            ->update(['name' => DB::raw("REPLACE(name, 'Reapers Growth', 'ReapersGrowth')")]);

        DB::table('payment_methods')
            ->where('account_name', 'like', '%Reapers Growth%')
            ->update(['account_name' => DB::raw("REPLACE(account_name, 'Reapers Growth', 'ReapersGrowth')")]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('name', 'like', '%ReapersGrowth%')
            ->update(['name' => DB::raw("REPLACE(name, 'ReapersGrowth', 'Reapers Growth')")]);

        DB::table('payment_methods')
            ->where('account_name', 'like', '%ReapersGrowth%')
            ->update(['account_name' => DB::raw("REPLACE(account_name, 'ReapersGrowth', 'Reapers Growth')")]);
    }
};
