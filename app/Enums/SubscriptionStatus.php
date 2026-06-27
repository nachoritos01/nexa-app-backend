<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Trialing = 'trialing';
    case Canceled = 'canceled';
    case PastDue = 'past_due';
    case Incomplete = 'incomplete';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa',
            self::Trialing => 'En prueba',
            self::Canceled => 'Cancelada',
            self::PastDue => 'Pago vencido',
            self::Incomplete => 'Incompleta',
        };
    }

    public function isValid(): bool
    {
        return match ($this) {
            self::Active, self::Trialing => true,
            default => false,
        };
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
}
