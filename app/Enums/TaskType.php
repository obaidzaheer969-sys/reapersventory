<?php

namespace App\Enums;

enum TaskType: string
{
    case YoutubeSubscribe = 'youtube_subscribe';
    case YoutubeWatch = 'youtube_watch';
    case YoutubeLike = 'youtube_like';
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
            self::YoutubeLike => 'YouTube Like',
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
            self::YoutubeSubscribe, self::YoutubeWatch, self::YoutubeLike => 'YouTube',
            self::FacebookFollow, self::FacebookWatch => 'Facebook',
            self::InstagramFollow, self::InstagramWatch => 'Instagram',
            self::WhatsappChannel => 'WhatsApp',
            self::AdWatch => 'Ads',
        };
    }

    public function platformKey(): string
    {
        return match ($this) {
            self::YoutubeSubscribe, self::YoutubeWatch, self::YoutubeLike => 'youtube',
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
            self::YoutubeLike => 'Like video',
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

    /**
     * Only platforms with an official embeddable player are rendered inside
     * the lock page. Instagram and generic ad tasks keep the real-page flow.
     */
    public function isEmbeddedWatchTask(): bool
    {
        return in_array($this, [
            self::YoutubeWatch,
            self::FacebookWatch,
        ], true);
    }

    public function embedUrl(string $targetUrl, ?string $origin = null): ?string
    {
        return match ($this) {
            self::YoutubeWatch => $this->youtubeEmbedUrl($targetUrl, $origin),
            self::FacebookWatch => $this->facebookEmbedUrl($targetUrl),
            default => null,
        };
    }

    private function youtubeEmbedUrl(string $targetUrl, ?string $origin): ?string
    {
        $videoId = self::youtubeVideoId($targetUrl);

        if ($videoId === null) {
            return null;
        }

        $query = [
            'enablejsapi' => '1',
            'rel' => '0',
            'playsinline' => '1',
        ];

        if ($origin !== null && $origin !== '') {
            $query['origin'] = $origin;
        }

        return 'https://www.youtube.com/embed/'.$videoId.'?'.http_build_query($query);
    }

    private static function youtubeVideoId(string $url): ?string
    {
        $parts = parse_url($url);

        if ($parts === false || empty($parts['host'])) {
            return null;
        }

        $host = strtolower((string) $parts['host']);
        $path = trim((string) ($parts['path'] ?? ''), '/');
        $isYoutubeHost = $host === 'youtube.com' || str_ends_with($host, '.youtube.com');

        if ($host === 'youtu.be' || str_ends_with($host, '.youtu.be')) {
            return preg_match('/^[A-Za-z0-9_-]{11}$/', $path) === 1 ? $path : null;
        }

        if (! $isYoutubeHost) {
            return null;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);

        if (isset($query['v']) && is_string($query['v']) && preg_match('/^[A-Za-z0-9_-]{11}$/', $query['v']) === 1) {
            return $query['v'];
        }

        if (preg_match('#^(?:embed|shorts|live|v)/([A-Za-z0-9_-]{11})$#', $path, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private function facebookEmbedUrl(string $targetUrl): ?string
    {
        $host = strtolower((string) parse_url($targetUrl, PHP_URL_HOST));

        $isFacebookHost = $host === 'facebook.com'
            || str_ends_with($host, '.facebook.com')
            || $host === 'fb.watch'
            || str_ends_with($host, '.fb.watch');

        if (! $isFacebookHost) {
            return null;
        }

        return 'https://www.facebook.com/plugins/video.php?'.http_build_query([
            'href' => $targetUrl,
            'show_text' => 'false',
            'autoplay' => 'false',
            'allowfullscreen' => 'true',
        ]);
    }

    public function isCampaignEligible(): bool
    {
        return $this !== self::AdWatch;
    }

    /**
     * Follower, subscriber and like campaigns are priced per action, not per second.
     */
    public function isQuantityCampaign(): bool
    {
        return ! $this->isWatchTask();
    }

    public function color(): string
    {
        return match ($this) {
            self::YoutubeSubscribe, self::YoutubeWatch, self::YoutubeLike => '#ef4444',
            self::FacebookFollow, self::FacebookWatch => '#2563eb',
            self::InstagramFollow, self::InstagramWatch => '#db2777',
            self::WhatsappChannel => '#16a34a',
            self::AdWatch => '#f59e0b',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::YoutubeSubscribe, self::YoutubeWatch, self::YoutubeLike => '▶',
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

    /**
     * @return array<string, string>
     */
    public static function campaignOptions(): array
    {
        return collect(self::cases())
            ->filter(fn (self $type) => $type->isCampaignEligible())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }
}
