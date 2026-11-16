<?php

namespace App\Http\Controllers\Earning;

use App\Enums\PayoutProvider;
use App\Http\Controllers\Controller;
use App\Models\PayoutAccount;
use App\Services\PayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PayoutController extends Controller
{
    public function __construct(
        private readonly PayoutService $payouts,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('earning.wallet.index', [
            'accounts' => $user->payoutAccounts()->orderByDesc('is_default')->latest()->get(),
            'payouts' => $user->payoutRequests()->latest()->paginate(10, ['*'], 'payouts'),
            'transactions' => $user->walletTransactions()->latest()->limit(12)->get(),
            'minimumPayout' => (float) config('earning.min_payout', 500),
            'totalWithdrawn' => $user->totalWithdrawn(),
            'pendingPayout' => $user->pendingPayoutTotal(),
            'recentDeposits' => $user->deposits()->latest()->limit(5)->get(),
            'totalDeposited' => $user->totalDeposited(),
        ]);
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $this->validateAccount($request);

        if ($user->payoutAccounts()->count() >= 10) {
            throw ValidationException::withMessages([
                'account_number' => 'Aap zyada se zyada 10 wallets save kar sakte hain.',
            ]);
        }

        $this->ensureUniqueAccount($user->id, $data['provider'], $data['account_number']);

        DB::transaction(function () use ($user, $data): void {
            $makeDefault = (bool) ($data['is_default'] ?? false) || $user->payoutAccounts()->count() === 0;

            if ($makeDefault) {
                $user->payoutAccounts()->update(['is_default' => false]);
            }

            $user->payoutAccounts()->create([
                'provider' => $data['provider'],
                'account_name' => $data['account_name'],
                'account_number' => trim($data['account_number']),
                'is_default' => $makeDefault,
            ]);
        });

        return back()->with('status', 'Wallet account save ho gaya.');
    }

    public function updateAccount(Request $request, PayoutAccount $account): RedirectResponse
    {
        abort_unless($account->user_id === $request->user()->id, 403);

        $data = $this->validateAccount($request);
        $this->ensureUniqueAccount($request->user()->id, $data['provider'], $data['account_number'], $account->id);

        DB::transaction(function () use ($request, $account, $data): void {
            $makeDefault = (bool) ($data['is_default'] ?? false) || $account->is_default;

            if ($makeDefault) {
                $request->user()->payoutAccounts()->whereKeyNot($account->id)->update(['is_default' => false]);
            }

            $account->update([
                'provider' => $data['provider'],
                'account_name' => $data['account_name'],
                'account_number' => trim($data['account_number']),
                'is_default' => $makeDefault,
            ]);
        });

        return back()->with('status', 'Wallet account update ho gaya.');
    }

    public function destroyAccount(Request $request, PayoutAccount $account): RedirectResponse
    {
        abort_unless($account->user_id === $request->user()->id, 403);

        DB::transaction(function () use ($request, $account): void {
            $wasDefault = $account->is_default;
            $account->delete();

            if ($wasDefault) {
                $request->user()->payoutAccounts()->latest()->first()?->update(['is_default' => true]);
            }
        });

        return back()->with('status', 'Wallet account delete ho gaya.');
    }

    public function storePayout(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'payout_account_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:1'],
        ], [], [
            'payout_account_id' => 'wallet account',
            'amount' => 'amount',
        ]);

        $account = $user->payoutAccounts()->find($data['payout_account_id']);

        if (! $account) {
            throw ValidationException::withMessages([
                'payout_account_id' => 'Pehle wallet account save karein.',
            ]);
        }

        $payout = $this->payouts->requestPayout($user, $account, (float) $data['amount']);

        return back()->with('status', 'Payout request #'.$payout->id.' bhej di gayi. Admin review ke baad payment bhejega.');
    }

    /**
     * @return array{provider: string, account_name: string, account_number: string, is_default?: bool}
     */
    private function validateAccount(Request $request): array
    {
        /** @var array{provider: string, account_name: string, account_number: string, is_default?: bool} $data */
        $data = $request->validate([
            'provider' => ['required', Rule::enum(PayoutProvider::class)],
            'account_name' => ['required', 'string', 'max:120'],
            'account_number' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s]{7,30}$/'],
            'is_default' => ['nullable', 'boolean'],
        ], [
            'account_number.regex' => 'Account number sirf digits, +, - aur spaces par mushtamil ho.',
        ]);

        return $data;
    }

    private function ensureUniqueAccount(int $userId, string $provider, string $accountNumber, ?int $ignoreId = null): void
    {
        $exists = PayoutAccount::query()
            ->where('user_id', $userId)
            ->where('provider', $provider)
            ->where('account_number', trim($accountNumber))
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'account_number' => 'Yeh wallet account pehle se save hai.',
            ]);
        }
    }
}
