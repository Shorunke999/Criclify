<?php

namespace Modules\Waitlist\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WaitlistExportRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
     public function rules(): array
    {
        return [
            'referral_code' => 'nullable|string',
            'has_referral'  => 'nullable|boolean',
            'joined_from'   => 'nullable|date',
            'joined_to'     => 'nullable|date|after_or_equal:joined_from',
        ];
    }

    public function messages(): array
    {
        return [
            'joined_to.after_or_equal' =>
                'The joined_to date must be after or equal to joined_from.',
            'has_referral.boolean' =>
                'has_referral must be true or false.',
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
