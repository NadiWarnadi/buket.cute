<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendWhatsAppMediaRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'file' => 'required|file|max:'.(env('MAX_FILE_SIZE', 15) * 1024),
            'caption' => 'nullable|string|max:1024',
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
            'file.required' => 'File is required',
            'file.file' => 'Uploaded file is invalid',
            'file.max' => 'File size exceeds maximum allowed size ('.env('MAX_FILE_SIZE', 15).'MB)',
            'caption.max' => 'Caption cannot exceed 1024 characters',
            'order_id.exists' => 'Order not found',
        ];
    }
}
