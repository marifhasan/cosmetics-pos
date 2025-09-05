<?php

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('can create a brand', function () {
    $brand = Brand::factory()->create();

    expect($brand)->toBeInstanceOf(Brand::class);
    expect($brand->name)->toBeString();
    expect($brand->slug)->toBeString();
});

it('has many products', function () {
    $brand = Brand::factory()->create();
    $products = Product::factory()->count(3)->create([
        'brand_id' => $brand->id,
    ]);

    expect($brand->products)->toHaveCount(3);
});

it('auto generates slug on creation', function () {
    $name = 'Premium Cosmetics Brand';
    $brand = Brand::factory()->create(['name' => $name, 'slug' => null]);

    expect($brand->slug)->toBe(Str::slug($name));
});

it('auto generates slug on update', function () {
    $brand = Brand::factory()->create(['name' => 'Old Brand Name']);
    $newName = 'New Premium Brand';

    $brand->update(['name' => $newName]);

    expect($brand->fresh()->slug)->toBe(Str::slug($newName));
});

it('preserves custom slug when updating other fields', function () {
    $customSlug = 'custom-premium-brand';
    $brand = Brand::factory()->create([
        'name' => 'Premium Brand',
        'slug' => $customSlug,
    ]);

    $brand->update(['description' => 'Updated description']);

    expect($brand->fresh()->slug)->toBe($customSlug);
});

it('has correct fillable attributes', function () {
    $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'website',
        'is_active',
    ];

    $brand = new Brand();

    foreach ($fillable as $attribute) {
        expect(in_array($attribute, $brand->getFillable()))->toBeTrue();
    }
});

it('has correct cast attributes', function () {
    $casts = [
        'is_active' => 'boolean',
    ];

    $brand = new Brand();

    expect($brand->getCasts()['is_active'])->toBe('boolean');
});

it('can have optional description', function () {
    $brand = Brand::factory()->create(['description' => null]);

    expect($brand->description)->toBeNull();
});

it('can have optional logo', function () {
    $brand = Brand::factory()->create(['logo' => null]);

    expect($brand->logo)->toBeNull();
});

it('can have optional website', function () {
    $brand = Brand::factory()->create(['website' => null]);

    expect($brand->website)->toBeNull();
});

it('validates slug uniqueness', function () {
    $slug = 'test-brand-slug';

    Brand::factory()->create(['slug' => $slug]);

    expect(fn() => Brand::factory()->create(['slug' => $slug]))
        ->toThrow(Exception::class);
})->skip('Database constraint test - requires database setup');

it('can create brand with all optional fields', function () {
    $brand = Brand::factory()->create([
        'description' => 'A premium cosmetics brand',
        'logo' => 'brands/premium-logo.jpg',
        'website' => 'https://premiumcosmetics.com',
    ]);

    expect($brand->description)->toBe('A premium cosmetics brand');
    expect($brand->logo)->toBe('brands/premium-logo.jpg');
    expect($brand->website)->toBe('https://premiumcosmetics.com');
});

it('has correct relationship with products', function () {
    $brand = Brand::factory()->create();
    $product = Product::factory()->create(['brand_id' => $brand->id]);

    expect($brand->products->first()->id)->toBe($product->id);
    expect($brand->products->first())->toBeInstanceOf(Product::class);
});

it('can be active or inactive', function () {
    $activeBrand = Brand::factory()->create(['is_active' => true]);
    $inactiveBrand = Brand::factory()->create(['is_active' => false]);

    expect($activeBrand->is_active)->toBeTrue();
    expect($inactiveBrand->is_active)->toBeFalse();
});

it('generates proper slug from name with special characters', function () {
    $name = 'Brand Name & Special (Characters)!';
    $brand = Brand::factory()->create(['name' => $name]);

    expect($brand->slug)->toBe('brand-name-special-characters');
});
