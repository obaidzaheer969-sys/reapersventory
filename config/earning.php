<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Earning Platform
    |--------------------------------------------------------------------------
    */

    'brand' => env('EARNING_BRAND', 'ReapersGrowth'),

    'tagline' => env('EARNING_TAGLINE', 'Watch. Follow. Earn.'),

    'meta_title' => env('EARNING_META_TITLE', 'ReapersGrowth — Watch Videos & Complete Tasks for PKR'),

    'meta_description' => env('EARNING_META_DESCRIPTION', 'Earn with ReapersGrowth. Watch YouTube videos, follow social accounts, complete simple tasks and withdraw via Easypaisa, JazzCash, UPaisa or NayaPay.'),

    'currency' => env('EARNING_CURRENCY', 'PKR'),

    // All daily task locks and "unlock after 12am" logic uses this timezone.
    'timezone' => env('EARNING_TIMEZONE', 'Asia/Karachi'),

    // Allowed withdrawal range for a single request, in PKR.
    'min_payout' => (float) env('EARNING_MIN_PAYOUT', 50),
    'max_payout' => (float) env('EARNING_MAX_PAYOUT', 250),

    // Only one withdrawal request is allowed in this window.
    'payout_cooldown_hours' => (int) env('EARNING_PAYOUT_COOLDOWN_HOURS', 24),

    // Admin fee kept from each withdrawal. The user is debited the full amount,
    // while the payout request shown to admin is the amount after this fee.
    'payout_admin_fee_percent' => (float) env('EARNING_PAYOUT_ADMIN_FEE_PERCENT', 10),

    'support_email' => env('EARNING_SUPPORT_EMAIL', 'support@earnpk.test'),

    'signup_bonus' => (float) env('EARNING_SIGNUP_BONUS', 0),

    // A task attempt that is not completed within this many hours is reset.
    'attempt_timeout_hours' => (int) env('EARNING_ATTEMPT_TIMEOUT_HOURS', 6),

    /*
    |--------------------------------------------------------------------------
    | User Campaigns
    |--------------------------------------------------------------------------
    |
    | Users pay from their wallet to publish tasks for other users. Every
    | campaign action (subscribe, follow, like or video view) pays the earner
    | 2 PKR and the admin 1 PKR, so 100 actions cost the owner 300 PKR.
    | Owners choose any quantity from 1 up, even a single action.
    |
    */

    'campaign' => [
        'min_quantity' => (int) env('EARNING_CAMPAIGN_MIN_QUANTITY', 1),
        'max_quantity' => (int) env('EARNING_CAMPAIGN_MAX_QUANTITY', 100000),

        // Price paid to each earner for one action.
        'action_reward' => (float) env('EARNING_CAMPAIGN_ACTION_REWARD', 2),

        // Admin fee charged for every delivered action, on top of the reward.
        'action_admin_fee' => (float) env('EARNING_CAMPAIGN_ACTION_ADMIN_FEE', 1),

        // Video campaigns still require a minimum watch time, but every view
        // pays the earner the same flat reward as other campaign actions.
        'video_min_seconds' => (int) env('EARNING_CAMPAIGN_VIDEO_MIN_SECONDS', 60),
        'video_max_seconds' => (int) env('EARNING_CAMPAIGN_VIDEO_MAX_SECONDS', 3600),
        'video_base_reward' => (float) env('EARNING_CAMPAIGN_VIDEO_BASE_REWARD', 2),
        'video_extra_reward' => (float) env('EARNING_CAMPAIGN_VIDEO_EXTRA_REWARD', 0),
        'video_step_seconds' => (int) env('EARNING_CAMPAIGN_VIDEO_STEP_SECONDS', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Wallet Deposits (Cashmaal)
    |--------------------------------------------------------------------------
    */

    'deposit' => [
        'min' => (float) env('EARNING_DEPOSIT_MIN', 100),
        'max' => (float) env('EARNING_DEPOSIT_MAX', 100000),

        // User enters the amount they want credited. The gateway is charged
        // this amount plus the admin fee; only the entered amount is credited.
        'admin_fee_percent' => (float) env('EARNING_DEPOSIT_ADMIN_FEE_PERCENT', 10),

        'cashmaal' => [
            'enabled' => (bool) env('CASHMAAL_ENABLED', true),
            'pay_url' => env('CASHMAAL_PAY_URL', 'https://cmaal.com/Pay/'),
            'verify_url' => env('CASHMAAL_VERIFY_URL', 'https://api.cmaal.com/verify_v2'),
            'currency' => env('CASHMAAL_CURRENCY', 'PKR'),
            'pay_method' => env('CASHMAAL_PAY_METHOD', ''),
            'timeout' => (int) env('CASHMAAL_TIMEOUT', 10),
        ],
    ],

];
