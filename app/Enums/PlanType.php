<?php

namespace App\Enums;

enum PlanType: string
{
    case Starter = 'starter';
    case Growth = 'growth';
    case Pro = 'pro';

    public function label(): string
    {
        return match ($this) {
            self::Starter => 'Starter',
            self::Growth => 'Growth',
            self::Pro => 'Pro',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::Starter => 0,
            self::Growth => 1,
            self::Pro => 2,
        };
    }

    public function isHigherThan(self $other): bool
    {
        return $this->order() > $other->order();
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    public static function default(): self
    {
        return self::Starter;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
