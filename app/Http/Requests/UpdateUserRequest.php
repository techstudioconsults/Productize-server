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
        return $user->hasVerifiedEmail();
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
            'password' => [Password::min(8)->mixedCase()->numbers()->symbols()],
            'username' => 'string|max:20',
            'phone_number' => 'string|unique:users,phone_number|max:14',
            'bio' => 'string|max:1000',
            'twitter_account' => 'string|url',
            'facebook_account' => 'string|url',
            'youtube_account' => 'string|url',
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
