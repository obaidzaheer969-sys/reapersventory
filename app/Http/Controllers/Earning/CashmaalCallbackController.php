<?php

namespace App\Http\Controllers\Earning;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Services\DepositService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashmaalCallbackController extends Controller
{
    public function success(Request $request, DepositService $deposits): View
    {
        $deposit = $this->resolveDeposit($request, $deposits);

        if ($deposit && ! $deposit->isCompleted()) {
            $cmTid = (string) $request->input('CM_TID', $deposit->cm_tid ?? '');

            $deposits->verifyCallback($deposit, $cmTid);
            $deposit->refresh();
        }

        return view('earning.deposits.callback', [
            'deposit' => $deposit,
            'isSuccess' => (bool) $deposit?->isCompleted(),
            'isCancelled' => false,
        ]);
    }

    public function cancel(Request $request, DepositService $deposits): View
    {
        $deposit = $this->resolveDeposit($request, $deposits);

        if ($deposit && $deposit->isPending()) {
            $deposits->markCancelled($deposit);
            $deposit->refresh();
        }

        return view('earning.deposits.callback', [
            'deposit' => $deposit,
            'isSuccess' => false,
            'isCancelled' => true,
        ]);
    }

    private function resolveDeposit(Request $request, DepositService $deposits): ?Deposit
    {
        $orderId = (string) $request->input('order_id', '');

        if (filled($orderId)) {
            return $deposits->findByOrderId($orderId);
        }

        $cmTid = (string) $request->input('CM_TID', '');

        return filled($cmTid) ? $deposits->findByCmTid($cmTid) : null;
    }
}
