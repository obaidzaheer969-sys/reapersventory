<?php

namespace App\Notifications;

use App\Models\Subscription;
use App\Notifications\Concerns\UsesAppChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionStatusNotification extends Notification
{
    use Queueable;
    use UsesAppChannels;

    public function __construct(
        public Subscription $subscription,
        public string $event,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        [$subject, $line] = $this->copy();

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($line)
            ->action('Manage subscription', url('/business/subscription'));
    }

    public function toArray(object $notifiable): array
    {
        [, $line] = $this->copy();

        return [
            'title' => match ($this->event) {
                'expiring_soon' => 'Subscription expiring soon',
                'grace' => 'Grace period started',
                'expired' => 'Subscription expired',
                default => 'Subscription update',
            },
            'body' => $line,
            'url' => url('/business/subscription'),
            'level' => match ($this->event) {
                'expired' => 'danger',
                'grace', 'expiring_soon' => 'warning',
                default => 'info',
            },
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function copy(): array
    {
        return match ($this->event) {
            'expiring_soon' => [
                'Subscription expiring soon — ReapersVentory',
                'Your subscription ends on '.$this->subscription->ends_at?->toFormattedDateString().'. Renew to avoid interruption.',
            ],
            'grace' => [
                'Subscription entered grace period — ReapersVentory',
                'Paid period ended. Grace lasts until '.$this->subscription->grace_ends_at?->toFormattedDateString().'.',
            ],
            'expired' => [
                'Subscription expired — ReapersVentory',
                'Your subscription has expired. Submit a payment to restore access.',
            ],
            default => [
                'Subscription update — ReapersVentory',
                'Your subscription status is now: '.$this->subscription->status->label().'.',
            ],
        };
    }
}
