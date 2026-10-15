<?php

namespace App\Notifications;

use App\Models\SubscriptionPayment;
use App\Notifications\Concerns\UsesAppChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionPaymentApprovedNotification extends Notification
{
    use Queueable;
    use UsesAppChannels;

    public function __construct(
        public SubscriptionPayment $payment,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Subscription payment approved — ReapersVentory')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your subscription payment has been approved.')
            ->line('Amount: '.$this->payment->currency.' '.number_format((float) $this->payment->amount, 2))
            ->action('View subscription', url('/business/subscription'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Payment approved',
            'body' => 'Your subscription payment of '.$this->payment->currency.' '.number_format((float) $this->payment->amount, 2).' was approved.',
            'url' => url('/business/subscription'),
            'level' => 'success',
        ];
    }
}
