<?php

return [
    'grace_period_days' => (int) env('SUBSCRIPTION_GRACE_PERIOD_DAYS', 7),
    'expiring_soon_days' => (int) env('SUBSCRIPTION_EXPIRING_SOON_DAYS', 3),
    'payment_proof_disk' => env('PAYMENT_PROOF_DISK', 'local'),
    'payment_proof_max_kb' => (int) env('PAYMENT_PROOF_MAX_KB', 5120),
    'payment_proof_mimes' => ['jpg', 'jpeg', 'png', 'pdf', 'webp'],
    // Email verification flow is disabled; registrations are auto-verified.
    'email_verification_required' => false,
    // Secret for public/rv-update.php (browser / webhook one-click deploy).
    'deploy_secret' => (string) env('DEPLOY_SECRET', ''),
];
