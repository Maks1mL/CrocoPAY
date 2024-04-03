<?php

namespace Modules\ExpressRestApi\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'amount' => ['required','numeric'],
            'currency' => ['required','string'],
            'item_name' => ['nullable','string'],
            'order_no' => ['sometimes','string'],
            'successUrl' => ['required','url'],
            'cancelUrl' => ['required','url'],
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
