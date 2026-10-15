<?php

namespace App\Notifications\Concerns;

trait UsesAppChannels
{
    /**
     * In-app database notifications always; mail only when a real mailer is configured.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $mailer = (string) config('mail.default');
        if (! in_array($mailer, ['log', 'array', ''], true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }
}
