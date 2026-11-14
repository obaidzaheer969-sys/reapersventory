<?php

namespace App\Http\Controllers\Earning;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        return view('earning.announcements.index', [
            'announcements' => Announcement::query()
                ->visible()
                ->latestFirst()
                ->paginate(10),
        ]);
    }
}
