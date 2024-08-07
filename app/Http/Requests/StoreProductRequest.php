<?php

namespace App\Http\Requests;

use App\Enums\ProductEnum;
use App\Enums\ProductTagsEnum;
use App\Exceptions\UnprocessableException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreProductRequest extends FormRequest
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
            'title' => 'string|required',
            'price' => 'integer|required',
            'discount_price' => 'integer',
            'product_type' => ['required', new Enum(ProductEnum::class)],
            'description' => 'string|required',
            'cover_photos' => 'required',
            'cover_photos.*' => 'required|image',
            'thumbnail' => 'required|image',
            'highlights' => 'required|array',
            'highlights.*' => 'string',
            'tags' => 'array',
            'tags*' => ['string', new Enum(ProductTagsEnum::class)],
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
