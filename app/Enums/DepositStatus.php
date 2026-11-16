<?php

namespace App\Enums;

enum DepositStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Completed => 'success',
            self::Failed => 'danger',
            self::Cancelled => 'gray',
        };
    }

    public function hex(): string
    {
        return match ($this) {
            self::Pending => '#fbbf24',
            self::Completed => '#34d399',
            self::Failed => '#fb7185',
            self::Cancelled => '#94a3b8',
        };
    }

    public function isCompleted(): bool
    {
        return $this === self::Completed;
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
