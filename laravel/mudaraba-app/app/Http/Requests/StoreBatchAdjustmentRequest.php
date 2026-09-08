<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBatchAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:fund_a,fund_b'],
            'transaction_date' => ['required', 'date'],
            'profit_month' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:500'],
            'investor_items' => ['array'],
            'investor_items.*.investor_id' => ['required_with:investor_items', 'exists:investors,id'],
            'investor_items.*.amount' => ['required_with:investor_items', 'numeric', 'min:0'],
            'sector_items' => ['array'],
            'sector_items.*.sector_id' => ['required_with:sector_items', 'exists:sectors,id'],
            'sector_items.*.amount' => ['required_with:sector_items', 'numeric', 'min:0'],
        ];
    }
}
