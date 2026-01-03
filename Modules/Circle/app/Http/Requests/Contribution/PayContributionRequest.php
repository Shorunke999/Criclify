<?php

namespace Modules\Circle\Http\Requests\Contribution;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Http\Requests\BaseRequest;

class PayContributionRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1'],
            'contribution_id' => ['nullable', 'integer', 'exists:circle_contributions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Payment amount is required',
            'amount.min' => 'Amount must be greater than zero',
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
