<?php

namespace App\Notifications;

use App\Models\Sale;
use App\Notifications\Concerns\UsesAppChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SaleCompletedNotification extends Notification
{
    use Queueable;
    use UsesAppChannels;

    public function __construct(
        public Sale $sale,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Sale '.$this->sale->sale_number.' completed')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A sale was completed for total '.number_format((float) $this->sale->total, 2).'.')
            ->action('View sale', url('/business/sales/'.$this->sale->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Sale completed',
            'body' => $this->sale->sale_number.' — total '.number_format((float) $this->sale->total, 2),
            'url' => url('/business/sales/'.$this->sale->id),
            'level' => 'success',
        ];
    }
}
