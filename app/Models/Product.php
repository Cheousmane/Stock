<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'company_id',
        'category_id',
        'unit_id',
        'name',
        'sku',
        'barcode',
        'price',
        'price_xof',
        'purchase_price_xof',
        'cost_price_xof',
        'wholesale_price_xof',
        'description',
        'quantity',
        'min_stock',
        'is_active',
        'metadata',
        'image',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'price_xof' => 'integer',
            'purchase_price_xof' => 'integer',
            'cost_price_xof' => 'integer',
            'wholesale_price_xof' => 'integer',
            'quantity' => 'integer',
            'min_stock' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->uuid)) {
                $product->uuid = (string) Str::uuid();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function stock(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function taxes(): BelongsToMany
    {
        return $this->belongsToMany(Tax::class, 'product_taxes');
    }

    public function variants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    private ?bool $hasVariantsCache = null;

    public function hasVariants(): bool
    {
        if ($this->hasVariantsCache !== null) {
            return $this->hasVariantsCache;
        }

        return $this->hasVariantsCache = $this->variants()->where('is_active', true)->exists();
    }
}
