<?php

namespace App\Notifications\Concerns;

use App\Support\EnsuresNotificationsTable;
use Illuminate\Support\Facades\Schema;
use Throwable;

trait UsesAppChannels
{
    /**
     * In-app database notifications when the table exists; mail only with a real mailer.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        try {
            EnsuresNotificationsTable::run();
            if (Schema::hasTable('notifications')) {
                $channels[] = 'database';
            }
        } catch (Throwable) {
            // ignore during deploy / missing DB
        }

        $mailer = (string) config('mail.default');
        if (! in_array($mailer, ['log', 'array', ''], true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }
}
