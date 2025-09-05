<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('can create a product', function () {
    $product = Product::factory()->create();

    expect($product)->toBeInstanceOf(Product::class);
    expect($product->name)->toBeString();
    expect($product->slug)->toBeString();
});

it('belongs to a brand', function () {
    $product = Product::factory()->create();

    expect($product->brand)->toBeInstanceOf(Brand::class);
});

it('belongs to a category', function () {
    $product = Product::factory()->create();

    expect($product->category)->toBeInstanceOf(Category::class);
});

it('has many variants', function () {
    $product = Product::factory()->create();
    $variants = ProductVariant::factory()->count(3)->create([
        'product_id' => $product->id,
    ]);

    expect($product->variants)->toHaveCount(3);
});

it('has many stock movements through variants', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $movements = StockMovement::factory()->count(2)->create([
        'product_variant_id' => $variant->id,
    ]);

    expect($product->stockMovements)->toHaveCount(2);
});

it('scopes active products', function () {
    Product::factory()->count(4)->create(['is_active' => true]);
    Product::factory()->count(2)->create(['is_active' => false]);

    $activeProducts = Product::active()->get();

    expect($activeProducts)->toHaveCount(4);
});

it('auto generates slug on creation', function () {
    $name = 'Premium Face Cream';
    $product = Product::factory()->create(['name' => $name, 'slug' => null]);

    expect($product->slug)->toBe(Str::slug($name));
});

it('auto generates slug on update', function () {
    $product = Product::factory()->create(['name' => 'Old Name']);
    $newName = 'New Premium Face Cream';

    $product->update(['name' => $newName]);

    expect($product->fresh()->slug)->toBe(Str::slug($newName));
});

it('preserves custom slug when updating other fields', function () {
    $customSlug = 'custom-premium-cream';
    $product = Product::factory()->create([
        'name' => 'Premium Face Cream',
        'slug' => $customSlug,
    ]);

    $product->update(['description' => 'Updated description']);

    expect($product->fresh()->slug)->toBe($customSlug);
});

it('has correct fillable attributes', function () {
    $fillable = [
        'name',
        'slug',
        'description',
        'brand_id',
        'category_id',
        'barcode',
        'image',
        'is_active',
    ];

    $product = new Product();

    foreach ($fillable as $attribute) {
        expect(in_array($attribute, $product->getFillable()))->toBeTrue();
    }
});

it('has correct cast attributes', function () {
    $casts = [
        'is_active' => 'boolean',
    ];

    $product = new Product();

    expect($product->getCasts()['is_active'])->toBe('boolean');
});

it('can have optional description', function () {
    $product = Product::factory()->create(['description' => null]);

    expect($product->description)->toBeNull();
});

it('can have optional barcode', function () {
    $product = Product::factory()->create(['barcode' => null]);

    expect($product->barcode)->toBeNull();
});

it('can have optional image', function () {
    $product = Product::factory()->create(['image' => null]);

    expect($product->image)->toBeNull();
});

it('validates slug uniqueness', function () {
    $slug = 'test-product-slug';

    Product::factory()->create(['slug' => $slug]);

    expect(fn() => Product::factory()->create(['slug' => $slug]))
        ->toThrow(Exception::class);
})->skip('Database constraint test - requires database setup');

it('can create product with all optional fields', function () {
    $product = Product::factory()->create([
        'description' => 'A premium skincare product',
        'barcode' => '1234567890123',
        'image' => 'products/premium-cream.jpg',
    ]);

    expect($product->description)->toBe('A premium skincare product');
    expect($product->barcode)->toBe('1234567890123');
    expect($product->image)->toBe('products/premium-cream.jpg');
});

it('has correct relationship with variants', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    expect($product->variants->first()->id)->toBe($variant->id);
    expect($product->variants->first())->toBeInstanceOf(ProductVariant::class);
});

it('loads brand relationship correctly', function () {
    $brand = Brand::factory()->create();
    $product = Product::factory()->create(['brand_id' => $brand->id]);

    expect($product->brand->name)->toBe($brand->name);
});

it('loads category relationship correctly', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $category->id]);

    expect($product->category->name)->toBe($category->name);
});
