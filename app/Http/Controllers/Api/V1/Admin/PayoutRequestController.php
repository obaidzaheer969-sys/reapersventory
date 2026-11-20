<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\PayoutStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ApprovePayoutRequestRequest;
use App\Http\Requests\Api\V1\Admin\RejectPayoutRequestRequest;
use App\Http\Resources\Api\V1\PayoutRequestResource;
use App\Models\PayoutRequest;
use App\Services\PayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PayoutRequestController extends Controller
{
    public function __construct(
        private readonly PayoutService $payouts,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::enum(PayoutStatus::class)],
            'search' => ['sometimes', 'string', 'max:127'],
        ]);

        $search = isset($validated['search']) ? trim((string) $validated['search']) : '';

        $payouts = PayoutRequest::query()
            ->with('user:id,name,email')
            ->when(isset($validated['status']), fn ($query) => $query
                ->where('status', $validated['status']))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(fn ($query) => $query
                    ->where('account_name', 'like', '%'.$search.'%')
                    ->orWhere('account_number', 'like', '%'.$search.'%')
                    ->orWhereHas('user', fn ($query) => $query
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')));
            })
            ->latest()
            ->paginate(15);

        return PayoutRequestResource::collection($payouts)->response();
    }

    public function approve(
        ApprovePayoutRequestRequest $request,
        PayoutRequest $payoutRequest,
    ): PayoutRequestResource {
        $notes = $request->string('notes')->trim()->toString();

        $payout = $this->payouts->markPaid(
            $payoutRequest,
            $request->user(),
            $notes !== '' ? $notes : null,
        );

        return new PayoutRequestResource($payout);
    }

    public function reject(
        RejectPayoutRequestRequest $request,
        PayoutRequest $payoutRequest,
    ): PayoutRequestResource {
        return new PayoutRequestResource(
            $this->payouts->reject(
                $payoutRequest,
                $request->user(),
                $request->string('reason')->trim()->toString(),
            ),
        );
    }
}
