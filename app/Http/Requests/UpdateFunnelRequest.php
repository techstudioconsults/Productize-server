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
            'status' => new Enum(ProductStatusEnum::class),
            'template' => ['string', 'json', function ($attribute, $value, $fail) {
                $decoded = json_decode($value, true);

                if (!isset($decoded['pages']) || !is_array($decoded['pages'])) {
                    $fail('The template must contain a "pages" array.');
                    return;
                }

                foreach ($decoded['pages'] as $index => $page) {
                    if (!isset($page['id'])) {
                        $fail("Page {$index} is missing the required 'id' field.");
                    }
                    if (!isset($page['name'])) {
                        $fail("Page {$index} is missing the required 'name' field.");
                    }
                    if (!isset($page['content'])) {
                        $fail("Page {$index} is missing the required 'content' field.");
                    }
                    if (!isset($page['style'])) {
                        $fail("Page {$index} is missing the required 'style' field.");
                    }
                }
            }],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new UnprocessableException($validator->errors()->first());
    }

     /**
     * Get the parsed template data.
     *
     * @return array
     */
    public function getParsedTemplate(): array
    {
        return json_decode($this->template, true)['pages'];
    }
}
