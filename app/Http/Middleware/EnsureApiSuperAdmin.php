<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Only a Super Admin can access this API.'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
