<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Admin\EarnerResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EarnerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->toString();

        $earners = User::query()
            ->where('role', UserRole::Earner->value)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(fn ($query) => $query
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%'));
            })
            ->when($request->filled('is_active'), fn ($query) => $query
                ->where('is_active', $request->boolean('is_active')))
            ->latest()
            ->paginate(15);

        return EarnerResource::collection($earners)->response();
    }
}
