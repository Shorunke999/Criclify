<?php
namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatorInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Common Fields (All Roles)
            |--------------------------------------------------------------------------
            */
            'email' => ['required', 'email'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            // 'occupation' => ['required', 'string'],
            'phone_number' => ['required', 'string'],
            'country_id' => ['required', 'exists:countries,id'],

            // System role (DO NOT change meaning)
            'role' => [
                'required',
                Rule::exists('roles', 'name'), // creator | cooperative
            ],

            /*
            |--------------------------------------------------------------------------
            | Individual / Community (role = creator)
            |--------------------------------------------------------------------------
            */
            'type_of_group' => [
                'required_if:role,creator',
                'string',
                'max:255',
            ],

            'group_duration' => [
                'required_if:role,creator',
                'string',
                Rule::in(['<6_months', '6_12_months', '1_3_years', '3_plus_years']),
            ],

            'experience' => [
                'required_if:role,creator',
                'string',
            ],

            'collection_methods' => [
                'required_if:role,creator',
                'array',
            ],

            'number_of_members' => [
                'required_if:role,creator',
                'integer',
                'min:2',
            ],

            'expected_monthly_contribution' => [
                'required_if:role,creator',
                'numeric',
                'min:0',
            ],

            'contribution_frequency' => [
                'required_if:role,creator',
                Rule::in(['daily', 'weekly', 'monthly']),
            ],

            'missed_contribution_handling' => [
                'required_if:role,creator',
                'string',
            ],

            'can_enforce_rules_off_app' => [
                'required_if:role,creator',
                'boolean',
            ],

            /*
            |--------------------------------------------------------------------------
            | Organisation / Cooperative (role = cooperative)
            |--------------------------------------------------------------------------
            */
            'role_in_org' => [
                'required_if:role,cooperative',
                'string',
                'max:255',
            ],

            'organisation_name' => [
                'required_if:role,cooperative',
                'string',
                'max:255',
            ],

            'organisation_type' => [
                'required_if:role,cooperative',
                'string',
            ],

            'organisation_reg_number' => [
                'nullable',
                'string',
                'max:255',
            ],

            'organisation_established_year' => [
                'nullable',
                'digits:4',
            ],

            'approx_member_number' => [
                'required_if:role,cooperative',
                Rule::in(['<50', '50_200', '200_1000', '1000_plus']),
            ],

            'has_existing_scheme' => [
                'required_if:role,cooperative',
                'boolean',
            ],

            'current_contribution_management' => [
                'required_if:role,cooperative',
                'string',
            ],

            'intended_api_usage' => [
                'required_if:role,cooperative',
                'array',
            ],

            'estimated_circle_count' => [
                'required_if:role,cooperative',
                'integer',
                'min:1',
            ],

            'organisation_handles_payments' => [
                'required_if:role,cooperative',
                'boolean',
            ],

            'has_internal_default_rules' => [
                'required_if:role,cooperative',
                'boolean',
            ],

            'governance_structure' => [
                'required_if:role,cooperative',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | Compliance (All Roles)
            |--------------------------------------------------------------------------
            */
            'not_a_bank_acknowledged' => ['required', 'accepted'],
            'no_fund_safeguard_acknowledged' => ['required', 'accepted'],
            'fixed_payout_acknowledged' => ['required', 'accepted'],
            'agree_to_terms' => ['required', 'accepted'],

            'additional_context' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [

            'role.required' => 'Please select the account type.',
            'role.exists' => 'Selected role is invalid.',

            'type_of_group.required_if' =>
                'Please specify the type of group you manage.',

            'organisation_name.required_if' =>
                'Organisation name is required for cooperatives.',

            'role_in_org.required_if' =>
                'Please specify your role in the organisation.',

            'approx_member_number.required_if' =>
                'Please estimate the number of members in your organisation.',

            'not_a_bank_acknowledged.accepted' =>
                'You must acknowledge that the app is not a bank.',

            'agree_to_terms.accepted' =>
                'You must agree to the terms and usage limitations.',
        ];
    }
}

