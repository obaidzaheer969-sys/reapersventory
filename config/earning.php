<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Earning Platform
    |--------------------------------------------------------------------------
    */

    'brand' => env('EARNING_BRAND', 'Earn To PKR'),

    'tagline' => env('EARNING_TAGLINE', 'Watch. Follow. Earn.'),

    'currency' => env('EARNING_CURRENCY', 'PKR'),

    // All daily task locks and "unlock after 12am" logic uses this timezone.
    'timezone' => env('EARNING_TIMEZONE', 'Asia/Karachi'),

    // Minimum wallet balance a user can withdraw in one request.
    'min_payout' => (float) env('EARNING_MIN_PAYOUT', 500),

    'support_email' => env('EARNING_SUPPORT_EMAIL', 'support@earnpk.test'),

    'signup_bonus' => (float) env('EARNING_SIGNUP_BONUS', 0),

    // A task attempt that is not completed within this many hours is reset.
    'attempt_timeout_hours' => (int) env('EARNING_ATTEMPT_TIMEOUT_HOURS', 6),

];
