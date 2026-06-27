<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Referral;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReferralStats extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $total = Referral::count();
        $converted = Referral::whereNotNull('converted_at')->count();
        $rewarded = Referral::whereNotNull('rewarded_at')->count();

        return [
            Stat::make('Total Referrals', $total)
                ->description('Signups via referral')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info'),

            Stat::make('Conversions', $converted)
                ->description('Subscribed')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($converted > 0 ? 'success' : 'gray'),

            Stat::make('Rewards', $rewarded)
                ->description('Coupons applied')
                ->descriptionIcon('heroicon-m-gift')
                ->color($rewarded > 0 ? 'success' : 'gray'),
        ];
    }
}
