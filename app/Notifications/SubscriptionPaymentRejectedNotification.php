<?php

namespace App\Notifications;

use App\Models\SubscriptionPayment;
use App\Notifications\Concerns\UsesAppChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionPaymentRejectedNotification extends Notification
{
    use Queueable;
    use UsesAppChannels;

    public function __construct(
        public SubscriptionPayment $payment,
    ) {}

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

        return $mail->action('Submit a new payment', url('/business/subscription'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Payment rejected',
            'body' => $this->payment->review_notes
                ? 'Payment rejected: '.$this->payment->review_notes
                : 'Your subscription payment proof was rejected.',
            'url' => url('/business/subscription'),
            'level' => 'danger',
        ];
    }
}
