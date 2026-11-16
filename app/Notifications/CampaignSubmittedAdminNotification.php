<?php

namespace App\Notifications;

use App\Models\EarningTask;
use App\Notifications\Concerns\FormatsFilamentDatabaseNotification;
use App\Notifications\Concerns\UsesAppChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CampaignSubmittedAdminNotification extends Notification
{
    use FormatsFilamentDatabaseNotification;
    use Queueable;
    use UsesAppChannels;

    public function __construct(
        public EarningTask $campaign,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New campaign is live')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A user published a new campaign. It is already live for earners.')
            ->line('User: '.($this->campaign->owner?->name ?? '—'))
            ->line('Task: '.$this->campaign->task_type->label().' — '.$this->campaign->title)
            ->line('Quantity: '.number_format((int) $this->campaign->target_quantity))
            ->line('Payment held: PKR '.number_format((float) $this->campaign->total_cost, 2))
            ->action('View campaigns', url('/admin/campaigns'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New campaign is live',
            'body' => ($this->campaign->owner?->name ?? 'User').' · '.$this->campaign->task_type->label()
                .' · PKR '.number_format((float) $this->campaign->total_cost, 2),
            'url' => url('/admin/campaigns'),
            'level' => 'success',
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->filamentDatabaseMessage(
            title: 'New campaign is live',
            body: ($this->campaign->owner?->name ?? 'User')
                .' published '.$this->campaign->task_type->label()
                .' for '.number_format((int) $this->campaign->target_quantity).' actions.',
            url: url('/admin/campaigns'),
            status: 'success',
            actionLabel: 'View campaign',
        );
    }
}
