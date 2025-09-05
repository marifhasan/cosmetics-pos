<?php

use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('identifies low stock items', function () {
    // Create low stock items
    $lowStockItems = ProductVariant::factory()->count(3)->create([
        'stock_quantity' => 5,
        'min_stock_level' => 10,
        'is_active' => true,
    ]);

    // Create normal stock items
    ProductVariant::factory()->count(2)->create([
        'stock_quantity' => 50,
        'min_stock_level' => 10,
        'is_active' => true,
    ]);

    // Create inactive items (should not be included)
    ProductVariant::factory()->count(2)->create([
        'stock_quantity' => 5,
        'min_stock_level' => 10,
        'is_active' => false,
    ]);

    $lowStockVariants = ProductVariant::lowStock()->active()->get();

    expect($lowStockVariants)->toHaveCount(3);
    $lowStockVariants->each(function ($variant) {
        expect($variant->stock_quantity)->toBeLessThanOrEqual($variant->min_stock_level);
        expect($variant->is_active)->toBeTrue();
    });
});

it('identifies out of stock items', function () {
    // Create out of stock items
    $outOfStockItems = ProductVariant::factory()->count(2)->create([
        'stock_quantity' => 0,
        'min_stock_level' => 5,
        'is_active' => true,
    ]);

    // Create items with stock
    ProductVariant::factory()->count(3)->create([
        'stock_quantity' => 10,
        'min_stock_level' => 5,
        'is_active' => true,
    ]);

    // Create inactive out of stock items (should not be included)
    ProductVariant::factory()->count(1)->create([
        'stock_quantity' => 0,
        'min_stock_level' => 5,
        'is_active' => false,
    ]);

    $outOfStockVariants = ProductVariant::outOfStock()->active()->get();

    expect($outOfStockVariants)->toHaveCount(2);
    $outOfStockVariants->each(function ($variant) {
        expect($variant->stock_quantity)->toBe(0);
        expect($variant->is_active)->toBeTrue();
    });
});

it('identifies critical stock level items', function () {
    // Create critical stock items (stock <= 20% of min level)
    $criticalItems = ProductVariant::factory()->count(2)->create([
        'stock_quantity' => 1,
        'min_stock_level' => 5,
        'is_active' => true,
    ]);

    // Create low but not critical items
    ProductVariant::factory()->count(2)->create([
        'stock_quantity' => 3,
        'min_stock_level' => 5,
        'is_active' => true,
    ]);

    // Create normal stock items
    ProductVariant::factory()->count(2)->create([
        'stock_quantity' => 10,
        'min_stock_level' => 5,
        'is_active' => true,
    ]);

    $criticalVariants = ProductVariant::whereColumn('stock_quantity', '<=', \DB::raw('min_stock_level * 0.2'))
        ->active()
        ->get();

    expect($criticalVariants)->toHaveCount(2);
    $criticalVariants->each(function ($variant) {
        expect($variant->stock_quantity)->toBeLessThanOrEqual($variant->min_stock_level * 0.2);
    });
});

it('gets stock status summary', function () {
    // Create various stock status items
    ProductVariant::factory()->count(5)->create([
        'stock_quantity' => 50,
        'min_stock_level' => 10,
        'is_active' => true,
    ]); // in_stock

    ProductVariant::factory()->count(3)->create([
        'stock_quantity' => 5,
        'min_stock_level' => 10,
        'is_active' => true,
    ]); // low_stock

    ProductVariant::factory()->count(2)->create([
        'stock_quantity' => 0,
        'min_stock_level' => 10,
        'is_active' => true,
    ]); // out_of_stock

    $inStock = ProductVariant::whereColumn('stock_quantity', '>', 'min_stock_level')->active()->count();
    $lowStock = ProductVariant::lowStock()->active()->count();
    $outOfStock = ProductVariant::outOfStock()->active()->count();

    expect($inStock)->toBe(5);
    expect($lowStock)->toBe(3);
    expect($outOfStock)->toBe(2);
});

it('excludes inactive items from alerts', function () {
    // Create inactive low stock items
    ProductVariant::factory()->count(2)->create([
        'stock_quantity' => 5,
        'min_stock_level' => 10,
        'is_active' => false,
    ]);

    // Create active low stock items
    ProductVariant::factory()->count(3)->create([
        'stock_quantity' => 5,
        'min_stock_level' => 10,
        'is_active' => true,
    ]);

    $alerts = ProductVariant::lowStock()->active()->get();

    expect($alerts)->toHaveCount(3);
});

it('calculates stock status for individual items', function () {
    $inStockVariant = ProductVariant::factory()->create([
        'stock_quantity' => 100,
        'min_stock_level' => 10,
    ]);

    $lowStockVariant = ProductVariant::factory()->create([
        'stock_quantity' => 5,
        'min_stock_level' => 10,
    ]);

    $outOfStockVariant = ProductVariant::factory()->create([
        'stock_quantity' => 0,
        'min_stock_level' => 10,
    ]);

    expect($inStockVariant->stock_status)->toBe('in_stock');
    expect($lowStockVariant->stock_status)->toBe('low_stock');
    expect($outOfStockVariant->stock_status)->toBe('out_of_stock');
});

it('handles edge cases for stock calculations', function () {
    // Zero min stock level
    $variant1 = ProductVariant::factory()->create([
        'stock_quantity' => 10,
        'min_stock_level' => 0,
    ]);

    // Stock exactly at min level
    $variant2 = ProductVariant::factory()->create([
        'stock_quantity' => 10,
        'min_stock_level' => 10,
    ]);

    // Negative stock (shouldn't happen but test edge case)
    $variant3 = ProductVariant::factory()->create([
        'stock_quantity' => -5,
        'min_stock_level' => 10,
    ]);

    expect($variant1->stock_status)->toBe('in_stock');
    expect($variant2->stock_status)->toBe('low_stock');
    expect($variant3->stock_status)->toBe('out_of_stock');
});

it('filters alerts by product category', function () {
    $product1 = \App\Models\Product::factory()->create(['category_id' => 1]);
    $product2 = \App\Models\Product::factory()->create(['category_id' => 2]);

    ProductVariant::factory()->create([
        'product_id' => $product1->id,
        'stock_quantity' => 5,
        'min_stock_level' => 10,
        'is_active' => true,
    ]);

    ProductVariant::factory()->create([
        'product_id' => $product2->id,
        'stock_quantity' => 5,
        'min_stock_level' => 10,
        'is_active' => true,
    ]);

    $category1Alerts = ProductVariant::lowStock()
        ->active()
        ->whereHas('product', function ($query) {
            $query->where('category_id', 1);
        })
        ->get();

    expect($category1Alerts)->toHaveCount(1);
});

it('orders alerts by severity', function () {
    // Create out of stock (most severe)
    $outOfStock = ProductVariant::factory()->create([
        'stock_quantity' => 0,
        'min_stock_level' => 10,
        'is_active' => true,
    ]);

    // Create critical stock
    $critical = ProductVariant::factory()->create([
        'stock_quantity' => 1,
        'min_stock_level' => 10,
        'is_active' => true,
    ]);

    // Create low stock
    $lowStock = ProductVariant::factory()->create([
        'stock_quantity' => 5,
        'min_stock_level' => 10,
        'is_active' => true,
    ]);

    $alerts = ProductVariant::where('stock_quantity', '<=', \DB::raw('min_stock_level'))
        ->active()
        ->orderBy('stock_quantity')
        ->get();

    expect($alerts->first()->id)->toBe($outOfStock->id);
    expect($alerts->last()->id)->toBe($lowStock->id);
});
