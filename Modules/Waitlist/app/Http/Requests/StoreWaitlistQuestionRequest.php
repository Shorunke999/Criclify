<?php

namespace Modules\Waitlist\Http\Requests;

use Modules\Core\Http\Requests\BaseRequest;

class StoreWaitlistQuestionRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'key' => 'required|string|alpha_dash|unique:waitlist_questions,key',
            'label' => 'required|string|max:255',
            'type' => 'required|in:text,select,number',
            'options' => 'nullable|array',
            'required' => 'boolean',
            'active' => 'boolean',
        ];
    }
}
