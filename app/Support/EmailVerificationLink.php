<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class EmailVerificationLink
{
    public static function for(User $user, ?Request $request = null): string
    {
        $root = $request
            ? $request->getSchemeAndHttpHost()
            : rtrim((string) config('app.url'), '/');

        $previousRoot = URL::to('/');
        URL::forceRootUrl($root);

        try {
            return URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes((int) Config::get('auth.verification.expire', 60)),
                [
                    'id' => $user->getKey(),
                    'hash' => sha1($user->getEmailForVerification()),
                ],
            );
        } finally {
            URL::forceRootUrl($previousRoot);
        }
    }
}
