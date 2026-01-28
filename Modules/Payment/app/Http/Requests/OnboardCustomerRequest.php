<?php

namespace Modules\Payment\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Http\Requests\BaseRequest;

class OnboardCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $provider = config('app.bank_driver');

        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
        ];

        // Add provider-specific rules
        if ($provider === 'anchor') {
            $rules = array_merge($rules, [
                'middle_name' => 'nullable|string|max:255',
                'address_line1' => 'required|string|max:255',
                'address_line2' => 'nullable|string|max:255',
                'city' => 'required|string|max:100',
                'state' => 'required|string|max:100',
                'postal_code' => 'required|string|max:20',
                'country' => 'required|string|size:2|in:NG',
                'dob' => 'required|date|before:18 years ago',
                'gender' => 'required|in:male,female,MALE,FEMALE',
                'bvn' => 'required|string|size:11',
            ]);
        }

        // Optional KYC fields for Paystack
        if ($provider === 'paystack') {
            $rules = array_merge($rules, [
                'bvn' => 'required|string|size:11',
                'dob' => 'required|date|before:18 years ago',
                'gender' => 'required|in:male,female',
                'account_number' => 'required_with:bvn|string|size:10',
                'bank_code' => 'required_with:bvn|string',
            ]);
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required',
            'last_name.required' => 'Last name is required',
            'phone_number.required' => 'Phone number is required',
            'bvn.size' => 'BVN must be exactly 11 digits',
            'dob.before' => 'You must be at least 18 years old',
            'country.in' => 'Currently only Nigeria (NG) is supported',
        ];
    }
}
