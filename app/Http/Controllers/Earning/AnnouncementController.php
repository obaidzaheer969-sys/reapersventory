<?php

namespace App\Http\Controllers\Earning;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\User;
use App\Services\AnnouncementNotificationService;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(AnnouncementNotificationService $announcementNotifications): View
    {
        /** @var User $user */
        $user = auth()->user();

        $unreadAnnouncementIds = $announcementNotifications->unreadAnnouncementIds($user);
        $announcementNotifications->markRead($user);

        return view('earning.announcements.index', [
            'announcements' => Announcement::query()
                ->visible()
                ->latestFirst()
                ->paginate(10),
            'unreadAnnouncementIds' => $unreadAnnouncementIds,
        ]);
    }
}
