<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListReferenceCountriesRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
     public function rules(): array
    {
        return [
            'search'        => 'nullable|string|max:100',
            'currency_code' => 'nullable|string|size:3',
            'per_page'      => 'nullable|integer|min:1|max:100',
            'page'          => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'currency_code.size' => 'Currency code must be 3 characters (e.g. NGN)',
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
