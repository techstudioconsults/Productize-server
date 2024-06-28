<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *
 *  @version 1.0
 *
 *  @since 21-06-2024
 */

namespace App\Http\Requests;

use App\Exceptions\UnprocessableException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateKycRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'country' => 'required|string',
            'document_type' => 'required|string|in:Driver\'s license,National Id card,National Passport',
            'document_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB',
        ];
    }

    public function messages()
    {
        return [
            'document_image.max' => 'The document image must not be greater than 2mb',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new UnprocessableException($validator->errors()->first());
    }
}
