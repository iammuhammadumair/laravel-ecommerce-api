<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $productTypes = ['Electronics', 'Clothing', 'Books', 'Home & Garden', 'Sports', 'Beauty', 'Toys', 'Automotive'];
        $vendors = ['Apple', 'Samsung', 'Nike', 'Adidas', 'Amazon', 'Sony', 'LG', 'Brand Co', 'Generic Brand'];
        $weightUnits = ['kg', 'g', 'lb', 'oz'];
        $statuses = ['active', 'inactive', 'archived'];
        
        // Generate base price and compare price
        $price = $this->faker->randomFloat(2, 5, 999);
        $comparePrice = $this->faker->boolean(30) ? $price + $this->faker->randomFloat(2, 5, 100) : null;
        
        // Generate product name and related data
        $productName = $this->faker->words(rand(2, 4), true);
        $productName = ucwords($productName);
        
        // Generate SKU
        $sku = strtoupper($this->faker->bothify('???-###'));
        
        // Generate tags
        $tagOptions = ['bestseller', 'new', 'featured', 'organic', 'eco-friendly', 'premium', 'limited', 'sale'];
        $tags = $this->faker->randomElements($tagOptions, rand(0, 4));
        
        // Generate images
        $imageCount = rand(1, 5);
        $images = [];
        for ($i = 0; $i < $imageCount; $i++) {
            $images[] = $this->faker->imageUrl(800, 600, 'products', true, $productName);
        }
        
        return [
            'name' => $productName,
            'description' => $this->faker->paragraph(rand(2, 4)),
            'sku' => $sku,
            'price' => $price,
            'compare_price' => $comparePrice,
            'inventory_quantity' => $this->faker->numberBetween(0, 500),
            'track_inventory' => $this->faker->boolean(80), // 80% chance of tracking inventory
            'status' => $this->faker->randomElement($statuses),
            'vendor' => $this->faker->randomElement($vendors),
            'product_type' => $this->faker->randomElement($productTypes),
            'tags' => $tags,
            'images' => $images,
            'weight' => $this->faker->randomFloat(2, 0.1, 10),
            'weight_unit' => $this->faker->randomElement($weightUnits),
            'requires_shipping' => $this->faker->boolean(85), // 85% require shipping
            'seo' => [
                'title' => $productName . ' - ' . $this->faker->words(2, true),
                'description' => $this->faker->sentence(rand(10, 20)),
                'keywords' => implode(', ', $this->faker->words(rand(5, 10)))
            ]
        ];
    }

    /**
     * Indicate that the product is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the product is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }

    /**
     * Indicate that the product is on sale.
     */
    public function onSale(): static
    {
        return $this->state(function (array $attributes) {
            $price = $attributes['price'] ?? $this->faker->randomFloat(2, 10, 200);
            return [
                'price' => $price,
                'compare_price' => $price + $this->faker->randomFloat(2, 5, 50),
            ];
        });
    }

    /**
     * Indicate that the product is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'inventory_quantity' => 0,
            'track_inventory' => true,
        ]);
    }

    /**
     * Indicate that the product is in stock.
     */
    public function inStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'inventory_quantity' => $this->faker->numberBetween(10, 100),
            'track_inventory' => true,
        ]);
    }

    /**
     * Create a product for a specific vendor.
     */
    public function forVendor(string $vendor): static
    {
        return $this->state(fn (array $attributes) => [
            'vendor' => $vendor,
        ]);
    }

    /**
     * Create a product of a specific type.
     */
    public function ofType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => $type,
        ]);
    }

    /**
     * Create an electronics product.
     */
    public function electronics(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => 'Electronics',
            'vendor' => $this->faker->randomElement(['Apple', 'Samsung', 'Sony', 'LG']),
            'weight_unit' => $this->faker->randomElement(['kg', 'g']),
            'requires_shipping' => true,
        ]);
    }

    /**
     * Create a clothing product.
     */
    public function clothing(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => 'Clothing',
            'vendor' => $this->faker->randomElement(['Nike', 'Adidas', 'H&M', 'Zara']),
            'weight_unit' => 'g',
            'requires_shipping' => true,
            'tags' => array_merge($attributes['tags'] ?? [], ['fashion', 'apparel']),
        ]);
    }

    /**
     * Create a book product.
     */
    public function book(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => 'Books',
            'vendor' => $this->faker->randomElement(['Penguin', 'Harper', 'Random House']),
            'weight_unit' => 'g',
            'weight' => $this->faker->randomFloat(2, 0.2, 2),
            'requires_shipping' => true,
        ]);
    }

    /**
     * Create a digital product (no shipping required).
     */
    public function digital(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => 'Digital',
            'weight' => 0,
            'requires_shipping' => false,
            'track_inventory' => false,
            'inventory_quantity' => 999999, // Unlimited for digital products
        ]);
    }
}
