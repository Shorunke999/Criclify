<?php

namespace Modules\Payment\Http\Requests;

use Modules\Core\Http\Requests\BaseRequest;

class VerifyBankAccountRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_number' => 'required|string|size:10|regex:/^[0-9]+$/',
            'bank_code' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'account_number.required' => 'Account number is required',
            'account_number.size' => 'Account number must be 10 digits',
            'account_number.regex' => 'Account number must contain only numbers',
            'bank_code.required' => 'Bank code is required',
        ];
    }
}
