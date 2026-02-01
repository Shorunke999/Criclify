<?php

namespace Modules\Cooperative\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCooperativeMemberRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => 'nullable|exists:users,id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'bvn' => 'nullable|string|max:20',
            'phone_number' => 'required|string|max:20',
            'email' => 'required|email',
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
