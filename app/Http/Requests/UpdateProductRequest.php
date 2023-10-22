<?php

namespace App\Http\Requests;

use App\Exceptions\UnprocessableException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rules\Enum;

class UpdateProductRequest extends FormRequest
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
            'title' => 'string',
            'price' => 'integer',
            'product_type' => [new Enum(ProductEnum::class)],
            'description' => 'string',
            'data.*' => 'file',
            'cover_photos.*' => 'image',
            'thumbnail' => 'image',
            'highlights' => 'array',
            'highlights.*' => 'string',
            'tags' => 'array',
            'tags*' => 'string',
            'stock_count' => 'boolean',
            'choose_quantity' => 'boolean',
            'show_sales_count' => 'boolean',
            'status' => 'string|in:draft'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new UnprocessableException($validator->errors()->first());
    }
}
