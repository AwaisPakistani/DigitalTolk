<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\{ValidationRule, Validator};
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'exists:users,email'
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:255'
            ],
            'device_name' => [
                'nullable',
                'string',
                'max:100'
            ],
            'remember_me' => [
                'boolean'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.exists' => 'No account found with this email address',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters'
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim($this->email)),
            'device_name' => $this->device_name ?? request()->userAgent() ?? 'unknown'
        ]);
    }

    /**
     * Handle a failed validation attempt
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'error_code' => 'VALIDATION_ERROR'
            ], 422)
        );
    }
}
