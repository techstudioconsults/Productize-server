<?php

namespace App\Http\Requests;

use App\Enums\ProductStatusEnum;
use App\Exceptions\UnprocessableException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateFunnelRequest extends FormRequest
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
            'title' => 'string',
            'thumbnail' => 'file|image', // 2000 kilobytes
            'template' => 'string',
            'status' => new Enum(ProductStatusEnum::class),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new UnprocessableException($validator->errors()->first());
    }
}
