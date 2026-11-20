<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\DepositStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\CompleteDepositRequest;
use App\Http\Requests\Api\V1\Admin\FailDepositRequest;
use App\Http\Resources\Api\V1\DepositResource;
use App\Models\Deposit;
use App\Services\DepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepositController extends Controller
{
    public function __construct(
        private readonly DepositService $deposits,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::enum(DepositStatus::class)],
            'search' => ['sometimes', 'string', 'max:127'],
        ]);

        $search = isset($validated['search']) ? trim((string) $validated['search']) : '';

        $deposits = Deposit::query()
            ->with('user:id,name,email')
            ->when(isset($validated['status']), fn ($query) => $query
                ->where('status', $validated['status']))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(fn ($query) => $query
                    ->where('order_id', 'like', '%'.$search.'%')
                    ->orWhere('cm_tid', 'like', '%'.$search.'%')
                    ->orWhereHas('user', fn ($query) => $query
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')));
            })
            ->latest()
            ->paginate(15);

        return DepositResource::collection($deposits)->response();
    }

    public function complete(CompleteDepositRequest $request, Deposit $deposit): DepositResource
    {
        $data = $request->safe()->only(['cm_tid', 'notes']);
        $cmTid = isset($data['cm_tid']) ? trim((string) $data['cm_tid']) : '';

        $deposit = $this->deposits->complete(
            $deposit,
            $cmTid !== '' ? $cmTid : null,
            ['manual_completion' => true, 'via' => 'api'],
        );

        $notes = isset($data['notes']) ? trim((string) $data['notes']) : '';

        if ($notes !== '') {
            $deposit->forceFill(['admin_notes' => $notes])->save();
        }

        return new DepositResource($deposit);
    }

    public function fail(FailDepositRequest $request, Deposit $deposit): DepositResource
    {
        $deposit = $this->deposits->markFailed(
            $deposit,
            $request->string('notes')->trim()->toString(),
            ['manual_failure' => true, 'via' => 'api'],
        );

        return new DepositResource($deposit);
    }
}
