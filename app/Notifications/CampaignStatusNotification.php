<?php

namespace App\Notifications;

use App\Enums\CampaignStatus;
use App\Models\EarningTask;
use App\Notifications\Concerns\UsesAppChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CampaignStatusNotification extends Notification
{
    use Queueable;
    use UsesAppChannels;

    public function __construct(
        public EarningTask $campaign,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $approved = $this->campaign->campaign_status === CampaignStatus::Active;

        $message = (new MailMessage)
            ->subject(($approved ? 'Campaign approved' : 'Campaign update').' — '.config('earning.brand'))
            ->greeting('Hello '.$notifiable->name.',')
            ->line($approved
                ? 'Aap ki campaign approve ho gayi hai aur ab live hai. Doosre users ise complete kar sakte hain.'
                : 'Aap ki campaign approve nahi hui. Payment wallet me wapas add kar di gayi hai.')
            ->line('Task: '.$this->campaign->task_type->label().' — '.$this->campaign->title)
            ->line('Quantity: '.number_format((int) $this->campaign->target_quantity))
            ->line('Amount: PKR '.number_format((float) $this->campaign->total_cost, 2));

        if (! $approved && filled($this->campaign->admin_notes)) {
            $message->line('Reason: '.$this->campaign->admin_notes);
        }

        return $message->action('Open my campaigns', url('/campaigns'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $approved = $this->campaign->campaign_status === CampaignStatus::Active;

        return [
            'title' => $approved ? 'Campaign approved' : 'Campaign rejected',
            'body' => $this->campaign->title
                .' · PKR '.number_format((float) $this->campaign->total_cost, 2)
                .($approved ? ' · Live' : ' · Refunded to wallet'),
            'url' => url('/campaigns'),
            'level' => $approved ? 'success' : 'danger',
        ];
    }
}
