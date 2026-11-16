<?php

namespace App\Http\Controllers\Earning;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Services\CashmaalGateway;
use App\Services\DepositService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepositController extends Controller
{
    public function __construct(
        private readonly DepositService $deposits,
        private readonly CashmaalGateway $gateway,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('earning.deposits.index', [
            'deposits' => $user->deposits()->latest()->paginate(10),
            'minimum' => (float) config('earning.deposit.min', 100),
            'maximum' => (float) config('earning.deposit.max', 100000),
            'cashmaalEnabled' => $this->gateway->isConfigured(),
            'totalDeposited' => $user->totalDeposited(),
            'walletBalance' => (float) $user->wallet_balance,
        ]);
    }

    public function store(Request $request): View
    {
        $minimum = (float) config('earning.deposit.min', 100);
        $maximum = (float) config('earning.deposit.max', 100000);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:'.$minimum, 'max:'.$maximum],
        ]);

        $deposit = $this->deposits->create($request->user(), (float) $data['amount']);

        return $this->redirectView($deposit);
    }

    public function pay(Request $request, Deposit $deposit): View|RedirectResponse
    {
        abort_unless($deposit->user_id === $request->user()->id, 403);

        if (! $deposit->isPending()) {
            return redirect()
                ->route('deposits.index')
                ->with('status', 'Yeh deposit already '.$deposit->status->label().' hai.');
        }

        if (! $this->gateway->isConfigured()) {
            return redirect()
                ->route('deposits.index')
                ->withErrors(['amount' => 'Cashmaal deposits filhal available nahi hain.']);
        }

        return $this->redirectView($deposit);
    }

    private function redirectView(Deposit $deposit): View
    {
        return view('earning.deposits.redirect', [
            'deposit' => $deposit,
            'action' => $this->gateway->payUrl(),
            'fields' => $this->gateway->payload($deposit),
        ]);
    }
}
