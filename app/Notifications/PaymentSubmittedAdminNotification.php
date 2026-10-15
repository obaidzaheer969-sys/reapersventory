<?php

namespace App\Notifications;

use App\Models\SubscriptionPayment;
use App\Notifications\Concerns\FormatsFilamentDatabaseNotification;
use App\Notifications\Concerns\UsesAppChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSubmittedAdminNotification extends Notification
{
    use FormatsFilamentDatabaseNotification;
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
            ->line('Business: '.($this->payment->business?->name ?? '—'))
            ->line('Amount: '.$this->payment->currency.' '.number_format((float) $this->payment->amount, 2))
            ->line('Plan: '.($this->payment->plan?->name ?? '—'))
            ->action('Review in admin', url('/admin/subscription-payments'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Payment proof pending',
            'body' => ($this->payment->business?->name ?? 'Business').' · '.$this->payment->currency.' '.number_format((float) $this->payment->amount, 2),
            'url' => url('/admin/subscription-payments'),
            'level' => 'warning',
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->filamentDatabaseMessage(
            title: 'Payment proof pending',
            body: ($this->payment->business?->name ?? 'Business')
                .' submitted '.$this->payment->currency.' '
                .number_format((float) $this->payment->amount, 2)
                .' for '.($this->payment->plan?->name ?? 'a plan').'.',
            url: url('/admin/subscription-payments'),
            status: 'warning',
            actionLabel: 'Review payment',
        );
    }
}
