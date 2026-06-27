<?php

return [

    'trial' => [
        'days' => 14,
        'grace_days' => 3,
        'referral_bonus_referred' => 7,
        'referral_bonus_referrer' => 15,
        'referral_bonus_conversion' => 15,
        'referral_max_registration_days' => 90,
        'referral_max_total_days' => 120,
    ],

    'plans' => [
        'default' => 'starter',

        'starter' => [
            'label' => 'Starter',
            'max_orders' => 50,
            'max_users' => 2,
            'max_locations' => 1,
            'max_items' => 20,
            'max_customers' => 200,
            'price_monthly' => 299,
            'price_yearly' => 2868,
            'stripe_monthly' => env('STRIPE_PRICE_STARTER_MONTHLY'),
            'stripe_yearly' => env('STRIPE_PRICE_STARTER_YEARLY'),
            'features' => [
                '50 orders/month',
                '2 users',
                '1 location',
                '20 items',
                '200 customers',
            ],
        ],

        'growth' => [
            'label' => 'Growth',
            'max_orders' => 200,
            'max_users' => 5,
            'max_locations' => 3,
            'max_items' => 100,
            'max_customers' => 1000,
            'price_monthly' => 699,
            'price_yearly' => 6708,
            'stripe_monthly' => env('STRIPE_PRICE_GROWTH_MONTHLY'),
            'stripe_yearly' => env('STRIPE_PRICE_GROWTH_YEARLY'),
            'features' => [
                '200 orders/month',
                '5 users',
                '3 locations',
                '100 items',
                '1,000 customers',
                'CSV report export',
            ],
        ],

        'pro' => [
            'label' => 'Pro',
            'max_orders' => null,
            'max_users' => null,
            'max_locations' => null,
            'max_items' => null,
            'max_customers' => null,
            'price_monthly' => 1299,
            'price_yearly' => 12468,
            'stripe_monthly' => env('STRIPE_PRICE_PRO_MONTHLY'),
            'stripe_yearly' => env('STRIPE_PRICE_PRO_YEARLY'),
            'features' => [
                'Unlimited orders',
                'Unlimited users',
                'Unlimited locations',
                'Unlimited items',
                'Unlimited customers',
                'CSV report export',
                'Priority support',
            ],
        ],
    ],

    'limits' => [
        'soft_limit_percentage' => 80,
        'grace_days' => 7,
    ],

    'onboarding' => [
        'steps' => [
            'setup_business',
            'first_item',
        ],
    ],

    'cache' => [
        'usage_counts_ttl' => 300,    // 5 min
        'dashboard_stats_ttl' => 60,  // 1 min
    ],

    'api' => [
        'rate_limits' => [
            'starter' => 60,
            'growth' => 120,
            'pro' => 300,
            'unauthenticated' => 10,
        ],
    ],

    'retention' => [
        'health_score' => [
            'weights' => [
                'login_recency' => 30,
                'order_recency' => 30,
                'feature_adoption' => 20,
                'user_activity' => 20,
            ],
            'lookback_days' => 30,
            'active_user_days' => 7,
            'healthy_threshold' => 70,
            'at_risk_threshold' => 50,
        ],
        'inactivity_reminder_days' => 14,
        'low_health_threshold' => 50,
        'tracked_features' => [
            'order_created',
            'customer_created',
            'item_created',
            'payment_received',
            'export_csv',
        ],
        'winback' => [
            'days_after_cancellation' => 7,
            'promotion_url' => env('WINBACK_PROMOTION_URL'),
        ],
        'referral' => [],
    ],

];
