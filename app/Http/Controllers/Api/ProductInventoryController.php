<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductInventoryUpdateRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: "Inventory Management",
    description: "API endpoints for managing product and product variant inventory"
)]
class ProductInventoryController extends Controller
{
    #[OA\Patch(
        path: "/api/products/{id}/inventory",
        summary: "Update product inventory",
        description: "Update inventory quantity for a specific product using set, increment, or decrement operations",
        tags: ["Inventory Management"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Product ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "quantity",
                        type: "integer",
                        description: "Quantity to set, increment, or decrement",
                        example: 10
                    ),
                    new OA\Property(
                        property: "operation",
                        type: "string",
                        enum: ["set", "increment", "decrement"],
                        description: "Inventory operation type",
                        example: "set"
                    )
                ],
                required: ["quantity", "operation"]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Product inventory updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Product inventory updated successfully"),
                        new OA\Property(property: "data", ref: "#/components/schemas/Product")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Insufficient inventory",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Insufficient inventory")
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
                        new OA\Property(property: "message", type: "string", example: "Failed to update inventory"),
                        new OA\Property(property: "error", type: "string")
                    ]
                )
            )
        ]
    )]
    public function updateProductInventory(ProductInventoryUpdateRequest $request, string $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $validated = $request->validated();

            switch ($validated['operation']) {
                case 'set':
                    $product->update(['inventory_quantity' => $validated['quantity']]);
                    break;
                case 'increment':
                    $product->increment('inventory_quantity', $validated['quantity']);
                    break;
                case 'decrement':
                    if ($product->inventory_quantity >= $validated['quantity']) {
                        $product->decrement('inventory_quantity', $validated['quantity']);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Insufficient inventory'
                        ], 400);
                    }
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'Product inventory updated successfully',
                'data' => $product->fresh()
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update inventory',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Patch(
        path: "/api/variants/{id}/inventory",
        summary: "Update variant inventory",
        description: "Update inventory quantity for a specific product variant using set, increment, or decrement operations",
        tags: ["Inventory Management"],
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
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "quantity",
                        type: "integer",
                        description: "Quantity to set, increment, or decrement",
                        example: 10
                    ),
                    new OA\Property(
                        property: "operation",
                        type: "string",
                        enum: ["set", "increment", "decrement"],
                        description: "Inventory operation type",
                        example: "set"
                    )
                ],
                required: ["quantity", "operation"]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Variant inventory updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Variant inventory updated successfully"),
                        new OA\Property(property: "data", ref: "#/components/schemas/ProductVariant")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Insufficient inventory",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Insufficient inventory")
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
                        new OA\Property(property: "message", type: "string", example: "Failed to update inventory"),
                        new OA\Property(property: "error", type: "string")
                    ]
                )
            )
        ]
    )]
    public function updateVariantInventory(Request $request, string $id): JsonResponse
    {
        try {
            $variant = ProductVariant::findOrFail($id);

            $validated = $request->validate([
                'quantity' => 'required|integer',
                'operation' => 'required|string|in:set,increment,decrement'
            ]);

            switch ($validated['operation']) {
                case 'set':
                    $variant->update(['inventory_quantity' => $validated['quantity']]);
                    break;
                case 'increment':
                    $variant->increment('inventory_quantity', $validated['quantity']);
                    break;
                case 'decrement':
                    if ($variant->canFulfill($validated['quantity'])) {
                        $variant->decrement('inventory_quantity', $validated['quantity']);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Insufficient inventory'
                        ], 400);
                    }
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'Variant inventory updated successfully',
                'data' => $variant->fresh()
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product variant not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update inventory',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Patch(
        path: "/api/inventory/products/bulk",
        summary: "Bulk update product inventory",
        description: "Update inventory for multiple products in a single request",
        tags: ["Inventory Management"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "products",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "id", type: "integer", description: "Product ID", example: 1),
                                new OA\Property(property: "quantity", type: "integer", description: "Quantity", example: 10),
                                new OA\Property(
                                    property: "operation",
                                    type: "string",
                                    enum: ["set", "increment", "decrement"],
                                    description: "Operation type",
                                    example: "set"
                                )
                            ],
                            required: ["id", "quantity", "operation"]
                        ),
                        description: "Array of products with inventory updates"
                    )
                ],
                required: ["products"]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Bulk inventory update completed",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Bulk inventory update completed"),
                        new OA\Property(
                            property: "results",
                            type: "object",
                            properties: [
                                new OA\Property(property: "successful", type: "integer", example: 5),
                                new OA\Property(property: "failed", type: "integer", example: 0),
                                new OA\Property(property: "errors", type: "array", items: new OA\Items(type: "string"))
                            ]
                        )
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
            )
        ]
    )]
    public function bulkUpdateProductInventory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer',
            'products.*.operation' => 'required|string|in:set,increment,decrement'
        ]);

        $successful = 0;
        $failed = 0;
        $errors = [];

        foreach ($validated['products'] as $productData) {
            try {
                $product = Product::findOrFail($productData['id']);

                switch ($productData['operation']) {
                    case 'set':
                        $product->update(['inventory_quantity' => $productData['quantity']]);
                        break;
                    case 'increment':
                        $product->increment('inventory_quantity', $productData['quantity']);
                        break;
                    case 'decrement':
                        if ($product->inventory_quantity >= $productData['quantity']) {
                            $product->decrement('inventory_quantity', $productData['quantity']);
                        } else {
                            $errors[] = "Product ID {$productData['id']}: Insufficient inventory";
                            $failed++;
                            continue 2;
                        }
                        break;
                }

                $successful++;
            } catch (\Exception $e) {
                $errors[] = "Product ID {$productData['id']}: {$e->getMessage()}";
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk inventory update completed',
            'results' => [
                'successful' => $successful,
                'failed' => $failed,
                'errors' => $errors
            ]
        ]);
    }

    #[OA\Patch(
        path: "/api/inventory/variants/bulk",
        summary: "Bulk update variant inventory",
        description: "Update inventory for multiple product variants in a single request",
        tags: ["Inventory Management"],
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
                                new OA\Property(property: "quantity", type: "integer", description: "Quantity", example: 10),
                                new OA\Property(
                                    property: "operation",
                                    type: "string",
                                    enum: ["set", "increment", "decrement"],
                                    description: "Operation type",
                                    example: "set"
                                )
                            ],
                            required: ["id", "quantity", "operation"]
                        ),
                        description: "Array of variants with inventory updates"
                    )
                ],
                required: ["variants"]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Bulk inventory update completed",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Bulk inventory update completed"),
                        new OA\Property(
                            property: "results",
                            type: "object",
                            properties: [
                                new OA\Property(property: "successful", type: "integer", example: 5),
                                new OA\Property(property: "failed", type: "integer", example: 0),
                                new OA\Property(property: "errors", type: "array", items: new OA\Items(type: "string"))
                            ]
                        )
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
            )
        ]
    )]
    public function bulkUpdateVariantInventory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'variants' => 'required|array|min:1',
            'variants.*.id' => 'required|exists:product_variants,id',
            'variants.*.quantity' => 'required|integer',
            'variants.*.operation' => 'required|string|in:set,increment,decrement'
        ]);

        $successful = 0;
        $failed = 0;
        $errors = [];

        foreach ($validated['variants'] as $variantData) {
            try {
                $variant = ProductVariant::findOrFail($variantData['id']);

                switch ($variantData['operation']) {
                    case 'set':
                        $variant->update(['inventory_quantity' => $variantData['quantity']]);
                        break;
                    case 'increment':
                        $variant->increment('inventory_quantity', $variantData['quantity']);
                        break;
                    case 'decrement':
                        if ($variant->canFulfill($variantData['quantity'])) {
                            $variant->decrement('inventory_quantity', $variantData['quantity']);
                        } else {
                            $errors[] = "Variant ID {$variantData['id']}: Insufficient inventory";
                            $failed++;
                            continue 2;
                        }
                        break;
                }

                $successful++;
            } catch (\Exception $e) {
                $errors[] = "Variant ID {$variantData['id']}: {$e->getMessage()}";
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk inventory update completed',
            'results' => [
                'successful' => $successful,
                'failed' => $failed,
                'errors' => $errors
            ]
        ]);
    }
} 