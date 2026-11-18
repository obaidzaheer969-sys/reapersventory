<?php

namespace App\Notifications;

use App\Models\EarningTask;
use App\Notifications\Concerns\UsesAppChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CampaignCompletedNotification extends Notification
{
    use Queueable;
    use UsesAppChannels;

    public function __construct(
        public EarningTask $campaign,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Campaign completed — '.config('earning.brand'))
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your campaign has reached its full quantity.')
            ->line('Task: '.$this->campaign->task_type->label().' — '.$this->campaign->title)
            ->line('Completed: '.number_format((int) $this->campaign->completed_quantity).' / '.number_format((int) $this->campaign->target_quantity))
            ->line('Total spent: PKR '.number_format((float) $this->campaign->total_cost, 2))
            ->action('View campaign', url('/campaigns'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Campaign completed',
            'body' => $this->campaign->title.' · '
                .number_format((int) $this->campaign->completed_quantity).'/'
                .number_format((int) $this->campaign->target_quantity).' delivered',
            'url' => url('/campaigns'),
            'level' => 'success',
        ];
    }
}
