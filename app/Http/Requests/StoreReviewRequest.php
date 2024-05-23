<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReviewRequest extends FormRequest
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
            // 'product_id' => [
            //     'required',
            //     'exists:products,id',
            //     Rule::unique('reviews')->where(function ($query) {
            //         return $query->where('user_id', auth()->id());
            //     }),
            // ],
            'rating' => 'required|integer|between:1,5',
            'comment' => 'required|string',
        ];
    }
}
