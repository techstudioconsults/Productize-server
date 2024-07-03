<?php

namespace App\Http\Requests;

use App\Enums\SkillSellingCategory;
use App\Exceptions\UnprocessableException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreSkillSellingRequest extends FormRequest
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
            'category' => ['required', new Enum(SkillSellingCategory::class)],
            'level' => 'required|string',
            'availability' => 'required|string',
            'link' => 'required|string',
            'resources' => 'required',
            'resources.*' => 'required|file',
            'product_id' => 'required|string|exists:products,id|unique:digital_products,product_id|unique:skill_sellings,product_id',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new UnprocessableException($validator->errors()->first());
    }
}
