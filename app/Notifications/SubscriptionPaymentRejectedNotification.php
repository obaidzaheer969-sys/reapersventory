<?php

namespace App\Notifications;

use App\Models\SubscriptionPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionPaymentRejectedNotification extends Notification implements ShouldQueue
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
        $mail = (new MailMessage)
            ->subject('Subscription payment rejected — ReapersVentory')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your subscription payment proof was rejected.')
            ->line('Amount: '.$this->payment->currency.' '.number_format((float) $this->payment->amount, 2));

        if ($this->payment->review_notes) {
            $mail->line('Reason: '.$this->payment->review_notes);
        }

        return $mail
            ->action('Submit a new payment', url('/business/subscription'))
            ->line('If you need help, reply to this email or contact support.');
    }
}
