<?php

namespace App\Notifications;

use App\Models\Deposit;
use App\Notifications\Concerns\UsesAppChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DepositCompletedNotification extends Notification
{
    use Queueable;
    use UsesAppChannels;

    public function __construct(
        public Deposit $deposit,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Wallet deposit received — '.config('earning.brand'))
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Aap ka Cashmaal deposit wallet me add ho gaya hai.')
            ->line('Amount: PKR '.number_format((float) $this->deposit->credit_amount, 2))
            ->line('Order: '.$this->deposit->order_id)
            ->line('Cashmaal TID: '.($this->deposit->cm_tid ?? '—'))
            ->action('Open wallet', url('/wallet'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Deposit received',
            'body' => 'PKR '.number_format((float) $this->deposit->credit_amount, 2)
                .' wallet me add ho gaye · '.$this->deposit->order_id,
            'url' => url('/wallet'),
            'level' => 'success',
        ];
    }
}
