<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class VerifyExistingUsersCommand extends Command
{
    protected $signature = 'reapersventory:verify-all-users';

    protected $description = 'Mark all users as email-verified (for hosts where activation mail never worked)';

    public function handle(): int
    {
        $count = User::query()
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);

        $this->info("Verified {$count} user(s).");

        return self::SUCCESS;
    }
}
