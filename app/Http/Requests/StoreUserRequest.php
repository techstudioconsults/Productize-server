<?php

namespace App\Http\Requests;

use App\Enums\Roles;
use App\Exceptions\UnprocessableException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
            'full_name' => 'required|string',
            'email' => 'required|email|string|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'role' => ['required', 'string', new Enum(Roles::class)],
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
