<?php

namespace App\Support;

final class Currency
{
    public static function format(float|int|string|null $amount, bool $withDecimals = true, ?string $symbol = null): string
    {
        $value = (float) $amount;

        return ($symbol ?? self::symbol()).' '.number_format($value, $withDecimals ? 2 : 0);
    }

    public static function symbol(): string
    {
        return (string) config('earning.currency', 'PKR');
    }

    public static function coins(float|int|string|null $amount, bool $withDecimals = true): string
    {
        return self::format($amount, $withDecimals, 'Coins');
    }

    public static function signed(float|int|string|null $amount, bool $isCredit, ?string $symbol = null): string
    {
        return ($isCredit ? '+' : '-').self::format(abs((float) $amount), true, $symbol);
    }

    public static function signedCoins(float|int|string|null $amount, bool $isCredit): string
    {
        return self::signed($amount, $isCredit, 'Coins');
    }
}
