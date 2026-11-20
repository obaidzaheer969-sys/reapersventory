<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return NotificationResource::collection(
            $user->notifications()->latest()->paginate(15),
        )->additional([
            'meta' => [
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ])->response();
    }

    public function markRead(Request $request, string $notification): NotificationResource
    {
        $record = $request->user()->notifications()->whereKey($notification)->firstOrFail();

        $record->markAsRead();

        return new NotificationResource($record->refresh());
    }
}
