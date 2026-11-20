<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\PayoutProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayoutAccountRequest extends FormRequest
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
        return [
            'provider' => ['required', Rule::enum(PayoutProvider::class)],
            'account_name' => ['required', 'string', 'max:120'],
            'account_number' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s]{7,30}$/'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'account_number.regex' => 'The account number may contain only digits, +, -, and spaces.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->request->remove('user_id');
        $this->request->remove('is_active');
    }
}
