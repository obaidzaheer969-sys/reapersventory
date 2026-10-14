<?php

namespace App\Notifications;

use App\Models\SubscriptionPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionPaymentApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SubscriptionPayment $payment,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Subscription payment approved — ReapersVentory')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your subscription payment has been approved.')
            ->line('Amount: '.$this->payment->currency.' '.number_format((float) $this->payment->amount, 2))
            ->line('Billing period: '.$this->payment->period_start->toFormattedDateString().' → '.$this->payment->period_end->toFormattedDateString())
            ->action('View subscription', url('/business/subscription'))
            ->line('Thank you for using ReapersVentory.');
    }
}
