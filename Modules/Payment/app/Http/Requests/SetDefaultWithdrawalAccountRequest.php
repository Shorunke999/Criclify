<?php

namespace Modules\Payment\Http\Requests;

use Modules\Core\Http\Requests\BaseRequest;


class SetDefaultWithdrawalAccountRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_code' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'recipient_code.required' => 'Recipient code is required',
        ];
    }
}
