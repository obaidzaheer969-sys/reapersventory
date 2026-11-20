<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiateDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $minimum = (float) config('earning.deposit.min', 100);
        $maximum = (float) config('earning.deposit.max', 100000);

        return [
            'payment_method_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('payment_methods', 'id')->where('is_active', true),
            ],
            'amount' => ['required', 'numeric', 'min:'.$minimum, 'max:'.$maximum],
        ];
    }
}
