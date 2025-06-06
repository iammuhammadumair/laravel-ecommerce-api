<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OpenApi\Attributes as OA;

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

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('inventory_quantity', '>', 0);
    }

    public function scopeByOption(Builder $query, string $option, string $value): Builder
    {
        return $query->where($option, $value);
    }

    // Accessors & Mutators
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2);
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->compare_price && $this->compare_price > $this->price;
    }

    public function getDiscountPercentageAttribute(): ?float
    {
        if (! $this->is_on_sale) {
            return null;
        }

        return round((($this->compare_price - $this->price) / $this->compare_price) * 100, 2);
    }

    public function getOptionsAttribute(): array
    {
        return array_filter([
            $this->option1,
            $this->option2,
            $this->option3,
        ]);
    }

    public function getDisplayTitleAttribute(): string
    {
        $options = $this->options;

        return $options ? implode(' / ', $options) : $this->title;
    }

    // Business Logic Methods
    public function isInStock(): bool
    {
        return $this->inventory_quantity > 0;
    }

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

    public function incrementInventory(int $quantity = 1): void
    {
        if ($this->track_inventory) {
            $this->increment('inventory_quantity', $quantity);
        }
    }
}
