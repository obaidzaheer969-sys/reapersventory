<?php

namespace App\Notifications;

use App\Notifications\Concerns\UsesAppChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class LowStockAlertNotification extends Notification
{
    use Queueable;
    use UsesAppChannels;

    /**
     * @param  Collection<int, \App\Models\Product>  $products
     */
    public function __construct(
        public Collection $products,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Low-stock alert — ReapersVentory')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('The following products are at or below reorder level:');

        foreach ($this->products->take(20) as $product) {
            $mail->line(sprintf(
                '• %s (%s): %s on hand / alert at %s',
                $product->name,
                $product->sku,
                number_format((float) $product->totalQuantity(), 3),
                number_format((float) $product->effectiveReorderLevel(), 3),
            ));
        }

        if ($this->products->count() > 20) {
            $mail->line('…and '.($this->products->count() - 20).' more.');
        }

        return $mail->action('Review inventory', url('/business/inventory'));
    }

    public function toArray(object $notifiable): array
    {
        $count = $this->products->count();
        $first = $this->products->first();

        return [
            'title' => 'Low-stock alert',
            'body' => $count === 1 && $first
                ? "{$first->name} is at or below reorder level."
                : "{$count} products are at or below reorder level.",
            'url' => url('/business/inventory'),
            'level' => 'warning',
        ];
    }
}
