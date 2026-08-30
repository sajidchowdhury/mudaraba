<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvestmentTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'investor_id' => ['required', 'exists:investors,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'type' => ['required', 'in:add,withdraw'],
            'transaction_month' => ['required', 'date'],
            'transaction_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'investor_id.required' => 'Please select an investor.',
            'investor_id.exists' => 'The selected investor does not exist.',
            'amount.required' => 'Amount is required.',
            'amount.min' => 'Amount must be greater than zero.',
            'type.required' => 'Please select a transaction type.',
            'type.in' => 'Type must be add or withdraw.',
            'transaction_month.required' => 'Transaction month is required.',
            'transaction_date.required' => 'Transaction date is required.',
        ];
    }

    /**
     * Configure the validator to enforce backdate permissions.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            if (! $user) {
                return;
            }

            // Enforce backdate permission: if the transaction_date is in the past
            // (more than 7 days ago), the user must have can_backdate permission.
            $transactionDate = $this->date('transaction_date');
            if ($transactionDate && $transactionDate->lt(now()->subDays(7))) {
                if (! $user->isSuperadmin() && ! $user->canBackdate('investments.index')) {
                    $validator->errors()->add(
                        'transaction_date',
                        'You do not have permission to backdate transactions beyond 7 days.'
                    );
                }
            }
        });
    }
}
