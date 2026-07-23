<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Subscription Plans
    |--------------------------------------------------------------------------
    |
    | Define each plan's features and limits. This is the single source of
    | truth — controllers and middleware read from here, never hard-code values.
    |
    */

    'plans' => [

        'starter' => [
            'label' => 'Starter',
            'description' => 'Pour démarrer votre présence en ligne',
            'monthly_price' => 19.90,
            'yearly_price' => 199.00,
            'limits' => [
                'max_portfolio_media' => 10,       // total media across all items
                'max_services' => 3,               // active services / publications
                'allows_video' => false,
                'has_pro_badge' => false,
                'has_search_boost' => false,
                'has_advanced_stats' => false,
            ],
        ],

        'pro' => [
            'label' => 'Pro',
            'description' => 'Galerie illimitée, badge Pro et statistiques',
            'monthly_price' => 49.90,
            'yearly_price' => 499.00,
            'limits' => [
                'max_portfolio_media' => null,     // null = unlimited
                'max_services' => null,            // null = unlimited
                'allows_video' => true,
                'has_pro_badge' => true,
                'has_search_boost' => false,
                'has_advanced_stats' => true,
            ],
        ],

        'premium' => [
            'label' => 'Premium',
            'description' => 'Mise en avant dans les résultats de recherche',
            'monthly_price' => 99.90,
            'yearly_price' => 999.00,
            'limits' => [
                'max_portfolio_media' => null,
                'max_services' => null,
                'allows_video' => true,
                'has_pro_badge' => true,
                'has_search_boost' => true,
                'has_advanced_stats' => true,
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Starter Default (no subscription)
    |--------------------------------------------------------------------------
    |
    | Providers with no active subscription are treated as Starter-tier
    | with the most restrictive limits.
    |
    */

    'default_plan' => 'starter',

];
