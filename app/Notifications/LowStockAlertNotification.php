<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class LowStockAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Collection<int, \App\Models\Product>|\Illuminate\Support\Collection  $products
     */
    public function __construct(
        public Collection $products,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Low-stock alert — ReapersVentory')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('The following products are at or below reorder level:');

        foreach ($this->products->take(20) as $product) {
            $mail->line(sprintf(
                '• %s (%s): %s on hand / reorder %s',
                $product->name,
                $product->sku,
                number_format((float) $product->totalQuantity(), 3),
                number_format((float) $product->reorder_level, 3),
            ));
        }

        if ($this->products->count() > 20) {
            $mail->line('…and '.($this->products->count() - 20).' more.');
        }

        return $mail->action('Review inventory', url('/business/inventory'));
    }
}
