<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvestorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'reference' => ['nullable', 'string', 'max:120'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'deed_ratio' => ['required', 'in:60,80,100'],
            'start_profit_month' => ['nullable', 'date'],
            'end_profit_month' => ['nullable', 'date', 'after_or_equal:start_profit_month'],
            'status' => ['required', 'in:active,inactive,closed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Investor name is required.',
            'name.min' => 'Name must be at least 3 characters.',
            'deed_ratio.required' => 'Please select a deed tier.',
            'deed_ratio.in' => 'Deed ratio must be 60, 80, or 100.',
            'end_profit_month.after_or_equal' => 'End profit month must be after or equal to start.',
        ];
    }
}
