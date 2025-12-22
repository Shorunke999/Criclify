<?php

namespace Modules\Waitlist\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Waitlist\Models\WaitlistQuestion;

class JoinWaitlistRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
      public function rules(): array
    {
        $rules = [
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:waitlist_entries,email',
            'referral_code' => 'nullable|string',
            'survey' => 'nullable|array',
        ];

        $questions = WaitlistQuestion::where('active', true)->get();

        foreach ($questions as $question) {
            $rules["survey.{$question->key}"] = $question->required
                ? 'required|string'
                : 'nullable|string';
        }

        return $rules;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
