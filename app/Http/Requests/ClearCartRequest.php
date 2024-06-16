<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClearCartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0',
            'recipient_email' => 'string|email',
            'recipient_name' => 'string|required_if_accepted:recipient_email,true',
            'products' => 'required|array',
            'products.*.product_slug' => 'required|string',
            'products.*.quantity' => 'required|integer|min:1',
        ];
    }
}
