<?php

use App\Models\SystemSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Earlier admin edits saved the same Monetag zone in more than one slot,
     * which leaves the later slots empty because ad networks serve a zone once.
     */
    public function up(): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        $keys = [
            'ad_auto_code',
            'ad_slot_top',
            'ad_slot_content',
            'ad_slot_bottom',
            'ad_slot_footer',
        ];

        $seenCodes = [];

        foreach ($keys as $key) {
            $code = trim((string) SystemSetting::getValue($key, ''));

            if ($code === '') {
                continue;
            }

            $normalizedCode = (string) preg_replace('/\s+/', ' ', $code);

            if (isset($seenCodes[$normalizedCode])) {
                SystemSetting::setValue($key, '', 'earning', 'Ad slot code: '.$key);

                continue;
            }

            $seenCodes[$normalizedCode] = true;
        }
    }

    /**
     * Duplicate ad codes are not restored because they prevented ads from rendering.
     */
    public function down(): void
    {
        //
    }
};
