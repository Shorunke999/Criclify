<?php

namespace Modules\Circle\Http\Requests;

use Modules\Core\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;
use Modules\Circle\Enums\CircleStatusEnum;

class ListCircleRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
        'status' => [
            'nullable',
            Rule::in(CircleStatusEnum::values()),
        ],
        'search' => 'nullable|string|max:255',
        'per_page' => 'nullable|integer|min:1|max:100',
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
