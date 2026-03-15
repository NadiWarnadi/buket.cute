<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendWhatsAppTextRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Bisa disesuaikan dengan authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'message' => 'required|string|min:1|max:1024',
            'order_id' => 'nullable|exists:orders,id',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer ID is required',
            'customer_id.exists' => 'Customer not found',
            'message.required' => 'Message cannot be empty',
            'message.min' => 'Message must at least 1 character',
            'message.max' => 'Message cannot exceed 1024 characters',
            'order_id.exists' => 'Order not found',
        ];
    }
}
