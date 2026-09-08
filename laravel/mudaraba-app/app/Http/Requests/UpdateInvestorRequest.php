<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvestorRequest extends FormRequest
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
}
