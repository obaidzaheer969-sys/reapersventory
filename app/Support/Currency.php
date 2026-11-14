<?php

namespace App\Support;

final class Currency
{
    public static function format(float|int|string|null $amount, bool $withDecimals = true): string
    {
        $value = (float) $amount;

        return self::symbol().' '.number_format($value, $withDecimals ? 2 : 0);
    }

    public static function symbol(): string
    {
        return (string) config('earning.currency', 'PKR');
    }

    public static function signed(float|int|string|null $amount, bool $isCredit): string
    {
        return ($isCredit ? '+' : '-').self::format(abs((float) $amount));
    }
}
