<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFuzzyRuleRequest extends FormRequest
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
            'intent' => 'sometimes|string|max:100',
            'pattern' => 'sometimes|string',
            'confidence_threshold' => 'sometimes|numeric|min:0|max:1',
            'action' => 'sometimes|string|max:100',
            'response_template' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'intent.max' => 'Intent must not exceed 100 characters',
            'confidence_threshold.numeric' => 'Confidence threshold must be a number',
            'confidence_threshold.min' => 'Confidence threshold must be at least 0',
            'confidence_threshold.max' => 'Confidence threshold must not exceed 1',
            'action.max' => 'Action must not exceed 100 characters',
        ];
    }
}
