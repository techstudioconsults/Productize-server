<?php

namespace App\Http\Requests;

use App\Exceptions\ForbiddenException;
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
            'country' => 'string|nullable',
            'document_type' => 'string|nullable|in:Driver\'s license,National Id card,National Passport',
            'document_image' => 'image|nullable|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new UnprocessableException($validator->errors()->first());
    }
}
