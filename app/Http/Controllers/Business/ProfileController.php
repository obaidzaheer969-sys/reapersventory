<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $business = $request->user()->business;

        abort_unless($request->user()->can('update', $business), 403);

        return view('business.profile', [
            'business' => $business,
        ]);
    }
}
