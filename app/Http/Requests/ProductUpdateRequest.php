<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ProductUpdateRequest",
    title: "ProductUpdateRequest",
    description: "Product update request (all fields optional)",
    type: "object",
    properties: [
        new OA\Property(property: "name", type: "string", example: "T-Shirt", description: "Product name"),
        new OA\Property(property: "description", type: "string", example: "A comfortable cotton t-shirt", description: "Product description"),
        new OA\Property(property: "sku", type: "string", example: "TSH-001", description: "Stock Keeping Unit"),
        new OA\Property(property: "price", type: "number", format: "float", example: 29.99, description: "Product price"),
        new OA\Property(property: "compare_price", type: "number", format: "float", example: 39.99, description: "Compare at price"),
        new OA\Property(property: "inventory_quantity", type: "integer", example: 100, description: "Inventory quantity"),
        new OA\Property(property: "track_inventory", type: "boolean", example: true, description: "Whether to track inventory"),
        new OA\Property(property: "status", type: "string", enum: ["active", "inactive", "archived"], example: "active", description: "Product status"),
        new OA\Property(property: "vendor", type: "string", example: "Brand Name", description: "Product vendor"),
        new OA\Property(property: "product_type", type: "string", example: "Clothing", description: "Product type/category"),
        new OA\Property(property: "tags", type: "array", items: new OA\Items(type: "string"), example: ["cotton", "casual"], description: "Product tags"),
        new OA\Property(property: "images", type: "array", items: new OA\Items(type: "string"), example: ["image1.jpg", "image2.jpg"], description: "Product images"),
        new OA\Property(property: "weight", type: "number", format: "float", example: 0.3, description: "Product weight"),
        new OA\Property(property: "weight_unit", type: "string", enum: ["kg", "g", "lb", "oz"], example: "kg", description: "Weight unit"),
        new OA\Property(property: "requires_shipping", type: "boolean", example: true, description: "Whether product requires shipping"),
        new OA\Property(
            property: "seo",
            type: "object",
            description: "SEO metadata",
            properties: [
                new OA\Property(property: "title", type: "string", example: "Comfortable Cotton T-Shirt"),
                new OA\Property(property: "description", type: "string", example: "Meta description for SEO"),
                new OA\Property(property: "keywords", type: "string", example: "t-shirt, cotton, clothing")
            ]
        )
    ]
)]
class ProductUpdateRequest extends FormRequest
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
        $productId = $this->route('id'); // Get the product ID from route parameter

        return [
            'name' => [
                'string',
                'max:255',
                'min:2'
            ],
            'description' => [
                'nullable',
                'string',
                'max:5000'
            ],
            'sku' => [
                'string',
                'max:100',
                'min:2',
                'regex:/^[A-Za-z0-9\-_]+$/',
                Rule::unique('products', 'sku')->ignore($productId)
            ],
            'price' => [
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
            'status' => [
                'string',
                Rule::in(['active', 'inactive', 'archived'])
            ],
            'vendor' => [
                'nullable',
                'string',
                'max:255'
            ],
            'product_type' => [
                'nullable',
                'string',
                'max:255'
            ],
            'tags' => [
                'nullable',
                'array',
                'max:20'
            ],
            'tags.*' => [
                'string',
                'max:50',
                'distinct'
            ],
            'images' => [
                'nullable',
                'array',
                'max:10'
            ],
            'images.*' => [
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
            'requires_shipping' => [
                'boolean'
            ],
            'seo' => [
                'nullable',
                'array'
            ],
            'seo.title' => [
                'nullable',
                'string',
                'max:255'
            ],
            'seo.description' => [
                'nullable',
                'string',
                'max:500'
            ],
            'seo.keywords' => [
                'nullable',
                'string',
                'max:255'
            ]
        ];
    }

    /**
     * Get the custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.min' => 'Product name must be at least 2 characters.',
            'sku.unique' => 'This SKU already exists. Please choose a different one.',
            'sku.regex' => 'SKU can only contain letters, numbers, hyphens, and underscores.',
            'price.min' => 'Price must be greater than or equal to 0.',
            'inventory_quantity.min' => 'Inventory quantity cannot be negative.',
            'status.in' => 'Status must be one of: active, inactive, archived.',
            'tags.max' => 'You can add a maximum of 20 tags.',
            'tags.*.distinct' => 'Duplicate tags are not allowed.',
            'images.max' => 'You can add a maximum of 10 images.',
            'weight_unit.in' => 'Weight unit must be one of: kg, g, lb, oz.',
            'seo.title.max' => 'SEO title cannot exceed 255 characters.',
            'seo.description.max' => 'SEO description cannot exceed 500 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'sku' => 'SKU',
            'seo.title' => 'SEO title',
            'seo.description' => 'SEO description',
            'seo.keywords' => 'SEO keywords',
        ];
    }
}
