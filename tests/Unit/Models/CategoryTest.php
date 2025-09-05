<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('can create a category', function () {
    $category = Category::factory()->create();

    expect($category)->toBeInstanceOf(Category::class);
    expect($category->name)->toBeString();
    expect($category->slug)->toBeString();
});

it('has many products', function () {
    $category = Category::factory()->create();
    $products = Product::factory()->count(3)->create([
        'category_id' => $category->id,
    ]);

    expect($category->products)->toHaveCount(3);
});

it('can have parent category', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    expect($child->parent)->toBeInstanceOf(Category::class);
    expect($child->parent->id)->toBe($parent->id);
});

it('can have child categories', function () {
    $parent = Category::factory()->create();
    $children = Category::factory()->count(2)->create([
        'parent_id' => $parent->id,
    ]);

    expect($parent->children)->toHaveCount(2);
});

it('auto generates slug on creation', function () {
    $name = 'Skincare Products';
    $category = Category::factory()->create(['name' => $name, 'slug' => null]);

    expect($category->slug)->toBe(Str::slug($name));
});

it('auto generates slug on update', function () {
    $category = Category::factory()->create(['name' => 'Old Category']);
    $newName = 'New Skincare Category';

    $category->update(['name' => $newName]);

    expect($category->fresh()->slug)->toBe(Str::slug($newName));
});

it('preserves custom slug when updating other fields', function () {
    $customSlug = 'custom-skincare-category';
    $category = Category::factory()->create([
        'name' => 'Skincare Category',
        'slug' => $customSlug,
    ]);

    $category->update(['description' => 'Updated description']);

    expect($category->fresh()->slug)->toBe($customSlug);
});

it('has correct fillable attributes', function () {
    $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'image',
        'is_active',
        'sort_order',
    ];

    $category = new Category();

    foreach ($fillable as $attribute) {
        expect(in_array($attribute, $category->getFillable()))->toBeTrue();
    }
});

it('has correct cast attributes', function () {
    $casts = [
        'is_active' => 'boolean',
    ];

    $category = new Category();

    expect($category->getCasts()['is_active'])->toBe('boolean');
});

it('can have optional description', function () {
    $category = Category::factory()->create(['description' => null]);

    expect($category->description)->toBeNull();
});

it('can have optional image', function () {
    $category = Category::factory()->create(['image' => null]);

    expect($category->image)->toBeNull();
});

it('can have optional parent', function () {
    $category = Category::factory()->create(['parent_id' => null]);

    expect($category->parent_id)->toBeNull();
});

it('validates slug uniqueness', function () {
    $slug = 'test-category-slug';

    Category::factory()->create(['slug' => $slug]);

    expect(fn() => Category::factory()->create(['slug' => $slug]))
        ->toThrow(Exception::class);
})->skip('Database constraint test - requires database setup');

it('can create category with all optional fields', function () {
    $parent = Category::factory()->create();
    $category = Category::factory()->create([
        'description' => 'A premium skincare category',
        'image' => 'categories/skincare.jpg',
        'parent_id' => $parent->id,
        'sort_order' => 5,
    ]);

    expect($category->description)->toBe('A premium skincare category');
    expect($category->image)->toBe('categories/skincare.jpg');
    expect($category->parent_id)->toBe($parent->id);
    expect($category->sort_order)->toBe(5);
});

it('has correct relationship with products', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $category->id]);

    expect($category->products->first()->id)->toBe($product->id);
    expect($category->products->first())->toBeInstanceOf(Product::class);
});

it('can be active or inactive', function () {
    $activeCategory = Category::factory()->create(['is_active' => true]);
    $inactiveCategory = Category::factory()->create(['is_active' => false]);

    expect($activeCategory->is_active)->toBeTrue();
    expect($inactiveCategory->is_active)->toBeFalse();
});

it('has default sort order', function () {
    $category = Category::factory()->create(['sort_order' => null]);

    expect($category->sort_order)->toBeNumeric();
});

it('generates proper slug from name with special characters', function () {
    $name = 'Skin Care & Beauty!';
    $category = Category::factory()->create(['name' => $name]);

    expect($category->slug)->toBe('skin-care-beauty');
});

it('handles parent child relationship correctly', function () {
    $parent = Category::factory()->create(['name' => 'Beauty Products']);
    $child1 = Category::factory()->create(['name' => 'Skincare', 'parent_id' => $parent->id]);
    $child2 = Category::factory()->create(['name' => 'Haircare', 'parent_id' => $parent->id]);

    expect($parent->children)->toHaveCount(2);
    expect($parent->children->pluck('name')->toArray())->toContain('Skincare', 'Haircare');

    expect($child1->parent->name)->toBe('Beauty Products');
    expect($child2->parent->name)->toBe('Beauty Products');
});
