<?php

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Testing\Fluent\AssertableJson;

test('Product List - Basic listing with variants', function () {
    // Clear existing products
    Product::query()->delete();
    
    // Create products of different types
    Product::factory()
        ->electronics()
        ->active()
        ->has(
            ProductVariant::factory()
                ->electronics()
                ->inStock()
                ->count(2)
                ->sequence(
                    ['position' => 1],
                    ['position' => 2]
                )
            , 'variants'
        )
        ->create();

    Product::factory()
        ->clothing()
        ->active()
        ->has(
            ProductVariant::factory()
                ->clothing()
                ->count(3)
                ->sequence(
                    ['option1' => 'S', 'option2' => 'Red', 'position' => 1],
                    ['option1' => 'M', 'option2' => 'Blue', 'position' => 2],
                    ['option1' => 'L', 'option2' => 'Green', 'position' => 3]
                )
            , 'variants'
        )
        ->create();

    Product::factory()
        ->book()
        ->active()
        ->has(
            ProductVariant::factory()
                ->book()
                ->count(2)
                ->sequence(
                    ['option1' => 'Hardcover', 'option2' => 'New', 'position' => 1],
                    ['option1' => 'Digital', 'option2' => 'New', 'position' => 2]
                )
            , 'variants'
        )
        ->create();

    // Make the API request
    $response = $this->get('/api/products');
    
    // Assert response structure
    $response->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) =>
            $json->has('success')
                 ->where('success', true)
                 ->has('data', 3)
                 ->has('data.0', fn (AssertableJson $json) =>
                     $json->has('id')
                          ->has('name')
                          ->has('sku')
                          ->has('price')
                          ->has('variants')
                          ->etc()
                 )
                 ->has('pagination', fn (AssertableJson $json) =>
                     $json->has('current_page')
                          ->has('per_page')
                          ->has('total')
                          ->has('last_page')
                          ->etc()
                 )
        );

    // Get response data
    $responseData = $response->json();
    
    // Verify pagination
    expect($responseData['pagination']['total'])->toBe(3);
    
    // Verify variants count
    $products = collect($responseData['data']);
    expect($products->firstWhere('product_type', 'Electronics')['variants'])->toHaveCount(2);
    expect($products->firstWhere('product_type', 'Clothing')['variants'])->toHaveCount(3);
    expect($products->firstWhere('product_type', 'Books')['variants'])->toHaveCount(2);
});

test('Product List - Filter and search functionality', function () {
    // Clear existing products
    Product::query()->delete();
    
    // Create products with different statuses and prices
    Product::factory()
        ->count(2)
        ->active()
        ->onSale()
        ->sequence(
            ['vendor' => 'Apple', 'product_type' => 'Electronics'],
            ['vendor' => 'Samsung', 'product_type' => 'Electronics']
        )
        ->create();
    
    Product::factory()
        ->count(2)
        ->inactive()
        ->sequence(
            ['vendor' => 'Nike', 'product_type' => 'Clothing'],
            ['vendor' => 'Adidas', 'product_type' => 'Clothing']
        )
        ->create();
    
    Product::factory()
        ->archived()
        ->create(['vendor' => 'Amazon', 'product_type' => 'Books']);

    // Test status filter
    $response = $this->get('/api/products?status=active');
    expect($response->json('pagination.total'))->toBe(2);
    expect($response->json('data.0.status'))->toBe('active');
    
    // Test vendor filter
    $response = $this->get('/api/products?vendor=Apple');
    expect($response->json('pagination.total'))->toBe(1);
    expect($response->json('data.0.vendor'))->toBe('Apple');
    
    // Test product type filter
    $response = $this->get('/api/products?product_type=Clothing');
    expect($response->json('pagination.total'))->toBe(2);
    expect($response->json('data.0.product_type'))->toBe('Clothing');
    
    // Test combined filters
    $response = $this->get('/api/products?status=active&product_type=Electronics');
    expect($response->json('pagination.total'))->toBe(2);
    
    // Test search functionality
    $searchProduct = Product::factory()->create([
        'name' => 'Special iPhone 15 Pro',
        'description' => 'Latest model with unique features',
        'sku' => 'IPH-15-PRO'
    ]);
    
    // Search by name
    $response = $this->get('/api/products?search=iPhone');
    expect($response->json('pagination.total'))->toBe(1);
    expect($response->json('data.0.name'))->toContain('iPhone');
    
    // Search by SKU
    $response = $this->get('/api/products?search=IPH-15');
    expect($response->json('pagination.total'))->toBe(1);
    expect($response->json('data.0.sku'))->toBe('IPH-15-PRO');
});

test('Product Show - Detailed product information', function () {
    // Clear existing products
    Product::query()->delete();
    
    // Create a product with variants and specific attributes
    $product = Product::factory()
        ->electronics()
        ->active()
        ->onSale()
        ->create([
            'name' => 'Test Product',
            'vendor' => 'Test Vendor',
            'tags' => ['premium', 'new'],
            'seo' => [
                'title' => 'Test Product - Premium Electronics',
                'description' => 'Test product description for SEO',
                'keywords' => 'test, product, electronics'
            ]
        ]);
    
    // Add variants with different configurations
    ProductVariant::factory()
        ->count(3)
        ->electronics()
        ->inStock()
        ->sequence(
            [
                'option1' => '128GB',
                'option2' => 'Space Gray',
                'price' => '999.99',
                'position' => 1
            ],
            [
                'option1' => '256GB',
                'option2' => 'Silver',
                'price' => '1099.99',
                'position' => 2
            ],
            [
                'option1' => '512GB',
                'option2' => 'Gold',
                'price' => '1299.99',
                'position' => 3
            ]
        )
        ->for($product)
        ->create();

    // Make API request
    $response = $this->get("/api/products/{$product->id}");
    
    // Assert response structure and data
    $response->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) =>
            $json->where('success', true)
                ->has('data', fn (AssertableJson $json) =>
                    $json->where('id', $product->id)
                        ->where('name', 'Test Product')
                        ->where('vendor', 'Test Vendor')
                        ->has('tags', 2)
                        ->has('seo', 3)
                        ->has('variants', 3)
                        ->has('variants.0', fn (AssertableJson $json) =>
                            $json->where('option1', '128GB')
                                ->where('option2', 'Space Gray')
                                ->where('price', '999.99')
                                ->etc()
                        )
                        ->etc()
                )
        );
});

test('Product API - Error handling and edge cases', function () {
    // Clear existing products
    Product::query()->delete();
    
    // Test non-existent product
    $response = $this->get('/api/products/99999');
    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Product not found'
        ]);
    
    // Test invalid status filter
    $response = $this->get('/api/products?status=invalid_status');
    $response->assertStatus(200);
    expect($response->json('data'))->toBeArray()->toBeEmpty();
    
    // Test pagination limits
    $products = Product::factory()
        ->count(15)
        ->create();
    
    // Test default pagination
    $response = $this->get('/api/products');
    expect($response->json('pagination.per_page'))->toBe(15);
    
    // Test custom pagination (within limits)
    $response = $this->get('/api/products?per_page=5');
    expect($response->json('pagination.per_page'))->toBe(5);
    
    // Test pagination limit enforcement
    $response = $this->get('/api/products?per_page=200');
    expect($response->json('pagination.per_page'))->toBe(100); // Should be capped at 100
});

test('Product List - Inventory and stock management', function () {
    // Clear existing products
    Product::query()->delete();
    
    // Create products with different inventory states
    $inStockProduct = Product::factory()
        ->active()
        ->inStock()
        ->has(
            ProductVariant::factory()
                ->inStock()
                ->count(2)
                ->sequence(
                    ['position' => 1],
                    ['position' => 2]
                )
            , 'variants'
        )
        ->create();
    
    Product::factory()
        ->active()
        ->outOfStock()
        ->has(
            ProductVariant::factory()
                ->outOfStock()
                ->count(2)
                ->sequence(
                    ['position' => 1],
                    ['position' => 2]
                )
            , 'variants'
        )
        ->create();
    
    Product::factory()
        ->active()
        ->has(
            ProductVariant::factory()
                ->count(2)
                ->sequence(
                    ['inventory_quantity' => 100, 'track_inventory' => true, 'position' => 1],
                    ['inventory_quantity' => 0, 'track_inventory' => true, 'position' => 2]
                )
            , 'variants'
        )
        ->create();

    // Test in_stock filter
    $response = $this->get('/api/products?in_stock=1');
    $responseData = $response->json();
    
    // Should include products with any variants in stock or main product in stock
    expect($responseData['pagination']['total'])->toBe(2); // inStockProduct and mixedStockProduct
    
    // Verify inventory quantities
    $products = collect($responseData['data']);
    $inStockProductData = $products->firstWhere('id', $inStockProduct->id);
    expect($inStockProductData['inventory_quantity'])->toBeGreaterThan(0);
    expect(collect($inStockProductData['variants'])->every(fn ($variant) => $variant['inventory_quantity'] > 0))->toBeTrue();
});
