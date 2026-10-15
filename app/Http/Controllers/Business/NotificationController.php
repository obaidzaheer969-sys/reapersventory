<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Services\LowStockMonitor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class NotificationController extends Controller
{
    public function index(Request $request, LowStockMonitor $lowStockMonitor): View
    {
        $user = $request->user();

        if ($user->business_id) {
            try {
                $lowStockMonitor->checkBusiness((int) $user->business_id);
            } catch (Throwable) {
                // ignore
            }
        }

        if (! Schema::hasTable('notifications')) {
            return view('business.notifications.index', [
                'notifications' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20),
                'unreadCount' => 0,
                'notificationsReady' => false,
            ]);
        }

        $notifications = $user->notifications()->latest()->paginate(20);

        return view('business.notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $user->unreadNotifications()->count(),
            'notificationsReady' => true,
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
