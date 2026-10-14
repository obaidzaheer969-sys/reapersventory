<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class EmailVerificationNotificationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('business.dashboard'));
        }

        if (config('mail.default') === 'log') {
            return back()->withErrors([
                'email' => 'Mail is set to "log" mode. On hosting set MAIL_MAILER=smtp (or sendmail) in .env, then run php artisan config:clear.',
            ]);
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'email' => 'Could not send verification email. Check SMTP settings (MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS) and that APP_URL matches your live domain.',
            ]);
        }

        return back()->with('status', 'verification-link-sent');
    }
}
