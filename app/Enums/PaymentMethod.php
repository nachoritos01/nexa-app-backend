<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case Transfer = 'transfer';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Card => 'Card',
            self::Transfer => 'Transfer',
            self::Other => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Cash => 'success',
            self::Card => 'primary',
            self::Transfer => 'info',
            self::Other => 'gray',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Cash => 'bg-green-100 text-green-700',
            self::Card => 'bg-blue-100 text-blue-700',
            self::Transfer => 'bg-cyan-100 text-cyan-700',
            self::Other => 'bg-gray-100 text-gray-700',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
