<?php

namespace App\Enums;

enum CancellationReason: string
{
    case Price = 'price';
    case MissingFeatures = 'missing_features';
    case ClosedBusiness = 'closed_business';
    case Competitor = 'competitor';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Price => 'Too expensive',
            self::MissingFeatures => 'Missing features',
            self::ClosedBusiness => 'Closed business',
            self::Competitor => 'Switched to competitor',
            self::Other => 'Other',
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
