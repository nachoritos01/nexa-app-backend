<?php

namespace App\Constants;

final class BusinessRules
{
    public const CURRENCY = 'USD';

    public const QUOTE_EXPIRY_DAYS = 7;

    public static function currency(): string
    {
        return (string) config('business.rules.currency', self::CURRENCY);
    }

    public static function quoteExpiryDays(): int
    {
        return (int) config('business.rules.quote_expiry_days', self::QUOTE_EXPIRY_DAYS);
    }
}
