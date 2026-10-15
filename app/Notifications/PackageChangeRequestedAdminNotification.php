<?php

namespace App\Notifications;

use App\Models\Plan;
use App\Models\Subscription;
use App\Notifications\Concerns\FormatsFilamentDatabaseNotification;
use App\Notifications\Concerns\UsesAppChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PackageChangeRequestedAdminNotification extends Notification
{
    use FormatsFilamentDatabaseNotification;
    use Queueable;
    use UsesAppChannels;

    public function __construct(
        public Subscription $subscription,
        public Plan $pendingPlan,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $businessName = $this->subscription->business?->name ?? 'Business';

        return (new MailMessage)
            ->subject('Package change requested — ReapersVentory')
            ->greeting('Hello '.$notifiable->name.',')
            ->line($businessName.' requested a package change.')
            ->line('Current: '.($this->subscription->plan?->name ?? '—'))
            ->line('Requested: '.$this->pendingPlan->name)
            ->action('Review payments', url('/admin/subscription-payments'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Package change requested',
            'body' => ($this->subscription->business?->name ?? 'Business').' → '.$this->pendingPlan->name,
            'url' => url('/admin/subscription-payments'),
            'level' => 'warning',
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->filamentDatabaseMessage(
            title: 'Package change requested',
            body: ($this->subscription->business?->name ?? 'Business')
                .' requested '.$this->pendingPlan->name
                .' (currently '.$this->subscription->plan?->name.')',
            url: url('/admin/subscription-payments'),
            status: 'warning',
            actionLabel: 'Review payments',
        );
    }
}
