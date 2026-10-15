<?php

namespace App\Notifications;

use App\Notifications\Concerns\UsesAppChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeBusinessNotification extends Notification
{
    use Queueable;
    use UsesAppChannels;

    public function __construct(
        public string $businessName,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to ReapersVentory')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your business "'.$this->businessName.'" is ready.')
            ->action('Open dashboard', url('/business/dashboard'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Welcome to ReapersVentory',
            'body' => 'Your business "'.$this->businessName.'" is set up. Choose a plan to unlock inventory and sales.',
            'url' => url('/business/subscription'),
            'level' => 'info',
        ];
    }
}
