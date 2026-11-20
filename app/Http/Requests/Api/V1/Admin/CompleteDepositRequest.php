<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CompleteDepositRequest extends FormRequest
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
            'cm_tid' => ['sometimes', 'nullable', 'string', 'max:100'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
