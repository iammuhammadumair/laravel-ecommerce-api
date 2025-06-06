<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ProductVariantUpdateRequest",
    title: "ProductVariantUpdateRequest",
    description: "Product variant update request (all fields optional)",
    type: "object",
    properties: [
        new OA\Property(property: "title", type: "string", example: "Small / Red", description: "Variant title"),
        new OA\Property(property: "sku", type: "string", example: "TSH-001-S-RED", description: "Stock Keeping Unit"),
        new OA\Property(property: "price", type: "number", format: "float", example: 29.99, description: "Variant price"),
        new OA\Property(property: "compare_price", type: "number", format: "float", example: 39.99, description: "Compare at price"),
        new OA\Property(property: "inventory_quantity", type: "integer", example: 25, description: "Inventory quantity"),
        new OA\Property(property: "track_inventory", type: "boolean", example: true, description: "Whether to track inventory"),
        new OA\Property(property: "inventory_policy", type: "string", enum: ["deny", "continue"], example: "deny", description: "Inventory policy"),
        new OA\Property(property: "fulfillment_service", type: "string", example: "manual", description: "Fulfillment service"),
        new OA\Property(property: "option1", type: "string", example: "Small", description: "Option 1 value"),
        new OA\Property(property: "option2", type: "string", example: "Red", description: "Option 2 value"),
        new OA\Property(property: "option3", type: "string", nullable: true, example: null, description: "Option 3 value"),
        new OA\Property(property: "weight", type: "number", format: "float", example: 0.3, description: "Variant weight"),
        new OA\Property(property: "weight_unit", type: "string", enum: ["kg", "g", "lb", "oz"], example: "kg", description: "Weight unit"),
        new OA\Property(property: "barcode", type: "string", example: "1234567890123", description: "Barcode"),
        new OA\Property(property: "image", type: "array", items: new OA\Items(type: "string"), example: ["variant1.jpg"], description: "Variant images"),
        new OA\Property(property: "requires_shipping", type: "boolean", example: true, description: "Whether variant requires shipping"),
        new OA\Property(property: "taxable", type: "boolean", example: true, description: "Whether variant is taxable"),
        new OA\Property(property: "position", type: "integer", example: 1, description: "Variant position")
    ]
)]
class ProductVariantUpdateRequest extends FormRequest
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
            'title' => [
                'string',
                'max:255'
            ],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9\-_]+$/'
            ],
            'price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99'
            ],
            'compare_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99'
            ],
            'inventory_quantity' => [
                'integer',
                'min:0',
                'max:999999'
            ],
            'track_inventory' => [
                'boolean'
            ],
            'inventory_policy' => [
                'string',
                Rule::in(['deny', 'continue'])
            ],
            'fulfillment_service' => [
                'nullable',
                'string',
                'max:255'
            ],
            'option1' => [
                'nullable',
                'string',
                'max:255'
            ],
            'option2' => [
                'nullable',
                'string',
                'max:255'
            ],
            'option3' => [
                'nullable',
                'string',
                'max:255'
            ],
            'weight' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999'
            ],
            'weight_unit' => [
                'string',
                Rule::in(['kg', 'g', 'lb', 'oz'])
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:255'
            ],
            'image' => [
                'nullable',
                'array',
                'max:5'
            ],
            'image.*' => [
                'string',
                'max:255'
            ],
            'requires_shipping' => [
                'boolean'
            ],
            'taxable' => [
                'boolean'
            ],
            'position' => [
                'integer',
                'min:1'
            ]
        ];
    }

    /**
     * Get the custom validation messages.
     */
    public function messages(): array
    {
        return [
            'sku.regex' => 'SKU can only contain letters, numbers, hyphens, and underscores.',
            'inventory_policy.in' => 'Inventory policy must be either "deny" or "continue".',
            'weight_unit.in' => 'Weight unit must be one of: kg, g, lb, oz.',
            'image.max' => 'You can add a maximum of 5 images per variant.',
            'position.min' => 'Position must be at least 1.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'sku' => 'SKU',
            'option1' => 'option 1',
            'option2' => 'option 2',
            'option3' => 'option 3',
        ];
    }
}
