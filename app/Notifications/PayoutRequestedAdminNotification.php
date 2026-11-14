<?php

namespace App\Notifications;

use App\Models\PayoutRequest;
use App\Notifications\Concerns\FormatsFilamentDatabaseNotification;
use App\Notifications\Concerns\UsesAppChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutRequestedAdminNotification extends Notification
{
    use FormatsFilamentDatabaseNotification;
    use Queueable;
    use UsesAppChannels;

    public function __construct(
        public PayoutRequest $payout,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New payout request pending review')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('An earner requested a payout.')
            ->line('User: '.($this->payout->user?->name ?? '—'))
            ->line('Amount: PKR '.number_format((float) $this->payout->amount, 2))
            ->line('Wallet: '.$this->payout->provider->label().' — '.$this->payout->account_number)
            ->action('Review in admin', url('/admin/payout-requests'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New payout request',
            'body' => ($this->payout->user?->name ?? 'Earner').' · PKR '.number_format((float) $this->payout->amount, 2),
            'url' => url('/admin/payout-requests'),
            'level' => 'warning',
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->filamentDatabaseMessage(
            title: 'New payout request',
            body: ($this->payout->user?->name ?? 'Earner')
                .' requested PKR '.number_format((float) $this->payout->amount, 2)
                .' via '.$this->payout->provider->label().'.',
            url: url('/admin/payout-requests'),
            status: 'warning',
            actionLabel: 'Review payout',
        );
    }
}
