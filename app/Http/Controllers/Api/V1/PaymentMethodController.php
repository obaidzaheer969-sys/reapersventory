<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PaymentMethodResource;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;

class PaymentMethodController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return PaymentMethodResource::collection(PaymentMethod::query()->active()->get())
            ->additional([
                'meta' => [
                    'currency' => (string) config('earning.currency', 'PKR'),
                ],
            ])
            ->response();
    }
}
