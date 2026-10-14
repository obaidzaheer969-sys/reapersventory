<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Subscription $subscription,
        public string $event,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        [$subject, $line] = match ($this->event) {
            'expiring_soon' => [
                'Subscription expiring soon — ReapersVentory',
                'Your ReapersVentory subscription ends on '.$this->subscription->ends_at?->toFormattedDateString().'. Please renew to avoid interruption.',
            ],
            'grace' => [
                'Subscription entered grace period — ReapersVentory',
                'Your paid period ended. You are in a grace period until '.$this->subscription->grace_ends_at?->toFormattedDateString().'.',
            ],
            'expired' => [
                'Subscription expired — ReapersVentory',
                'Your ReapersVentory subscription has expired. Submit a payment to restore access.',
            ],
            default => [
                'Subscription update — ReapersVentory',
                'Your subscription status is now: '.$this->subscription->status->label().'.',
            ],
        };

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($line)
            ->action('Manage subscription', url('/business/subscription'));
    }
}
