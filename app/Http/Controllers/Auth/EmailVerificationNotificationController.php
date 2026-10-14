<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\EmailVerificationLink;
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

        $verificationUrl = EmailVerificationLink::for($request->user(), $request);
        $mailSent = false;
        $mailError = null;

        if (config('mail.default') === 'log') {
            $mailError = 'Mailer is "log" (no inbox delivery). Use the button below to verify, or set SMTP in .env.';
        } else {
            try {
                $request->user()->sendEmailVerificationNotification();
                $mailSent = true;
            } catch (Throwable $e) {
                report($e);
                $mailError = 'Email could not be delivered by the server. Use the verify button below instead.';
            }
        }

        return back()
            ->with('status', $mailSent ? 'verification-link-sent' : 'verification-link-ready')
            ->with('verification_url', $verificationUrl)
            ->withErrors($mailError ? ['email' => $mailError] : []);
    }
}
