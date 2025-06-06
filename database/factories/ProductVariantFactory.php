<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate variant-specific data
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        $colors = ['Red', 'Blue', 'Green', 'Black', 'White', 'Gray', 'Navy', 'Pink'];
        $materials = ['Cotton', 'Polyester', 'Wool', 'Leather', 'Denim', 'Silk'];
        $weightUnits = ['kg', 'g', 'lb', 'oz'];
        $fulfillmentServices = ['manual', 'automatic', 'third_party'];
        $inventoryPolicies = ['deny', 'continue'];
        
        // Generate base price
        $price = $this->faker->randomFloat(2, 10, 500);
        $comparePrice = $this->faker->boolean(25) ? $price + $this->faker->randomFloat(2, 5, 100) : null;
        
        // Generate variant title
        $option1 = $this->faker->randomElement($sizes);
        $option2 = $this->faker->randomElement($colors);
        $option3 = $this->faker->boolean(50) ? $this->faker->randomElement($materials) : null;
        
        $titleParts = array_filter([$option1, $option2, $option3]);
        $title = implode(' / ', $titleParts);
        
        // Generate SKU
        $sku = strtoupper($this->faker->bothify('VAR-???-###'));
        
        // Generate barcode
        $barcode = $this->faker->boolean(70) ? $this->faker->numerify('############') : null;
        
        // Generate images
        $imageCount = rand(1, 3);
        $images = [];
        for ($i = 0; $i < $imageCount; $i++) {
            $images[] = $this->faker->imageUrl(600, 600, 'products', true, $title);
        }
        
        return [
            'product_id' => Product::factory(),
            'title' => $title,
            'sku' => $sku,
            'price' => $price,
            'compare_price' => $comparePrice,
            'inventory_quantity' => $this->faker->numberBetween(0, 100),
            'track_inventory' => $this->faker->boolean(85),
            'inventory_policy' => $this->faker->randomElement($inventoryPolicies),
            'fulfillment_service' => $this->faker->randomElement($fulfillmentServices),
            'option1' => $option1,
            'option2' => $option2,
            'option3' => $option3,
            'weight' => $this->faker->randomFloat(2, 0.1, 5),
            'weight_unit' => $this->faker->randomElement($weightUnits),
            'barcode' => $barcode,
            'image' => $images,
            'requires_shipping' => $this->faker->boolean(90),
            'taxable' => $this->faker->boolean(95),
            'position' => $this->faker->numberBetween(1, 10),
        ];
    }

    /**
     * Create a variant for a specific product.
     */
    public function forProduct(Product|int $product): static
    {
        $productId = $product instanceof Product ? $product->id : $product;
        
        return $this->state(fn (array $attributes) => [
            'product_id' => $productId,
        ]);
    }

    /**
     * Create a variant that is in stock.
     */
    public function inStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'inventory_quantity' => $this->faker->numberBetween(10, 100),
            'track_inventory' => true,
        ]);
    }

    /**
     * Create a variant that is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'inventory_quantity' => 0,
            'track_inventory' => true,
        ]);
    }

    /**
     * Create a variant that is on sale.
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
     * Create a size variant.
     */
    public function size(string $size): static
    {
        return $this->state(fn (array $attributes) => [
            'option1' => $size,
            'title' => $size . ($attributes['option2'] ? ' / ' . $attributes['option2'] : ''),
        ]);
    }

    /**
     * Create a color variant.
     */
    public function color(string $color): static
    {
        return $this->state(fn (array $attributes) => [
            'option2' => $color,
            'title' => ($attributes['option1'] ? $attributes['option1'] . ' / ' : '') . $color,
        ]);
    }

    /**
     * Create a variant with specific size and color.
     */
    public function sizeAndColor(string $size, string $color): static
    {
        return $this->state(fn (array $attributes) => [
            'option1' => $size,
            'option2' => $color,
            'title' => $size . ' / ' . $color,
        ]);
    }

    /**
     * Create a clothing variant with size and color.
     */
    public function clothing(): static
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        $colors = ['Black', 'White', 'Red', 'Blue', 'Green', 'Gray'];
        
        $size = $this->faker->randomElement($sizes);
        $color = $this->faker->randomElement($colors);
        
        return $this->state(fn (array $attributes) => [
            'option1' => $size,
            'option2' => $color,
            'title' => $size . ' / ' . $color,
            'weight_unit' => 'g',
            'requires_shipping' => true,
        ]);
    }

    /**
     * Create an electronics variant.
     */
    public function electronics(): static
    {
        $storages = ['64GB', '128GB', '256GB', '512GB', '1TB'];
        $colors = ['Space Gray', 'Silver', 'Gold', 'Blue', 'Green'];
        
        $storage = $this->faker->randomElement($storages);
        $color = $this->faker->randomElement($colors);
        
        return $this->state(fn (array $attributes) => [
            'option1' => $storage,
            'option2' => $color,
            'title' => $storage . ' / ' . $color,
            'weight_unit' => $this->faker->randomElement(['kg', 'g']),
            'requires_shipping' => true,
        ]);
    }

    /**
     * Create a book variant (different editions).
     */
    public function book(): static
    {
        $formats = ['Hardcover', 'Paperback', 'Digital'];
        $conditions = ['New', 'Used - Good', 'Used - Acceptable'];
        
        $format = $this->faker->randomElement($formats);
        $condition = $this->faker->randomElement($conditions);
        
        return $this->state(fn (array $attributes) => [
            'option1' => $format,
            'option2' => $condition,
            'title' => $format . ' / ' . $condition,
            'weight_unit' => 'g',
            'weight' => $format === 'Digital' ? 0 : $this->faker->randomFloat(2, 0.2, 2),
            'requires_shipping' => $format !== 'Digital',
        ]);
    }

    /**
     * Create a digital variant (no shipping required).
     */
    public function digital(): static
    {
        return $this->state(fn (array $attributes) => [
            'weight' => 0,
            'requires_shipping' => false,
            'track_inventory' => false,
            'inventory_quantity' => 999999, // Unlimited for digital products
        ]);
    }

    /**
     * Create a variant with specific position.
     */
    public function position(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }
} 