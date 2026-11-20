<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Admin\TokenResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class TokenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->toString();

        $tokens = PersonalAccessToken::query()
            ->with('tokenable')
            ->when($search !== '', fn ($query) => $query
                ->where('name', 'like', '%'.$search.'%'))
            ->latest('last_used_at')
            ->latest('id')
            ->paginate(15);

        return TokenResource::collection($tokens)->response();
    }

    public function destroy(string $token): JsonResponse
    {
        $record = PersonalAccessToken::query()->find($token);

        abort_unless($record !== null, 404, 'API token not found.');

        $record->delete();

        return response()->json(['message' => 'API token revoked.']);
    }
}
