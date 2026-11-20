<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiEarner
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isEarner()) {
            return response()->json(['message' => 'This app is only available to earner accounts.'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
