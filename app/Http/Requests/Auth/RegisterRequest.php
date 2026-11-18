<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:127'],
            'last_name' => ['required', 'string', 'max:127'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Enter your first name.',
            'last_name.required' => 'Enter your last name.',
            'email.required' => 'Enter your Gmail address.',
            'email.email' => 'Enter a valid Gmail address.',
            'email.unique' => 'This Gmail is already registered.',
            'password.required' => 'Enter a password.',
            'password.confirmed' => 'The passwords do not match.',
            'terms.accepted' => 'Accept the Terms and Conditions to register.',
        ];
    }

    /**
     * Strip any attempt to inject privileged roles via mass assignment vectors.
     */
    protected function prepareForValidation(): void
    {
        $this->request->remove('role');
        $this->request->remove('business_id');
        $this->request->remove('is_active');
        $this->request->remove('wallet_balance');
    }
}
