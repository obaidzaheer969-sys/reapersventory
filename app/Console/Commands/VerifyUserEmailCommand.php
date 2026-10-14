<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class VerifyUserEmailCommand extends Command
{
    protected $signature = 'reapersventory:verify-user {email : User email to mark as verified}';

    protected $description = 'Manually mark a user email as verified (hosting fallback when SMTP fails)';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error("No user found for {$email}");

            return self::FAILURE;
        }

        if ($user->hasVerifiedEmail()) {
            $this->info("Already verified: {$email}");

            return self::SUCCESS;
        }

        $user->forceFill(['email_verified_at' => now()])->save();
        $this->info("Verified: {$email}");

        return self::SUCCESS;
    }
}
