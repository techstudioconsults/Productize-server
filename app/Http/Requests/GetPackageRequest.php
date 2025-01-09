<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetPackageRequest extends FormRequest
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
            'email' => 'required|email',
            'fullname' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'An email address is required',
            'fullname.required' => 'Full name is required',
        ];
    }
}
