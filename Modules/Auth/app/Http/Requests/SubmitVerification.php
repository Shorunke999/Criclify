<?php

namespace Modules\Auth\Http\Requests;

use Modules\Core\Http\Requests\BaseRequest;

class SubmitVerification extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [

            'country' => 'required|string|size:2',
            'id_type' => 'required|string',
            'selfie_image' => 'required|string', // base64 or file path
            'id_card_image' => 'required|string',
            'id_card_back_image' => 'nullable|string',

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
            'country.required'     => 'Input a valid country NG,KE,GH',
            'id_type.required'       => 'Input the id type. PASSPORT, DRIVERS_LICENSE, NATIONAL_ID, VOTER_ID',
            'selfie_image.required'    => 'selfie image should be in base64 string and its required.',
            'id_card_image.required'    => 'Id card image should be in base64 string and its required.',

        ];
    }
}
