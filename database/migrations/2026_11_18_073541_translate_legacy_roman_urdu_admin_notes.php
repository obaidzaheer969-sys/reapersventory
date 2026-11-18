<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, array<string, string>>
     */
    private const TRANSLATIONS = [
        'deposits' => [
            'Cashmaal payment cancel ho gayi.' => 'The Cashmaal payment was cancelled.',
            'Cashmaal payment reject ho gayi.' => 'The Cashmaal payment was rejected.',
            'Cashmaal payment cancel ho gayi. Koi paisa wallet se nahi kata gaya.' => 'The Cashmaal payment was cancelled. No money was deducted from your wallet.',
        ],
        'earning_tasks' => [
            'Cashmaal payment cancel ho gayi.' => 'The Cashmaal payment was cancelled.',
            'Cashmaal payment reject ho gayi.' => 'The Cashmaal payment was rejected.',
        ],
        'payout_requests' => [
            'Cashmaal payment cancel ho gayi.' => 'The Cashmaal payment was cancelled.',
            'Cashmaal payment reject ho gayi.' => 'The Cashmaal payment was rejected.',
        ],
    ];

    public function up(): void
    {
        foreach (self::TRANSLATIONS as $table => $translations) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($translations as $legacyText => $englishText) {
                DB::table($table)
                    ->where('admin_notes', $legacyText)
                    ->update(['admin_notes' => $englishText]);
            }
        }
    }

    /**
     * The legacy Roman Urdu values are deliberately not restored.
     */
    public function down(): void
    {
        //
    }
};
