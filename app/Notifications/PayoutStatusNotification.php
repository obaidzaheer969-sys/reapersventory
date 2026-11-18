<?php

namespace App\Notifications;

use App\Models\PayoutRequest;
use App\Notifications\Concerns\UsesAppChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutStatusNotification extends Notification
{
    use Queueable;
    use UsesAppChannels;

    public function __construct(
        public PayoutRequest $payout,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $paid = $this->payout->status->value === 'paid';

        $message = (new MailMessage)
            ->subject($paid ? 'Payout sent — '.config('earning.brand') : 'Payout rejected — '.config('earning.brand'))
            ->greeting('Hello '.$notifiable->name.',')
            ->line($paid
                ? 'Your payout request was approved and the payment has been sent.'
                : 'Your payout request was rejected and the amount has been returned to your wallet.')
            ->line('Amount: PKR '.number_format((float) $this->payout->amount, 2))
            ->line('Wallet: '.$this->payout->provider->label().' — '.$this->payout->account_number);

        if (! $paid && filled($this->payout->admin_notes)) {
            $message->line('Reason: '.$this->payout->admin_notes);
        }

        return $message->action('Open wallet', url('/wallet'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $paid = $this->payout->status->value === 'paid';

        return [
            'title' => $paid ? 'Payout sent' : 'Payout rejected',
            'body' => 'PKR '.number_format((float) $this->payout->amount, 2).' — '.$this->payout->provider->label(),
            'url' => url('/wallet'),
            'level' => $paid ? 'success' : 'danger',
        ];
    }
}
