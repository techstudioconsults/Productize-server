<?php

namespace App\Http\Requests;

use App\Exceptions\ForbiddenException;
use App\Exceptions\UnprocessableException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
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
        $userId = $this->user()->id;

        return [
            'full_name' => 'string|nullable',
            'password' => [Password::min(8)->mixedCase()->numbers()->symbols()],
            'username' => 'string|max:20|nullable|unique:users,username,'.$userId,
            'phone_number' => 'string|max:14|nullable|unique:users,phone_number,'.$userId,
            'bio' => 'string|max:1000|nullable',
            'twitter_account' => 'nullable|string|url',
            'facebook_account' => 'nullable|string|url',
            'youtube_account' => 'string|url|nullable',
            'alt_email' => 'string|email|nullable',
            'logo' => 'image|nullable',
            'product_creation_notification' => 'boolean',
            'purchase_notification' => 'boolean',
            'news_and_update_notification' => 'boolean',
            'payout_notification' => 'boolean',
            'country' => 'required|string',
            'document_type' => 'required|string',
            'document' => 'required|file',
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

    public function messages(): array
    {
        return [
            'username.unique' => 'username has been taken',
            'phone_number.unique' => 'phone number must be unique',
        ];
    }
}
