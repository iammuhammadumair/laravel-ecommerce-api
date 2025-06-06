<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Product Variants",
    description: "API endpoints for managing product variants"
)]
class ProductVariantController extends Controller
{
    #[OA\Get(
        path: "/api/variants",
        summary: "Get all product variants",
        description: "Retrieve a paginated list of product variants with filtering and search capabilities",
        tags: ["Product Variants"],
        parameters: [
            new OA\Parameter(
                name: "product_id",
                description: "Filter by product ID",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "option1",
                description: "Filter by option 1 value",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", example: "Red")
            ),
            new OA\Parameter(
                name: "option2",
                description: "Filter by option 2 value",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", example: "Large")
            ),
            new OA\Parameter(
                name: "option3",
                description: "Filter by option 3 value",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", example: "Cotton")
            ),
            new OA\Parameter(
                name: "in_stock",
                description: "Filter by stock availability",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "boolean", example: true)
            ),
            new OA\Parameter(
                name: "search",
                description: "Search in variant title, SKU, or barcode",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", example: "shirt")
            ),
            new OA\Parameter(
                name: "sort_by",
                description: "Field to sort by",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["position", "title", "price", "created_at"], example: "position")
            ),
            new OA\Parameter(
                name: "sort_order",
                description: "Sort order",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["asc", "desc"], example: "asc")
            ),
            new OA\Parameter(
                name: "per_page",
                description: "Number of items per page (max 100)",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", minimum: 1, maximum: 100, example: 15)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Product variants retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/ProductVariant")
                        ),
                        new OA\Property(
                            property: "pagination",
                            properties: [
                                new OA\Property(property: "current_page", type: "integer", example: 1),
                                new OA\Property(property: "per_page", type: "integer", example: 15),
                                new OA\Property(property: "total", type: "integer", example: 50),
                                new OA\Property(property: "last_page", type: "integer", example: 4)
                            ],
                            type: "object"
                        )
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = ProductVariant::with('product');

        // Filter by product if provided
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by options
        if ($request->has('option1')) {
            $query->where('option1', $request->option1);
        }
        if ($request->has('option2')) {
            $query->where('option2', $request->option2);
        }
        if ($request->has('option3')) {
            $query->where('option3', $request->option3);
        }

        // Filter by stock status
        if ($request->has('in_stock') && $request->boolean('in_stock')) {
            $query->inStock();
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('sku', 'LIKE', "%{$search}%")
                    ->orWhere('barcode', 'LIKE', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'position');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $variants = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $variants->items(),
            'pagination' => [
                'current_page' => $variants->currentPage(),
                'per_page' => $variants->perPage(),
                'total' => $variants->total(),
                'last_page' => $variants->lastPage(),
            ]
        ]);
    }

    #[OA\Post(
        path: "/api/variants",
        summary: "Create a new product variant",
        description: "Create a new product variant for an existing product",
        tags: ["Product Variants"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/ProductVariantRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Product variant created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Product variant created successfully"),
                        new OA\Property(property: "data", ref: "#/components/schemas/ProductVariant")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Product not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Product not found")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "The given data was invalid."),
                        new OA\Property(
                            property: "errors",
                            type: "object",
                            additionalProperties: new OA\AdditionalProperties(
                                type: "array",
                                items: new OA\Items(type: "string")
                            )
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Server error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Failed to create product variant"),
                        new OA\Property(property: "error", type: "string")
                    ]
                )
            )
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'title' => 'required|string|max:255',
            'sku' => 'required|string|unique:product_variants,sku|max:255',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'inventory_quantity' => 'integer|min:0',
            'track_inventory' => 'boolean',
            'inventory_policy' => 'string|in:deny,continue',
            'fulfillment_service' => 'string|max:255',
            'option1' => 'nullable|string|max:255',
            'option2' => 'nullable|string|max:255',
            'option3' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0',
            'weight_unit' => 'string|in:kg,g,lb,oz',
            'barcode' => 'nullable|string|max:255',
            'image' => 'nullable|array',
            'requires_shipping' => 'boolean',
            'taxable' => 'boolean',
            'position' => 'integer|min:1',
        ]);

        try {
            // Verify product exists
            $product = Product::findOrFail($validated['product_id']);

            $variant = ProductVariant::create($validated);
            $variant->load('product');

            return response()->json([
                'success' => true,
                'message' => 'Product variant created successfully',
                'data' => $variant
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product variant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/variants/{id}",
        summary: "Get a specific product variant",
        description: "Retrieve details of a specific product variant by ID",
        tags: ["Product Variants"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Product variant ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Product variant retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/ProductVariant")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Product variant not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Product variant not found")
                    ]
                )
            )
        ]
    )]
    public function show(string $id): JsonResponse
    {
        try {
            $variant = ProductVariant::with('product')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $variant
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product variant not found'
            ], 404);
        }
    }

    #[OA\Put(
        path: "/api/variants/{id}",
        summary: "Update a product variant",
        description: "Update an existing product variant",
        tags: ["Product Variants"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Product variant ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/ProductVariantUpdateRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Product variant updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Product variant updated successfully"),
                        new OA\Property(property: "data", ref: "#/components/schemas/ProductVariant")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Product variant not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Product variant not found")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "The given data was invalid."),
                        new OA\Property(
                            property: "errors",
                            type: "object",
                            additionalProperties: new OA\AdditionalProperties(
                                type: "array",
                                items: new OA\Items(type: "string")
                            )
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Server error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Failed to update product variant"),
                        new OA\Property(property: "error", type: "string")
                    ]
                )
            )
        ]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $variant = ProductVariant::findOrFail($id);

            $validated = $request->validate([
                'product_id' => 'exists:products,id',
                'title' => 'string|max:255',
                'sku' => ['string', 'max:255', Rule::unique('product_variants', 'sku')->ignore($variant->id)],
                'price' => 'numeric|min:0',
                'compare_price' => 'nullable|numeric|min:0',
                'inventory_quantity' => 'integer|min:0',
                'track_inventory' => 'boolean',
                'inventory_policy' => 'string|in:deny,continue',
                'fulfillment_service' => 'string|max:255',
                'option1' => 'nullable|string|max:255',
                'option2' => 'nullable|string|max:255',
                'option3' => 'nullable|string|max:255',
                'weight' => 'nullable|numeric|min:0',
                'weight_unit' => 'string|in:kg,g,lb,oz',
                'barcode' => 'nullable|string|max:255',
                'image' => 'nullable|array',
                'requires_shipping' => 'boolean',
                'taxable' => 'boolean',
                'position' => 'integer|min:1',
            ]);

            $variant->update($validated);
            $variant->load('product');

            return response()->json([
                'success' => true,
                'message' => 'Product variant updated successfully',
                'data' => $variant
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product variant not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product variant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Delete(
        path: "/api/variants/{id}",
        summary: "Delete a product variant",
        description: "Delete a specific product variant",
        tags: ["Product Variants"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Product variant ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Product variant deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Product variant deleted successfully")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Product variant not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Product variant not found")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Server error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Failed to delete product variant"),
                        new OA\Property(property: "error", type: "string")
                    ]
                )
            )
        ]
    )]
    public function destroy(string $id): JsonResponse
    {
        try {
            $variant = ProductVariant::findOrFail($id);
            $variant->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product variant deleted successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product variant not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product variant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/products/{productId}/variants",
        summary: "Get variants for a specific product",
        description: "Retrieve all variants for a specific product ordered by position",
        tags: ["Product Variants"],
        parameters: [
            new OA\Parameter(
                name: "productId",
                description: "Product ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Product variants retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/ProductVariant")
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Product not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Product not found")
                    ]
                )
            )
        ]
    )]
    public function getByProduct(string $productId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);
            $variants = $product->variants()->orderBy('position')->get();

            return response()->json([
                'success' => true,
                'data' => $variants
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }
    }

    #[OA\Patch(
        path: "/api/variants/positions",
        summary: "Bulk update variant positions",
        description: "Update positions for multiple product variants in bulk",
        tags: ["Product Variants"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "variants",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "id", type: "integer", description: "Variant ID", example: 1),
                                new OA\Property(property: "position", type: "integer", description: "New position", example: 1)
                            ],
                            required: ["id", "position"]
                        ),
                        description: "Array of variants with their new positions"
                    )
                ],
                required: ["variants"]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Variant positions updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Variant positions updated successfully")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "The given data was invalid."),
                        new OA\Property(
                            property: "errors",
                            type: "object",
                            additionalProperties: new OA\AdditionalProperties(
                                type: "array",
                                items: new OA\Items(type: "string")
                            )
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Server error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Failed to update positions"),
                        new OA\Property(property: "error", type: "string")
                    ]
                )
            )
        ]
    )]
    public function updatePositions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'variants' => 'required|array',
            'variants.*.id' => 'required|exists:product_variants,id',
            'variants.*.position' => 'required|integer|min:1'
        ]);

        try {
            foreach ($validated['variants'] as $variantData) {
                ProductVariant::where('id', $variantData['id'])
                    ->update(['position' => $variantData['position']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Variant positions updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update positions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
