<?php

namespace Modules\ExpressRestApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GeneratedUrlRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'grant_id' => ['required','numeric'],
            'token' => ['required','string'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
    
    /**
     * messages
     *
     * @return array
     */
    public function messages()
    {
        return [
            'grant_id.required' => 'Grant id is required.',
            'token.required' => 'Token is required.',
        ];
    }
}
