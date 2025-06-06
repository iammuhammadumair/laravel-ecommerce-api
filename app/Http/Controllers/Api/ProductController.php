<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Requests\ProductInventoryUpdateRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Products", description: "API Endpoints for Product Management")]
class ProductController extends Controller
{
    #[OA\Get(
        path: "/api/products",
        summary: "Get list of products",
        description: "Retrieve a paginated list of products with optional filtering and search",
        operationId: "getProducts",
        tags: ["Products"],
        parameters: [
            new OA\Parameter(name: "status", in: "query", description: "Filter by product status", required: false, schema: new OA\Schema(type: "string", enum: ["active", "inactive", "archived"])),
            new OA\Parameter(name: "vendor", in: "query", description: "Filter by vendor", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "product_type", in: "query", description: "Filter by product type", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "in_stock", in: "query", description: "Filter by stock availability", required: false, schema: new OA\Schema(type: "boolean")),
            new OA\Parameter(name: "search", in: "query", description: "Search in name, description, or SKU", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "sort_by", in: "query", description: "Sort by field", required: false, schema: new OA\Schema(type: "string", default: "created_at")),
            new OA\Parameter(name: "sort_order", in: "query", description: "Sort order", required: false, schema: new OA\Schema(type: "string", enum: ["asc", "desc"], default: "desc")),
            new OA\Parameter(name: "per_page", in: "query", description: "Items per page (max 100)", required: false, schema: new OA\Schema(type: "integer", default: 15, maximum: 100))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Product")),
                        new OA\Property(
                            property: "pagination",
                            type: "object",
                            properties: [
                                new OA\Property(property: "current_page", type: "integer"),
                                new OA\Property(property: "per_page", type: "integer"),
                                new OA\Property(property: "total", type: "integer"),
                                new OA\Property(property: "last_page", type: "integer")
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Product::with('variants');

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('vendor')) {
            $query->where('vendor', $request->vendor);
        }

        if ($request->has('product_type')) {
            $query->where('product_type', $request->product_type);
        }

        if ($request->has('in_stock') && $request->boolean('in_stock')) {
            $query->inStock();
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('sku', 'LIKE', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ]
        ]);
    }

    #[OA\Post(
        path: "/api/products",
        summary: "Create a new product",
        description: "Create a new product with all required and optional fields",
        operationId: "createProduct",
        tags: ["Products"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/ProductRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Product created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Product created successfully"),
                        new OA\Property(property: "data", ref: "#/components/schemas/Product")
                    ]
                )
            ),
        ]
    )]
    public function store(ProductStoreRequest $request): JsonResponse
    {
        try {
            $product = Product::create($request->validated());
            $product->load('variants');

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/products/{id}",
        summary: "Get a specific product",
        description: "Retrieve a single product by ID including its variants",
        operationId: "getProduct",
        tags: ["Products"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", description: "Product ID", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/Product")
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
    public function show(string $id): JsonResponse
    {
        try {
            $product = Product::with('variants')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $product
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }
    }

    #[OA\Put(
        path: "/api/products/{id}",
        summary: "Update a product",
        description: "Update an existing product by ID",
        operationId: "updateProduct",
        tags: ["Products"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", description: "Product ID", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/ProductUpdateRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Product updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Product updated successfully"),
                        new OA\Property(property: "data", ref: "#/components/schemas/Product")
                    ]
                )
            ),
        ]
    )]
    public function update(ProductUpdateRequest $request, string $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $product->update($request->validated());
            $product->load('variants');

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Delete(
        path: "/api/products/{id}",
        summary: "Delete a product",
        description: "Delete a product and all its variants",
        operationId: "deleteProduct",
        tags: ["Products"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", description: "Product ID", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Product deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Product deleted successfully")
                    ]
                )
            ),
        ]
    )]
    public function destroy(string $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
