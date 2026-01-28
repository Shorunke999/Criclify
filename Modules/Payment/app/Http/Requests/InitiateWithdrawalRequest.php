<?php

namespace Modules\Payment\Http\Requests;

use Modules\Core\Http\Requests\BaseRequest;

class InitiateWithdrawalRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:100|max:1000000',
            'recipient_code' => 'required|string',
            'reason' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Withdrawal amount is required',
            'amount.numeric' => 'Amount must be a valid number',
            'amount.min' => 'Minimum withdrawal amount is ₦100',
            'amount.max' => 'Maximum withdrawal amount is ₦1,000,000',
            'recipient_code.required' => 'Recipient account is required',
            'recipient_code.string' => 'Invalid recipient code format',
        ];
    }
}
