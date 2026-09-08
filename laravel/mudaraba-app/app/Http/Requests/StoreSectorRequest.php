<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSectorRequest extends FormRequest
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
            'status' => ['required', 'in:active,inactive,closed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Sector name is required.',
            'name.min' => 'Name must be at least 3 characters.',
            'status.in' => 'Status must be active, inactive, or closed.',
        ];
    }
}
