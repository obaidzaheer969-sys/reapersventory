<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'name' => trim($request->string('first_name')->toString().' '.$request->string('last_name')->toString()),
            'email' => $request->string('email')->lower()->toString(),
            'password' => $request->string('password')->toString(),
            'role' => UserRole::Earner,
            'business_id' => null,
            'is_active' => true,
            'wallet_balance' => 0,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return $this->tokenResponse($user, $request->string('device_name')->toString(), 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('email', $request->string('email')->lower()->toString())
            ->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        return $this->tokenResponse($user, $request->string('device_name')->toString());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    private function tokenResponse(User $user, string $deviceName, int $status = 200): JsonResponse
    {
        $token = $user->createToken($deviceName !== '' ? $deviceName : 'reapersgrowth-mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], $status);
    }
}
