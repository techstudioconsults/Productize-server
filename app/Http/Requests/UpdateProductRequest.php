<?php

namespace App\Http\Requests;

use App\Exceptions\UnprocessableException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new UnprocessableException($validator->errors()->first());
    }
}
