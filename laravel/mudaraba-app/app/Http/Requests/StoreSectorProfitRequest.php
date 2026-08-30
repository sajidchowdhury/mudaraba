<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSectorProfitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'profit_month' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sector_id' => ['required', 'exists:sectors,id'],
            'items.*.estimated_profit' => ['required', 'numeric', 'min:0'],
            'items.*.actual_profit' => ['nullable', 'numeric', 'min:0'],
            'finalize' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'profit_month.required' => 'Profit month is required.',
            'items.required' => 'At least one sector profit entry is required.',
            'items.*.sector_id.required' => 'Sector ID is required for each entry.',
            'items.*.sector_id.exists' => 'One or more selected sectors do not exist.',
            'items.*.estimated_profit.required' => 'Estimated profit is required.',
            'items.*.estimated_profit.numeric' => 'Estimated profit must be a number.',
        ];
    }
}
