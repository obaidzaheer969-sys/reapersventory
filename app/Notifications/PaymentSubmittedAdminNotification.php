<?php

namespace App\Notifications;

use App\Models\SubscriptionPayment;
use App\Notifications\Concerns\UsesAppChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSubmittedAdminNotification extends Notification
{
    use Queueable;
    use UsesAppChannels;

    public function __construct(
        public SubscriptionPayment $payment,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New payment proof pending review')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A business submitted a subscription payment proof.')
            ->line('Amount: '.$this->payment->currency.' '.number_format((float) $this->payment->amount, 2))
            ->action('Review in admin', url('/admin/subscription-payments'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Payment proof pending',
            'body' => 'New payment of '.$this->payment->currency.' '.number_format((float) $this->payment->amount, 2).' needs review.',
            'url' => url('/admin/subscription-payments'),
            'level' => 'info',
        ];
    }
}
