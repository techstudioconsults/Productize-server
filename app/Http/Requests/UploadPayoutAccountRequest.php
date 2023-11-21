<?php

namespace App\Http\Requests;

use App\Enums\PaystackBankCodesEnum;
use App\Exceptions\UnprocessableException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rules\Enum;

class UploadPayoutAccountRequest extends FormRequest
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
            'business_name' => 'string|required',
            'bank_code' => ['required', new Enum(PaystackBankCodesEnum::class)],
            'bank_name' => 'string|required',
            'account_number' => 'string|max:10'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new UnprocessableException($validator->errors()->first());
    }
}
