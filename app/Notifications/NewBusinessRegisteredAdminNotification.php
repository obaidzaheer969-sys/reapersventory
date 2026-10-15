<?php

namespace App\Notifications;

use App\Models\Business;
use App\Models\User;
use App\Notifications\Concerns\FormatsFilamentDatabaseNotification;
use App\Notifications\Concerns\UsesAppChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBusinessRegisteredAdminNotification extends Notification
{
    use FormatsFilamentDatabaseNotification;
    use Queueable;
    use UsesAppChannels;

    public function __construct(
        public Business $business,
        public User $owner,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New business registered — ReapersVentory')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A new business registered on ReapersVentory.')
            ->line('Business: '.$this->business->name)
            ->line('Owner: '.$this->owner->name.' ('.$this->owner->email.')')
            ->action('Open admin', url('/admin'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New business registered',
            'body' => $this->business->name.' — '.$this->owner->email,
            'url' => url('/admin/businesses'),
            'level' => 'info',
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->filamentDatabaseMessage(
            title: 'New business registered',
            body: $this->business->name.' · owner '.$this->owner->name.' ('.$this->owner->email.')',
            url: url('/admin/businesses'),
            status: 'info',
            actionLabel: 'View businesses',
        );
    }
}
