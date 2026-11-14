<?php

namespace App\Http\Controllers\Earning;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EarningsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('earning.earnings.index', [
            'attempts' => $user->completedTaskAttempts()
                ->with('task')
                ->latest('completed_at')
                ->paginate(15, ['*'], 'tasks'),
            'transactions' => $user->walletTransactions()
                ->latest()
                ->paginate(15, ['*'], 'transactions'),
            'totalEarned' => $user->totalEarned(),
            'totalWithdrawn' => $user->totalWithdrawn(),
            'pendingPayout' => $user->pendingPayoutTotal(),
            'watchedSeconds' => $user->totalWatchedSeconds(),
        ]);
    }
}
