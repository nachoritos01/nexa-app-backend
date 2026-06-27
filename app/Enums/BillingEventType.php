<?php

namespace App\Enums;

enum BillingEventType: string
{
    case SubscriptionStarted = 'subscription_started';
    case PlanChanged = 'plan_changed';
    case PluginActivated = 'plugin_activated';
    case PluginDeactivated = 'plugin_deactivated';
    case SubscriptionCancelled = 'subscription_cancelled';

    public function label(): string
    {
        return match ($this) {
            self::SubscriptionStarted => 'Subscription Started',
            self::PlanChanged => 'Plan Changed',
            self::PluginActivated => 'Plugin Activated',
            self::PluginDeactivated => 'Plugin Deactivated',
            self::SubscriptionCancelled => 'Subscription Cancelled',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::SubscriptionStarted => 'heroicon-o-check-circle',
            self::PlanChanged => 'heroicon-o-arrow-path',
            self::PluginActivated => 'heroicon-o-puzzle-piece',
            self::PluginDeactivated => 'heroicon-o-x-circle',
            self::SubscriptionCancelled => 'heroicon-o-no-symbol',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SubscriptionStarted => 'success',
            self::PlanChanged => 'info',
            self::PluginActivated => 'success',
            self::PluginDeactivated => 'warning',
            self::SubscriptionCancelled => 'danger',
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
