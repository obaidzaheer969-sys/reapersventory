<?php

namespace App\Notifications;

use App\Models\Purchase;
use App\Notifications\Concerns\UsesAppChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseReceivedNotification extends Notification
{
    use Queueable;
    use UsesAppChannels;

    public function __construct(
        public Purchase $purchase,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Purchase '.$this->purchase->purchase_number.' received')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Stock was updated from purchase '.$this->purchase->purchase_number.'.')
            ->action('View purchase', url('/business/purchases/'.$this->purchase->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Purchase received',
            'body' => $this->purchase->purchase_number.' stock has been added to inventory.',
            'url' => url('/business/purchases/'.$this->purchase->id),
            'level' => 'success',
        ];
    }
}
