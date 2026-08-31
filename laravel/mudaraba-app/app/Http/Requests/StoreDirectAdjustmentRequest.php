<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDirectAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $mode = $this->input('mode');

        return [
            'mode' => ['required', Rule::in(['investor_wise', 'as_per_invest'])],
            'sector_id' => ['required', 'exists:sectors,id'],
            'transaction_date' => ['required', 'date'],
            'profit_month' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:500'],

            // investor_wise mode — single investor + total_amount
            'investor_id' => [
                Rule::requiredIf($mode === 'investor_wise'),
                'nullable',
                'exists:investors,id',
            ],

            // Both modes require total_amount (the sector-side debit)
            'total_amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],

            // as_per_invest mode — bulk investor_items
            'investor_items' => [
                Rule::requiredIf($mode === 'as_per_invest'),
                'nullable',
                'array',
            ],
            'investor_items.*.investor_id' => ['required_with:investor_items', 'exists:investors,id'],
            'investor_items.*.amount' => ['required_with:investor_items', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'mode.required' => 'Please select a mode (Investor-Wise or As-Per-Invest).',
            'mode.in' => 'Mode must be investor_wise or as_per_invest.',
            'sector_id.required' => 'A sector is required for direct funding.',
            'sector_id.exists' => 'The selected sector does not exist.',
            'investor_id.required' => 'An investor is required for investor-wise mode.',
            'investor_id.exists' => 'The selected investor does not exist.',
            'total_amount.required' => 'Total amount is required.',
            'total_amount.min' => 'Total amount must be greater than zero.',
            'investor_items.required' => 'Investor distribution is required for as-per-invest mode.',
            'investor_items.array' => 'Investor distribution must be a list.',
            'investor_items.*.investor_id.required_with' => 'Each investor row must have an investor.',
            'investor_items.*.investor_id.exists' => 'A selected investor does not exist.',
            'investor_items.*.amount.required_with' => 'Each investor row must have an amount.',
            'investor_items.*.amount.min' => 'Each investor amount must be greater than zero.',
        ];
    }
}
