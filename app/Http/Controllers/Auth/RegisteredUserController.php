<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\RegisterBusinessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, RegisterBusinessService $registrar): RedirectResponse
    {
        $user = $registrar->register($request->safe()->only([
            'name',
            'email',
            'password',
            'business_name',
            'phone',
        ]));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
