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
            'first_name.required' => 'Pehla naam likhein.',
            'last_name.required' => 'Akhri naam likhein.',
            'email.required' => 'Gmail address likhein.',
            'email.email' => 'Sahi Gmail address likhein.',
            'email.unique' => 'Yeh Gmail pehle se registered hai.',
            'password.required' => 'Password likhein.',
            'password.confirmed' => 'Dono passwords match nahi kar rahe.',
            'terms.accepted' => 'Register karne ke liye Terms and Conditions accept karein.',
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
