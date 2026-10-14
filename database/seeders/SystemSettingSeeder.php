<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        SystemSetting::setValue(
            'subscription_grace_period_days',
            (int) config('reapersventory.grace_period_days', 7),
            'billing',
            'Days of grace after a paid period ends before access is revoked.'
        );
    }
}
