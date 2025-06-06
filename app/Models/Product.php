<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OpenApi\Attributes as OA;

/**
 * Product Model
 *
 * Represents a product in the e-commerce system with its variants and attributes.
 *
 * @property int $id The unique identifier of the product
 * @property string $name The name of the product
 * @property string $description The detailed description of the product
 * @property string $sku The unique Stock Keeping Unit
 * @property float $price The base price of the product
 * @property float|null $compare_price The original/compare price for sale items
 * @property int $inventory_quantity The available quantity in stock
 * @property bool $track_inventory Whether inventory tracking is enabled
 * @property string $status Product status (active, inactive, archived)
 * @property string $vendor The vendor/brand name
 * @property string $product_type The type/category of the product
 * @property array<string> $tags Array of product tags
 * @property array<string> $images Array of product image URLs
 * @property float $weight Product weight
 * @property string $weight_unit Weight unit (kg, g, lb, oz)
 * @property bool $requires_shipping Whether the product requires shipping
 * @property array{
 *     title: string,
 *     description: string,
 *     keywords: string
 * } $seo SEO metadata
 * @property \Carbon\Carbon $created_at When the product was created
 * @property \Carbon\Carbon $updated_at When the product was last updated
 * 
 * @property-read bool $is_on_sale Whether the product is currently on sale
 * @property-read float|null $discount_percentage Calculated discount percentage if on sale
 * @property-read string $formatted_price Formatted price with currency
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductVariant> $variants Product variants relationship
 * 
 * @method static Builder|static active() Scope for active products
 * @method static Builder|static inStock() Scope for in-stock products
 * 
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: "Product",
    title: "Product",
    description: "Product model",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", format: "int64", example: 1),
        new OA\Property(property: "name", type: "string", example: "T-Shirt"),
        new OA\Property(property: "description", type: "string", example: "A comfortable cotton t-shirt"),
        new OA\Property(property: "sku", type: "string", example: "TSH-001"),
        new OA\Property(property: "price", type: "number", format: "float", example: 29.99),
        new OA\Property(property: "compare_price", type: "number", format: "float", example: 39.99),
        new OA\Property(property: "inventory_quantity", type: "integer", example: 100),
        new OA\Property(property: "track_inventory", type: "boolean", example: true),
        new OA\Property(property: "status", type: "string", enum: ["active", "inactive", "archived"], example: "active"),
        new OA\Property(property: "vendor", type: "string", example: "Brand Name"),
        new OA\Property(property: "product_type", type: "string", example: "Clothing"),
        new OA\Property(property: "tags", type: "array", items: new OA\Items(type: "string"), example: ["cotton", "casual"]),
        new OA\Property(property: "images", type: "array", items: new OA\Items(type: "string"), example: ["image1.jpg", "image2.jpg"]),
        new OA\Property(property: "weight", type: "number", format: "float", example: 0.3),
        new OA\Property(property: "weight_unit", type: "string", enum: ["kg", "g", "lb", "oz"], example: "kg"),
        new OA\Property(property: "requires_shipping", type: "boolean", example: true),
        new OA\Property(
            property: "seo",
            type: "object",
            properties: [
                new OA\Property(property: "title", type: "string", example: "Comfortable Cotton T-Shirt"),
                new OA\Property(property: "description", type: "string", example: "Meta description for SEO"),
                new OA\Property(property: "keywords", type: "string", example: "t-shirt, cotton, clothing")
            ]
        ),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
        new OA\Property(
            property: "variants",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/ProductVariant")
        )
    ]
)]
class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'description',
        'sku',
        'price',
        'compare_price',
        'inventory_quantity',
        'track_inventory',
        'status',
        'vendor',
        'product_type',
        'tags',
        'images',
        'weight',
        'weight_unit',
        'requires_shipping',
        'seo',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'inventory_quantity' => 'integer',
        'track_inventory' => 'boolean',
        'requires_shipping' => 'boolean',
        'tags' => 'array',
        'images' => 'array',
        'seo' => 'array',
    ];

    /**
     * Get the variants associated with this product.
     * 
     * The variants are ordered by their position in ascending order.
     * Each variant represents a different configuration of the product
     * (e.g., different sizes, colors, or other attributes).
     *
     * @return HasMany<ProductVariant>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    /**
     * Scope a query to only include active products.
     * 
     * Active products are those that are currently available for purchase
     * and visible in the store.
     *
     * @param Builder<Product> $query
     * @return Builder<Product>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include products that are in stock.
     * 
     * In-stock products have a positive inventory quantity and
     * are available for purchase.
     *
     * @param Builder<Product> $query
     * @return Builder<Product>
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('inventory_quantity', '>', 0);
    }

    /**
     * Get the formatted price with currency symbol.
     *
     * @return string
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format((float) $this->price, 2);
    }

    /**
     * Determine if the product is on sale.
     * 
     * A product is considered on sale when it has a compare price
     * that is higher than the current price.
     *
     * @return bool
     */
    public function getIsOnSaleAttribute(): bool
    {
        return $this->compare_price && $this->compare_price > $this->price;
    }

    /**
     * Calculate the discount percentage if the product is on sale.
     * 
     * Returns null if the product is not on sale.
     *
     * @return float|null
     */
    public function getDiscountPercentageAttribute(): ?float
    {
        if (! $this->is_on_sale) {
            return null;
        }

        return round((($this->compare_price - $this->price) / $this->compare_price) * 100, 2);
    }

    /**
     * Check if the product has any variants.
     *
     * @return bool
     */
    public function hasVariants(): bool
    {
        return $this->variants()->count() > 0;
    }

    /**
     * Get the total inventory across all variants or the main product.
     * 
     * If the product has variants, returns the sum of all variant quantities.
     * Otherwise, returns the product's own inventory quantity.
     *
     * @return int
     */
    public function getTotalInventory(): int
    {
        if ($this->hasVariants()) {
            return $this->variants()->sum('inventory_quantity');
        }

        return $this->inventory_quantity;
    }

    /**
     * Check if the product is in stock.
     * 
     * A product is considered in stock if it has a positive total inventory.
     *
     * @return bool
     */
    public function isInStock(): bool
    {
        return $this->getTotalInventory() > 0;
    }

    /**
     * Decrement the product's inventory by the specified quantity.
     * 
     * Only decrements if inventory tracking is enabled and there is
     * sufficient stock available.
     *
     * @param int $quantity The quantity to decrement
     * @return bool Whether the decrement was successful
     */
    public function decrementInventory(int $quantity = 1): bool
    {
        if (! $this->track_inventory) {
            return true;
        }

        if ($this->inventory_quantity >= $quantity) {
            $this->decrement('inventory_quantity', $quantity);

            return true;
        }

        return false;
    }
}
