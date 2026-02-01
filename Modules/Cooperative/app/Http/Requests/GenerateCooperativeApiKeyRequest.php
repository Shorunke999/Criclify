<?php

namespace Modules\Cooperative\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateCooperativeApiKeyRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'abilities' => 'nullable|array',
            'abilities.*' => 'string|exists:cooperative_api_permissions,permission_name',
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
