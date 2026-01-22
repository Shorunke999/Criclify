<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatorInviteRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'occupation' => 'required|string',
            'phone_number' => 'required|string',
            'country_id' => 'required|exists:countries,id'
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

     public function messages(): array
    {
        return [
            'first_name.required'     => 'Full name is required.',
            'first_name.required'       => 'Full name is required.',
            'email.required'    => 'Email address is required.',
            'email.email'       => 'Please provide a valid email address.',
            'email.unique'      => 'This email is already registered.',
            'occupation.required' => 'Occupation is required',
            'country_id.required' => "Country is required",
            'phone_number.required' => 'phone_number is required',
            'ndpr_consent.required' => 'You must agree to the NDPR data processing policy.',
            'ndpr_consent.accepted' => 'You must accept the NDPR data processing policy to continue.',
        ];
    }
}
