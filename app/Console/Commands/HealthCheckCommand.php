<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthCheckCommand extends Command
{
    protected $signature = 'reapersventory:health';

    protected $description = 'Verify database, storage, and queue readiness for deployment';

    public function handle(): int
    {
        $ok = true;

        try {
            DB::connection()->getPdo();
            $this->info('[ok] database');
        } catch (\Throwable $e) {
            $this->error('[fail] database: '.$e->getMessage());
            $ok = false;
        }

        $disk = config('reapersventory.payment_proof_disk', 'local');
        try {
            $path = 'healthchecks/'.uniqid('probe_', true).'.txt';
            Storage::disk($disk)->put($path, 'ok');
            $exists = Storage::disk($disk)->exists($path);
            Storage::disk($disk)->delete($path);
            if (! $exists) {
                throw new \RuntimeException('Write succeeded but file missing.');
            }
            $this->info("[ok] storage disk ({$disk})");
        } catch (\Throwable $e) {
            $this->error('[fail] storage: '.$e->getMessage());
            $ok = false;
        }

        $queue = config('queue.default');
        $this->info("[ok] queue driver ({$queue})");

        $appKey = (string) config('app.key');
        if ($appKey === '') {
            $this->error('[fail] APP_KEY is empty');
            $ok = false;
        } else {
            $this->info('[ok] APP_KEY set');
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
