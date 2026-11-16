<?php

namespace App\Enums;

enum CampaignStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Rejected = 'rejected';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending review',
            self::Active => 'Live',
            self::Rejected => 'Rejected',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Active => 'success',
            self::Rejected => 'danger',
            self::Completed => 'info',
            self::Cancelled => 'gray',
        };
    }

    public function hex(): string
    {
        return match ($this) {
            self::Pending => '#fbbf24',
            self::Active => '#34d399',
            self::Rejected => '#fb7185',
            self::Completed => '#60a5fa',
            self::Cancelled => '#94a3b8',
        };
    }

    public function isLive(): bool
    {
        return $this === self::Active;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Rejected, self::Completed, self::Cancelled], true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }
}
