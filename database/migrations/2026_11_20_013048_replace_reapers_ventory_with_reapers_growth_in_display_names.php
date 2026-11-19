<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('name', 'like', '%ReapersVentory%')
            ->update(['name' => DB::raw("REPLACE(name, 'ReapersVentory', 'Reapers Growth')")]);

        DB::table('payment_methods')
            ->where('account_name', 'like', '%ReapersVentory%')
            ->update(['account_name' => DB::raw("REPLACE(account_name, 'ReapersVentory', 'Reapers Growth')")]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('name', 'like', '%Reapers Growth%')
            ->update(['name' => DB::raw("REPLACE(name, 'Reapers Growth', 'ReapersVentory')")]);

        DB::table('payment_methods')
            ->where('account_name', 'like', '%Reapers Growth%')
            ->update(['account_name' => DB::raw("REPLACE(account_name, 'Reapers Growth', 'ReapersVentory')")]);
    }
};
