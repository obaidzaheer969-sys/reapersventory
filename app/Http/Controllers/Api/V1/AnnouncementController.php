<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AnnouncementResource;
use App\Models\Announcement;
use App\Models\User;
use App\Services\AnnouncementNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function __invoke(
        Request $request,
        AnnouncementNotificationService $announcementNotifications,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $unreadCount = $announcementNotifications->unreadAnnouncementIds($user)->count();

        $announcements = Announcement::query()
            ->visible()
            ->latestFirst()
            ->paginate(12);

        $announcementNotifications->markRead($user);

        return AnnouncementResource::collection($announcements)
            ->additional([
                'meta' => [
                    'unread_count' => $unreadCount,
                ],
            ])
            ->response();
    }
}
