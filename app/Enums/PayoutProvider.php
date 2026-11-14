<?php

namespace App\Enums;

enum PayoutProvider: string
{
    case Easypaisa = 'easypaisa';
    case JazzCash = 'jazzcash';
    case UPaisa = 'upaisa';
    case NayaPay = 'nayapay';

    public function label(): string
    {
        return match ($this) {
            self::Easypaisa => 'Easypaisa',
            self::JazzCash => 'JazzCash',
            self::UPaisa => 'UPaisa',
            self::NayaPay => 'NayaPay',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Easypaisa => '#16a34a',
            self::JazzCash => '#dc2626',
            self::UPaisa => '#7c3aed',
            self::NayaPay => '#0d9488',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Easypaisa => '03XX-XXXXXXX (Easypaisa mobile account)',
            self::JazzCash => '03XX-XXXXXXX (JazzCash mobile account)',
            self::UPaisa => '03XX-XXXXXXX (UPaisa mobile account)',
            self::NayaPay => '03XX-XXXXXXX (NayaPay account)',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $provider) => [$provider->value => $provider->label()])
            ->all();
    }
}
