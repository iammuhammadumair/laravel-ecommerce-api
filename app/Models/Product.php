<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OpenApi\Attributes as OA;

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

    // Relationships
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('inventory_quantity', '>', 0);
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

    // Business Logic Methods
    public function hasVariants(): bool
    {
        return $this->variants()->count() > 0;
    }

    public function getTotalInventory(): int
    {
        if ($this->hasVariants()) {
            return $this->variants()->sum('inventory_quantity');
        }

        return $this->inventory_quantity;
    }

    public function isInStock(): bool
    {
        return $this->getTotalInventory() > 0;
    }

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
