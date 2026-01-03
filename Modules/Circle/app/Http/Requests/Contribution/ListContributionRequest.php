<?php

namespace Modules\Circle\Http\Requests\Contribution;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Circle\Enums\StatusEnum;
use Modules\Core\Http\Requests\BaseRequest;

class ListContributionRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
          return [
            'circle_id' => 'sometimes|integer|exists:circles,id',
            'member_id' => 'sometimes|integer|exists:circle_members,id',
            'user_id'   => 'sometimes|integer|exists:users,id',
            'status'    => ['sometimes', Rule::in(StatusEnum::values())],
            'due_from'  => 'sometimes|date',
            'due_to'    => 'sometimes|date|after_or_equal:due_from',
            'per_page'  => 'sometimes|integer|min:1|max:100',
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
