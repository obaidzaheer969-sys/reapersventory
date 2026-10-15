<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if (! Schema::hasTable('notifications')) {
            return view('business.notifications.index', [
                'notifications' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20),
                'unreadCount' => 0,
            ]);
        }

        $notifications = $user->notifications()->latest()->paginate(20);

        return view('business.notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        if (! Schema::hasTable('notifications')) {
            return redirect()->route('business.notifications.index');
        }

        $notification = $request->user()
            ->notifications()
            ->whereKey($id)
            ->firstOrFail();

        $notification->markAsRead();

        $url = $notification->data['url'] ?? route('business.notifications.index');

        return redirect()->to($url);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('notifications')) {
            return back();
        }

        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'All notifications marked as read.');
    }
}
