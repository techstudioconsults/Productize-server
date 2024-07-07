<?php

namespace App\Http\Requests;

use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnprocessableException;
use App\Models\Product;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class StoreAssetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get the authenticated user
        $user = Auth::user();

        // Get the product_id from the request input
        $product_id = $this->input('product_id');

        // Retrieve the product by product_id
        $product = Product::find($product_id);

        if (!$product) {
            throw new NotFoundException("Product Not Found");
        }

        // Check if the product exists and if the product's user_id matches the authenticated user's id
        if ($product->user_id !== $user->id) {
            throw new ForbiddenException("You are not authorized to access this product resource.");
        }

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
            'asset' => 'required|file',
            'product_id' => 'required|string|exists:products,id|unique:digital_products,product_id|unique:skill_sellings,product_id',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new UnprocessableException($validator->errors()->first());
    }
}
