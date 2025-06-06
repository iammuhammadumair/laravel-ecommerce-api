<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductInventoryUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // For now, allow all requests. In production, you might want to check user permissions
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
            'quantity' => [
                'required',
                'integer',
                'min:0',
                'max:999999'
            ],
            'operation' => [
                'required',
                'string',
                Rule::in(['set', 'increment', 'decrement'])
            ]
        ];
    }

    /**
     * Get the custom validation messages.
     */
    public function messages(): array
    {
        return [
            'quantity.required' => 'Quantity is required.',
            'quantity.integer' => 'Quantity must be a whole number.',
            'quantity.min' => 'Quantity cannot be negative.',
            'quantity.max' => 'Quantity cannot exceed 999,999.',
            'operation.required' => 'Operation type is required.',
            'operation.in' => 'Operation must be one of: set, increment, decrement.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'quantity' => 'inventory quantity',
            'operation' => 'operation type',
        ];
    }
}
