<?php

namespace Modules\Vault\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreVaultRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'description'   => ['required', 'string'],
            'total_amount'  => ['required', 'numeric', 'min:100'],
            'interval'      => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'maturity_date' => ['required', 'date', 'after:today'],
        ];
    }

    public function messages():array
    {
        return [
             'description.required'     => 'Description is required.',
            'total_amount.required' => 'amount is required.',
            'total_amount.numeric' => 'amount must be numeric',
            'interval.required'      => 'Interval is reqired',
            'maturity_date.required'=> 'Limit is required',
        ];
    }
}
