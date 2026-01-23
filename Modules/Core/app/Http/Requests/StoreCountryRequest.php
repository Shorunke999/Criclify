<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCountryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
         return [
            'name' => 'required|string',
            'iso_code' => 'required|string|size:2',

            'currency_name' => 'required|string',
            'currency_code' => 'required|string|size:3',
            'currency_symbol' => 'nullable|string',

            'platform_fee_percentage' => 'required|numeric|min:0|max:100',
            'circle_creation_fee_percentage' => 'required|numeric|min:0|max:100',
            'is_active' => 'required|boolean'
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
