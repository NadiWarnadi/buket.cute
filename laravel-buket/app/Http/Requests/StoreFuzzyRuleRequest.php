<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFuzzyRuleRequest extends FormRequest
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
            'intent' => 'required|string|max:100',
            'pattern' => 'required|string',
            'confidence_threshold' => 'required|numeric|min:0|max:1',
            'action' => 'required|string|max:100',
            'response_template' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'intent.required' => 'Intent field is required',
            'intent.max' => 'Intent must not exceed 100 characters',
            'pattern.required' => 'Pattern field is required',
            'confidence_threshold.required' => 'Confidence threshold is required',
            'confidence_threshold.numeric' => 'Confidence threshold must be a number',
            'confidence_threshold.min' => 'Confidence threshold must be at least 0',
            'confidence_threshold.max' => 'Confidence threshold must not exceed 1',
            'action.required' => 'Action field is required',
            'action.max' => 'Action must not exceed 100 characters',
        ];
    }
}
