<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class TranslationRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
   public function rules(): array
    {
        $rules = [
            'value' => 'required|string',
            'group' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'is_active' => 'nullable|boolean'
        ];

        if ($this->isMethod('POST')) {
            $rules['key'] = 'required|string|max:255';
            $rules['locale'] = [
                'required',
                'string',
                'max:10',
                Rule::in(config('app.available_locales', ['en', 'fr', 'es']))
            ];
        }

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['key'] = 'sometimes|string|max:255';
            $rules['locale'] = [
                'sometimes',
                'string',
                'max:10',
                Rule::in(config('app.available_locales', ['en', 'fr', 'es']))
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'key.required' => 'The translation key is required.',
            'locale.required' => 'The locale is required.',
            'locale.in' => 'The selected locale is not supported.',
            'value.required' => 'The translation value is required.',
            'tags.array' => 'Tags must be provided as an array.',
            'tags.*.string' => 'Each tag must be a string.',
        ];
    }
}
