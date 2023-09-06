<?php

namespace App\Http\Requests;

use App\Exceptions\UnprocessableException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'full_name' => 'required|string',
            'email' => 'required|email|string|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()]
        ];
    }

    /**
     *  Use Validator contract to use this method
     */
    protected function failedValidation(Validator $validator)
    {
        throw new UnprocessableException($validator->errors()->first());
    }
}
