<?php

namespace App\Enums;

enum TaskType: string
{
    case YoutubeSubscribe = 'youtube_subscribe';
    case YoutubeWatch = 'youtube_watch';
    case FacebookFollow = 'facebook_follow';
    case FacebookWatch = 'facebook_watch';
    case InstagramFollow = 'instagram_follow';
    case InstagramWatch = 'instagram_watch';
    case WhatsappChannel = 'whatsapp_channel';
    case AdWatch = 'ad_watch';

    public function label(): string
    {
        return match ($this) {
            self::YoutubeSubscribe => 'YouTube Subscribe',
            self::YoutubeWatch => 'YouTube Video Watch',
            self::FacebookFollow => 'Facebook Page Follow',
            self::FacebookWatch => 'Facebook Video Watch',
            self::InstagramFollow => 'Instagram Follow',
            self::InstagramWatch => 'Instagram Video Watch',
            self::WhatsappChannel => 'WhatsApp Channel Follow',
            self::AdWatch => 'Ad Watch',
        };
    }

    public function platform(): string
    {
        return match ($this) {
            self::YoutubeSubscribe, self::YoutubeWatch => 'YouTube',
            self::FacebookFollow, self::FacebookWatch => 'Facebook',
            self::InstagramFollow, self::InstagramWatch => 'Instagram',
            self::WhatsappChannel => 'WhatsApp',
            self::AdWatch => 'Ads',
        };
    }

    public function platformKey(): string
    {
        return match ($this) {
            self::YoutubeSubscribe, self::YoutubeWatch => 'youtube',
            self::FacebookFollow, self::FacebookWatch => 'facebook',
            self::InstagramFollow, self::InstagramWatch => 'instagram',
            self::WhatsappChannel => 'whatsapp',
            self::AdWatch => 'ads',
        };
    }

    public function actionText(): string
    {
        return match ($this) {
            self::YoutubeSubscribe => 'Subscribe',
            self::YoutubeWatch => 'Watch video',
            self::FacebookFollow => 'Follow page',
            self::FacebookWatch => 'Watch video',
            self::InstagramFollow => 'Follow profile',
            self::InstagramWatch => 'Watch video',
            self::WhatsappChannel => 'Follow channel',
            self::AdWatch => 'Watch ad',
        };
    }

    public function defaultSeconds(): int
    {
        return match ($this) {
            self::YoutubeWatch, self::FacebookWatch, self::InstagramWatch, self::AdWatch => 30,
            default => 15,
        };
    }

    public function isWatchTask(): bool
    {
        return in_array($this, [
            self::YoutubeWatch,
            self::FacebookWatch,
            self::InstagramWatch,
            self::AdWatch,
        ], true);
    }

    public function color(): string
    {
        return match ($this) {
            self::YoutubeSubscribe, self::YoutubeWatch => '#ef4444',
            self::FacebookFollow, self::FacebookWatch => '#2563eb',
            self::InstagramFollow, self::InstagramWatch => '#db2777',
            self::WhatsappChannel => '#16a34a',
            self::AdWatch => '#f59e0b',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::YoutubeSubscribe, self::YoutubeWatch => '▶',
            self::FacebookFollow, self::FacebookWatch => 'f',
            self::InstagramFollow, self::InstagramWatch => '◎',
            self::WhatsappChannel => '✆',
            self::AdWatch => '★',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }
}
