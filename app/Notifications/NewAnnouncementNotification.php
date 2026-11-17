<?php

namespace App\Notifications;

use App\Models\Announcement;
use App\Notifications\Concerns\UsesAppChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewAnnouncementNotification extends Notification
{
    use Queueable;
    use UsesAppChannels;

    public function __construct(
        public Announcement $announcement,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New announcement — '.config('earning.brand'))
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A new announcement has been published.')
            ->line($this->announcement->title)
            ->line($this->announcement->body)
            ->action('Read announcement', route('announcements.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New announcement',
            'body' => $this->announcement->title,
            'url' => route('announcements.index'),
            'level' => 'info',
            'announcement_id' => $this->announcement->id,
        ];
    }
}
