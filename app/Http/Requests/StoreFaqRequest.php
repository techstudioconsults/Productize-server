<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Exceptions\UnprocessableException;
use Illuminate\Contracts\Validation\Validator;

class StoreFaqRequest extends FormRequest
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
            'title' => 'required|string',
            'question' => 'required|string',
            'answer' => 'required|string'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new UnprocessableException($validator->errors()->first());
    }
}
