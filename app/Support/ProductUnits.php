<?php

namespace App\Support;

final class ProductUnits
{
    /** @return list<string> */
    public static function all(): array
    {
        return ['pcs', 'kg', 'g', 'liter', 'ml', 'box', 'pack', 'dozen'];
    }

    public static function label(string $unit): string
    {
        return match ($unit) {
            'pcs' => 'pcs (pieces)',
            'kg' => 'kg (kilogram)',
            'g' => 'g (gram)',
            'liter' => 'liter',
            'ml' => 'ml',
            'box' => 'box',
            'pack' => 'pack',
            'dozen' => 'dozen',
            default => $unit,
        };
    }
}
