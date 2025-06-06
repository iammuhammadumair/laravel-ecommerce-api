<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OpenApi\Attributes as OA;

/**
 * ProductVariant Model
 *
 * Represents a specific variant of a product with its unique attributes and inventory.
 *
 * @property int $id The unique identifier of the variant
 * @property int $product_id The ID of the parent product
 * @property string $title The display title of the variant
 * @property string $sku The unique Stock Keeping Unit
 * @property float $price The variant's price
 * @property float|null $compare_price The original/compare price for sale items
 * @property int $inventory_quantity The available quantity in stock
 * @property bool $track_inventory Whether inventory tracking is enabled
 * @property string $inventory_policy The inventory policy (deny or continue)
 * @property string $fulfillment_service The fulfillment service type
 * @property string $option1 First option value (e.g., size)
 * @property string|null $option2 Second option value (e.g., color)
 * @property string|null $option3 Third option value (e.g., material)
 * @property float $weight The variant's weight
 * @property string $weight_unit Weight unit (kg, g, lb, oz)
 * @property string|null $barcode The variant's barcode/SKU
 * @property array<string> $image Array of variant-specific image URLs
 * @property bool $requires_shipping Whether shipping is required
 * @property bool $taxable Whether the variant is taxable
 * @property int $position The display position/order
 * @property \Carbon\Carbon $created_at When the variant was created
 * @property \Carbon\Carbon $updated_at When the variant was last updated
 * 
 * @property-read bool $is_on_sale Whether the variant is currently on sale
 * @property-read float|null $discount_percentage Calculated discount percentage if on sale
 * @property-read string $formatted_price Formatted price with currency
 * @property-read array<string> $options Array of all option values
 * @property-read string $display_title Generated display title from options
 * 
 * @property-read Product $product The parent product relationship
 * 
 * 
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: "ProductVariant",
    title: "ProductVariant",
    description: "Product variant model",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", format: "int64", example: 1),
        new OA\Property(property: "product_id", type: "integer", format: "int64", example: 1),
        new OA\Property(property: "title", type: "string", example: "Small / Red"),
        new OA\Property(property: "sku", type: "string", example: "TSH-001-S-RED"),
        new OA\Property(property: "price", type: "number", format: "float", example: 29.99),
        new OA\Property(property: "compare_price", type: "number", format: "float", example: 39.99),
        new OA\Property(property: "inventory_quantity", type: "integer", example: 25),
        new OA\Property(property: "track_inventory", type: "boolean", example: true),
        new OA\Property(property: "inventory_policy", type: "string", enum: ["deny", "continue"], example: "deny"),
        new OA\Property(property: "fulfillment_service", type: "string", example: "manual"),
        new OA\Property(property: "option1", type: "string", example: "Small"),
        new OA\Property(property: "option2", type: "string", example: "Red"),
        new OA\Property(property: "option3", type: "string", nullable: true, example: null),
        new OA\Property(property: "weight", type: "number", format: "float", example: 0.3),
        new OA\Property(property: "weight_unit", type: "string", enum: ["kg", "g", "lb", "oz"], example: "kg"),
        new OA\Property(property: "barcode", type: "string", example: "1234567890123"),
        new OA\Property(property: "image", type: "array", items: new OA\Items(type: "string"), example: ["variant1.jpg"]),
        new OA\Property(property: "requires_shipping", type: "boolean", example: true),
        new OA\Property(property: "taxable", type: "boolean", example: true),
        new OA\Property(property: "position", type: "integer", example: 1),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time")
    ]
)]
class ProductVariant extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'product_id',
        'title',
        'sku',
        'price',
        'compare_price',
        'inventory_quantity',
        'track_inventory',
        'inventory_policy',
        'fulfillment_service',
        'option1',
        'option2',
        'option3',
        'weight',
        'weight_unit',
        'barcode',
        'image',
        'requires_shipping',
        'taxable',
        'position',
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
        'taxable' => 'boolean',
        'position' => 'integer',
        'image' => 'array',
    ];

    /**
     * Get the product that owns this variant.
     * 
     * Each variant belongs to a single product and inherits certain
     * attributes from its parent product.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope a query to only include variants that are in stock.
     * 
     * In-stock variants have a positive inventory quantity and
     * are available for purchase.
     *
     * @param Builder<ProductVariant> $query
     * @return Builder<ProductVariant>
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('inventory_quantity', '>', 0);
    }

    /**
     * Scope a query to filter by option value.
     * 
     * Filter variants by a specific option value (e.g., size="Small" or color="Red").
     *
     * @param Builder<ProductVariant> $query
     * @param string $option The option field to filter (option1, option2, option3)
     * @param string $value The option value to match
     * @return Builder<ProductVariant>
     */
    public function scopeByOption(Builder $query, string $option, string $value): Builder
    {
        return $query->where($option, $value);
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
     * Determine if the variant is on sale.
     * 
     * A variant is considered on sale when it has a compare price
     * that is higher than the current price.
     *
     * @return bool
     */
    public function getIsOnSaleAttribute(): bool
    {
        return $this->compare_price && $this->compare_price > $this->price;
    }

    /**
     * Calculate the discount percentage if the variant is on sale.
     * 
     * Returns null if the variant is not on sale.
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
     * Get the variant options as an array.
     * 
     * Returns an array of non-null option values that define this variant
     * (e.g., ["Small", "Red"] for a small red t-shirt).
     *
     * @return array<string>
     */
    public function getOptionsAttribute(): array
    {
        return array_filter([
            $this->option1,
            $this->option2,
            $this->option3,
        ]);
    }

    /**
     * Get the display title for the variant.
     * 
     * If options are set, returns them joined with " / ".
     * Otherwise returns the variant title.
     *
     * @return string
     */
    public function getDisplayTitleAttribute(): string
    {
        $options = $this->options;

        return $options ? implode(' / ', $options) : $this->title;
    }

    /**
     * Check if the variant is in stock.
     * 
     * A variant is considered in stock if it has a positive inventory quantity.
     *
     * @return bool
     */
    public function isInStock(): bool
    {
        return $this->inventory_quantity > 0;
    }

    /**
     * Check if the variant can fulfill the requested quantity.
     * 
     * Takes into account inventory tracking settings and inventory policy.
     *
     * @param int $quantity The quantity to check
     * @return bool Whether the variant can fulfill the quantity
     */
    public function canFulfill(int $quantity = 1): bool
    {
        if (! $this->track_inventory) {
            return true;
        }

        if ($this->inventory_policy === 'continue') {
            return true;
        }

        return $this->inventory_quantity >= $quantity;
    }

    /**
     * Decrement the variant's inventory by the specified quantity.
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

        if ($this->canFulfill($quantity)) {
            $this->decrement('inventory_quantity', $quantity);

            return true;
        }

        return false;
    }

    /**
     * Increment the variant's inventory by the specified quantity.
     * 
     * Only increments if inventory tracking is enabled.
     *
     * @param int $quantity The quantity to increment
     * @return void
     */
    public function incrementInventory(int $quantity = 1): void
    {
        if ($this->track_inventory) {
            $this->increment('inventory_quantity', $quantity);
        }
    }
}
