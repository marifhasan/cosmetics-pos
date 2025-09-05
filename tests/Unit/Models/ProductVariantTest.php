<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a product variant', function () {
    $variant = ProductVariant::factory()->create();

    expect($variant)->toBeInstanceOf(ProductVariant::class);
    expect($variant->sku)->toBeString();
    expect($variant->selling_price)->toBeFloat();
});

it('belongs to a product', function () {
    $variant = ProductVariant::factory()->create();

    expect($variant->product)->toBeInstanceOf(Product::class);
});

it('has many sale items', function () {
    $variant = ProductVariant::factory()->create();
    $saleItems = SaleItem::factory()->count(3)->create([
        'product_variant_id' => $variant->id,
    ]);

    expect($variant->saleItems)->toHaveCount(3);
});

it('has many stock movements', function () {
    $variant = ProductVariant::factory()->create();
    $movements = StockMovement::factory()->count(5)->create([
        'product_variant_id' => $variant->id,
    ]);

    expect($variant->stockMovements)->toHaveCount(5);
});

it('calculates stock status correctly', function () {
    // Test in stock
    $variant = ProductVariant::factory()->create([
        'stock_quantity' => 100,
        'min_stock_level' => 10,
    ]);
    expect($variant->stock_status)->toBe('in_stock');

    // Test low stock
    $variant->update(['stock_quantity' => 5]);
    expect($variant->stock_status)->toBe('low_stock');

    // Test out of stock
    $variant->update(['stock_quantity' => 0]);
    expect($variant->stock_status)->toBe('out_of_stock');
});

it('has stock status accessor', function () {
    $variant = ProductVariant::factory()->create();

    expect($variant->stock_status)->toBeIn(['in_stock', 'low_stock', 'out_of_stock']);
});

it('has current stock accessor', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 50]);

    expect($variant->current_stock)->toBe(50);
});

it('scopes low stock variants', function () {
    ProductVariant::factory()->count(3)->create([
        'stock_quantity' => 5,
        'min_stock_level' => 10,
    ]);

    ProductVariant::factory()->count(2)->create([
        'stock_quantity' => 50,
        'min_stock_level' => 10,
    ]);

    $lowStockVariants = ProductVariant::lowStock()->get();

    expect($lowStockVariants)->toHaveCount(3);
});

it('scopes out of stock variants', function () {
    ProductVariant::factory()->count(2)->create(['stock_quantity' => 0]);
    ProductVariant::factory()->count(3)->create(['stock_quantity' => 50]);

    $outOfStockVariants = ProductVariant::outOfStock()->get();

    expect($outOfStockVariants)->toHaveCount(2);
});

it('scopes active variants', function () {
    ProductVariant::factory()->count(3)->create(['is_active' => true]);
    ProductVariant::factory()->count(2)->create(['is_active' => false]);

    $activeVariants = ProductVariant::active()->get();

    expect($activeVariants)->toHaveCount(3);
});

it('updates stock and creates movement record', function () {
    $variant = ProductVariant::factory()->create([
        'stock_quantity' => 100,
    ]);

    $result = $variant->updateStock(25, 'sale', 1, 1, 'Test sale');

    expect($result)->toBeTrue();
    expect($variant->fresh()->stock_quantity)->toBe(75);

    $movement = StockMovement::latest()->first();
    expect($movement)->not->toBeNull();
    expect($movement->quantity_change)->toBe(-25);
    expect($movement->movement_type)->toBe('sale');
    expect($movement->reference_id)->toBe(1);
});

it('prevents negative stock', function () {
    $variant = ProductVariant::factory()->create([
        'stock_quantity' => 10,
    ]);

    expect(fn() => $variant->updateStock(50, 'sale'))
        ->toThrow(Exception::class, 'Insufficient stock');
});

it('has correct fillable attributes', function () {
    $fillable = [
        'product_id',
        'variant_name',
        'sku',
        'cost_price',
        'selling_price',
        'stock_quantity',
        'min_stock_level',
        'barcode',
        'is_active',
    ];

    $variant = new ProductVariant();

    foreach ($fillable as $attribute) {
        expect(in_array($attribute, $variant->getFillable()))->toBeTrue();
    }
});

it('has correct cast attributes', function () {
    $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'min_stock_level' => 'integer',
        'is_active' => 'boolean',
    ];

    $variant = new ProductVariant();

    foreach ($casts as $attribute => $cast) {
        expect($variant->getCasts()[$attribute])->toBe($cast);
    }
});

it('has stock status in appended attributes', function () {
    $variant = new ProductVariant();

    expect(in_array('stock_status', $variant->getAppends()))->toBeTrue();
    expect(in_array('current_stock', $variant->getAppends()))->toBeTrue();
});
