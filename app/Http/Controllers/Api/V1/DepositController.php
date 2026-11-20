<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\InitiateDepositRequest;
use App\Http\Resources\Api\V1\DepositResource;
use App\Models\PaymentMethod;
use App\Services\CashmaalGateway;
use App\Services\DepositService;
use Illuminate\Http\JsonResponse;

class DepositController extends Controller
{
    public function store(
        InitiateDepositRequest $request,
        DepositService $deposits,
        CashmaalGateway $gateway,
    ): JsonResponse {
        $data = $request->safe()->only(['payment_method_id', 'amount']);
        $amount = (float) $data['amount'];

        $paymentMethod = isset($data['payment_method_id'])
            ? PaymentMethod::query()->active()->find($data['payment_method_id'])
            : null;

        if (isset($data['payment_method_id']) && $paymentMethod === null) {
            abort(404, 'Payment method not found.');
        }

        $deposit = $deposits->create($request->user(), $amount);

        if ($paymentMethod !== null) {
            $deposit->setAttribute('api_payment_method_id', $paymentMethod->id);
        }

        $deposit->setAttribute('api_quote', $deposits->quote($amount));
        $deposit->setAttribute('api_checkout', [
            'url' => $gateway->payUrl(),
            'fields' => $gateway->payload($deposit),
        ]);

        return (new DepositResource($deposit))
            ->response()
            ->setStatusCode(201);
    }
}
