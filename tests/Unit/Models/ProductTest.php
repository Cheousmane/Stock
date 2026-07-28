<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tax;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Product model', function () {

    it('belongs to company', function () {
        $company = Company::factory()->create();
        $product = Product::factory()->create(['company_id' => $company->id]);

        expect($product->company)->toBeInstanceOf(\App\Models\Company::class);
        expect($product->company->id)->toBe($company->id);
    });

    it('belongs to category (nullable)', function () {
        $company = Company::factory()->create();
        $category = Category::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
        ]);

        expect($product->category)->toBeInstanceOf(Category::class);
        expect($product->category->id)->toBe($category->id);
    });

    it('belongs to unit (nullable)', function () {
        $company = Company::factory()->create();
        $unit = Unit::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'unit_id' => $unit->id,
        ]);

        expect($product->unit)->toBeInstanceOf(Unit::class);
        expect($product->unit->id)->toBe($unit->id);
    });

    it('has many taxes (many-to-many)', function () {
        $company = Company::factory()->create();
        $product = Product::factory()->create(['company_id' => $company->id]);
        $tax = Tax::factory()->create(['company_id' => $company->id]);

        $product->taxes()->attach($tax);

        expect($product->taxes)->toHaveCount(1);
        expect($product->taxes->first())->toBeInstanceOf(Tax::class);
    });

    it('casts price as integer', function () {
        $product = new Product;
        $casts = $product->getCasts();

        expect($casts['price'])->toBe('integer');
    });

    it('casts purchase_price_xof as integer', function () {
        $product = new Product;
        $casts = $product->getCasts();

        expect($casts['purchase_price_xof'])->toBe('integer');
    });

    it('casts is_active as boolean', function () {
        $product = new Product;
        $casts = $product->getCasts();

        expect($casts['is_active'])->toBe('boolean');
    });

    it('casts metadata as array', function () {
        $product = new Product;
        $casts = $product->getCasts();

        expect($casts['metadata'])->toBe('array');
    });

});
