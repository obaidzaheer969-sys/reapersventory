<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreWithdrawalRequest extends FormRequest
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
        $minimum = (float) config('earning.min_payout', 50);
        $maximum = (float) config('earning.max_payout', 250);

        return [
            'payout_account_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:'.$minimum, 'max:'.$maximum],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $minimum = (float) config('earning.min_payout', 50);
        $maximum = (float) config('earning.max_payout', 250);

        return [
            'amount.min' => 'Minimum payout is Coins '.number_format($minimum, 0).'.',
            'amount.max' => 'Maximum payout is Coins '.number_format($maximum, 0).'.',
        ];
    }
}
