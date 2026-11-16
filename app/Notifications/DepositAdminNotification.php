<?php

namespace App\Notifications;

use App\Models\Deposit;
use App\Notifications\Concerns\FormatsFilamentDatabaseNotification;
use App\Notifications\Concerns\UsesAppChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DepositAdminNotification extends Notification
{
    use FormatsFilamentDatabaseNotification;
    use Queueable;
    use UsesAppChannels;

    public function __construct(
        public Deposit $deposit,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New wallet deposit received')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A user deposited money through Cashmaal.')
            ->line('User: '.($this->deposit->user?->name ?? '—'))
            ->line('Amount: PKR '.number_format((float) $this->deposit->credit_amount, 2))
            ->line('Order: '.$this->deposit->order_id)
            ->line('Cashmaal TID: '.($this->deposit->cm_tid ?? '—'))
            ->action('View deposits', url('/admin/deposits'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New wallet deposit',
            'body' => ($this->deposit->user?->name ?? 'User')
                .' · PKR '.number_format((float) $this->deposit->credit_amount, 2),
            'url' => url('/admin/deposits'),
            'level' => 'success',
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->filamentDatabaseMessage(
            title: 'New wallet deposit',
            body: ($this->deposit->user?->name ?? 'User')
                .' deposited PKR '.number_format((float) $this->deposit->credit_amount, 2)
                .' via Cashmaal.',
            url: url('/admin/deposits'),
            status: 'success',
            actionLabel: 'View deposit',
        );
    }
}
