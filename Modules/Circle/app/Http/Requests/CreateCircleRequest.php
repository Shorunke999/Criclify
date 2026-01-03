<?php

namespace Modules\Circle\Http\Requests;

use Modules\Core\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;
use Modules\Circle\Enums\CircleIntervalEnum;

class CreateCircleRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
     public function rules(): array
    {
        return [
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:100|max:1000000',
            'interval' => [
                'required',
                Rule::in(CircleIntervalEnum::values())
            ],
            'limit' => 'required|integer|min:2|max:50',
            'start_date' => 'nullable|date|after:today',
            'settings' => 'nullable|array'
        ];
    }

     public function messages(): array
    {
        return [
            'name.required'     => 'Full name is required.',
            'name.string'       => 'Name must be a valid string.',
            'amount.required' => 'amount is required.',
            'amount.numeric' => 'amount must be numeric',
            'interval.required'      => 'Interval is reqired',
            'limit.required'=> 'Limit is required',
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
