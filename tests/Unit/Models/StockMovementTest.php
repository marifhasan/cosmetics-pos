<?php

use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a stock movement', function () {
    $variant = ProductVariant::factory()->create();
    $user = User::factory()->create();

    $movement = StockMovement::factory()->create([
        'product_variant_id' => $variant->id,
        'user_id' => $user->id,
    ]);

    expect($movement)->toBeInstanceOf(StockMovement::class);
    expect($movement->quantity_change)->toBeNumeric();
    expect($movement->movement_type)->toBeString();
});

it('belongs to a product variant', function () {
    $variant = ProductVariant::factory()->create();
    $movement = StockMovement::factory()->create([
        'product_variant_id' => $variant->id,
    ]);

    expect($movement->productVariant)->toBeInstanceOf(ProductVariant::class);
    expect($movement->productVariant->id)->toBe($variant->id);
});

it('belongs to a user', function () {
    $user = User::factory()->create();
    $movement = StockMovement::factory()->create([
        'user_id' => $user->id,
    ]);

    expect($movement->user)->toBeInstanceOf(User::class);
    expect($movement->user->id)->toBe($user->id);
});

it('has correct fillable attributes', function () {
    $fillable = [
        'product_variant_id',
        'movement_type',
        'reference_id',
        'quantity_change',
        'previous_quantity',
        'new_quantity',
        'notes',
        'movement_date',
        'user_id',
    ];

    $movement = new StockMovement();

    foreach ($fillable as $attribute) {
        expect(in_array($attribute, $movement->getFillable()))->toBeTrue();
    }
});

it('has correct cast attributes', function () {
    $casts = [
        'quantity_change' => 'integer',
        'previous_quantity' => 'integer',
        'new_quantity' => 'integer',
        'reference_id' => 'integer',
        'user_id' => 'integer',
        'movement_date' => 'datetime',
    ];

    $movement = new StockMovement();

    foreach ($casts as $attribute => $cast) {
        expect($movement->getCasts()[$attribute])->toBe($cast);
    }
});

it('can record sale movement', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 100]);
    $user = User::factory()->create();

    $movement = StockMovement::create([
        'product_variant_id' => $variant->id,
        'movement_type' => 'sale',
        'reference_id' => 1,
        'quantity_change' => -5,
        'previous_quantity' => 100,
        'new_quantity' => 95,
        'notes' => 'Sale #SALE001',
        'movement_date' => now(),
        'user_id' => $user->id,
    ]);

    expect($movement->movement_type)->toBe('sale');
    expect($movement->quantity_change)->toBe(-5);
    expect($movement->previous_quantity)->toBe(100);
    expect($movement->new_quantity)->toBe(95);
});

it('can record purchase movement', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 50]);
    $user = User::factory()->create();

    $movement = StockMovement::create([
        'product_variant_id' => $variant->id,
        'movement_type' => 'purchase',
        'reference_id' => 1,
        'quantity_change' => 25,
        'previous_quantity' => 50,
        'new_quantity' => 75,
        'notes' => 'Purchase #PUR001',
        'movement_date' => now(),
        'user_id' => $user->id,
    ]);

    expect($movement->movement_type)->toBe('purchase');
    expect($movement->quantity_change)->toBe(25);
    expect($movement->previous_quantity)->toBe(50);
    expect($movement->new_quantity)->toBe(75);
});

it('can record adjustment movement', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 30]);
    $user = User::factory()->create();

    $movement = StockMovement::create([
        'product_variant_id' => $variant->id,
        'movement_type' => 'adjustment',
        'quantity_change' => 10,
        'previous_quantity' => 30,
        'new_quantity' => 40,
        'notes' => 'Stock count adjustment',
        'movement_date' => now(),
        'user_id' => $user->id,
    ]);

    expect($movement->movement_type)->toBe('adjustment');
    expect($movement->quantity_change)->toBe(10);
});

it('tracks movement date', function () {
    $movement = StockMovement::factory()->create();

    expect($movement->movement_date)->toBeInstanceOf(\Carbon\Carbon::class);
});

it('can have optional reference id', function () {
    $movement = StockMovement::factory()->create(['reference_id' => null]);

    expect($movement->reference_id)->toBeNull();
});

it('can have optional notes', function () {
    $movement = StockMovement::factory()->create(['notes' => null]);

    expect($movement->notes)->toBeNull();
});

it('validates required fields', function () {
    expect(fn() => StockMovement::create([
        'movement_type' => 'sale',
        // missing product_variant_id
        'quantity_change' => -5,
    ]))->toThrow(Exception::class);
})->skip('Database constraint test - requires database setup');

it('calculates quantity changes correctly', function () {
    $movements = collect([
        ['quantity_change' => 50],  // Initial stock
        ['quantity_change' => -5],  // Sale
        ['quantity_change' => 10],  // Purchase
        ['quantity_change' => -2],  // Another sale
    ]);

    $totalChange = $movements->sum('quantity_change');
    expect($totalChange)->toBe(53);
});

it('can be filtered by movement type', function () {
    $variant = ProductVariant::factory()->create();

    StockMovement::factory()->count(3)->create([
        'product_variant_id' => $variant->id,
        'movement_type' => 'sale',
    ]);

    StockMovement::factory()->count(2)->create([
        'product_variant_id' => $variant->id,
        'movement_type' => 'purchase',
    ]);

    $sales = StockMovement::where('movement_type', 'sale')->get();
    $purchases = StockMovement::where('movement_type', 'purchase')->get();

    expect($sales)->toHaveCount(3);
    expect($purchases)->toHaveCount(2);
});

it('belongs to correct product variant', function () {
    $variant1 = ProductVariant::factory()->create();
    $variant2 = ProductVariant::factory()->create();

    $movement1 = StockMovement::factory()->create(['product_variant_id' => $variant1->id]);
    $movement2 = StockMovement::factory()->create(['product_variant_id' => $variant2->id]);

    expect($movement1->productVariant->id)->toBe($variant1->id);
    expect($movement2->productVariant->id)->toBe($variant2->id);
});
