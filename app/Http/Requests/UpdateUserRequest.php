<?php

namespace App\Http\Requests;

use App\Exceptions\ForbiddenException;
use App\Exceptions\UnprocessableException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user->email_verified_at ? true : false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => 'string',
            'email' => 'email|string|unique:users,email',
            'password' => [Password::min(8)->mixedCase()->numbers()->symbols()],
            'username' => 'string|unique:users,username|max:20',
            'phone_number' => 'string|unique:users,phone_number|max:14',
            'twitter_account' => 'string',
            'facebook_account' => 'string',
            'youtube_account' => 'string',
            'logo' => 'image'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new UnprocessableException($validator->errors()->first());
    }

    protected function failedAuthorization()
    {
        throw new ForbiddenException('Email Address not verified');
    }
}
