<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;
use Illuminate\Contracts\Validation\Validator;

#[OA\Schema(
    schema: "ProductRequest",
    title: "ProductRequest",
    description: "Product creation request",
    type: "object",
    required: ["name", "sku", "price"],
    properties: [
        new OA\Property(property: "name", type: "string", example: "T-Shirt", description: "Product name"),
        new OA\Property(property: "description", type: "string", example: "A comfortable cotton t-shirt", description: "Product description"),
        new OA\Property(property: "sku", type: "string", example: "TSH-001", description: "Stock Keeping Unit"),
        new OA\Property(property: "price", type: "number", format: "float", example: 29.99, description: "Product price"),
        new OA\Property(property: "compare_price", type: "number", format: "float", example: 39.99, description: "Compare at price"),
        new OA\Property(property: "inventory_quantity", type: "integer", example: 100, description: "Initial inventory quantity"),
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
/**
 * ProductStoreRequest is a request class for creating a new product.
 */
class ProductStoreRequest extends FormRequest
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
            'name' => [
                'required',
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
                'required',
                'string',
                'unique:products,sku',
                'max:100',
                'min:2',
                'regex:/^[A-Za-z0-9\-_]+$/' // Only alphanumeric, hyphens, and underscores
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99'
            ],
            'compare_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
                'gte:price' // Compare price should be greater than or equal to regular price
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
                'max:20' // Maximum 20 tags
            ],
            'tags.*' => [
                'string',
                'max:50',
                'distinct' // No duplicate tags
            ],
            'images' => [
                'nullable',
                'array',
                'max:10' // Maximum 10 images
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
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required.',
            'name.min' => 'Product name must be at least 2 characters.',
            'sku.required' => 'SKU is required.',
            'sku.unique' => 'This SKU already exists. Please choose a different one.',
            'sku.regex' => 'SKU can only contain letters, numbers, hyphens, and underscores.',
            'price.required' => 'Product price is required.',
            'price.min' => 'Price must be greater than or equal to 0.',
            'compare_price.gte' => 'Compare price must be greater than or equal to the regular price.',
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
     * @return array<string, string>
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

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'inventory_quantity' => $this->input('inventory_quantity', 0),
            'track_inventory' => $this->input('track_inventory', true),
            'status' => $this->input('status', 'active'),
            'weight_unit' => $this->input('weight_unit', 'kg'),
            'requires_shipping' => $this->input('requires_shipping', true),
        ]);

        // Convert SKU to uppercase for consistency
        if ($this->has('sku')) {
            $this->merge([
                'sku' => strtoupper($this->input('sku'))
            ]);
        }

        // Trim whitespace from string fields
        $stringFields = ['name', 'description', 'vendor', 'product_type'];
        foreach ($stringFields as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $this->merge([
                    $field => trim($this->input($field))
                ]);
            }
        }
    }

    /**
     * Configure the validator instance.
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            // Custom validation: If compare_price is set, ensure it makes sense
            if ($this->filled('compare_price') && $this->filled('price')) {
                if ($this->input('compare_price') <= $this->input('price')) {
                    $validator->errors()->add('compare_price',
                        'Compare price should typically be higher than the regular price to show savings.');
                }
            }

            // Custom validation: If tracking inventory is disabled, set quantity to 0
            if ($this->input('track_inventory') === false) {
                $this->merge(['inventory_quantity' => 0]);
            }
        });
    }
}
