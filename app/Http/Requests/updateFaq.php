<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class updateFaq extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $method = $this->method();
        
        return [
          
                'title' => 'required|string',
                'question' => 'required|string',
                'answer' => 'required|string'
        ];

    }

}
