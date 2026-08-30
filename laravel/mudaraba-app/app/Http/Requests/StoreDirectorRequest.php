<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDirectorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_my' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Director name is required.',
            'name.min' => 'Name must be at least 3 characters.',
        ];
    }
}
