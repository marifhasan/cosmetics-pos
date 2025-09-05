<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('admin can view products list', function () {
    actingAsAdmin();

    Product::factory()->count(5)->create();

    $response = $this->get('/admin/products');

    $response->assertStatus(200);
    $response->assertSee('Products');
});

it('admin can create a new product', function () {
    actingAsAdmin();

    $brand = Brand::factory()->create();
    $category = Category::factory()->create();

    $productData = [
        'name' => 'Premium Face Cream',
        'description' => 'A luxurious face cream for all skin types',
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'barcode' => '1234567890123',
        'is_active' => true,
    ];

    $response = $this->post('/admin/products', $productData);

    $response->assertRedirect();
    $this->assertDatabaseHas('products', $productData);
});

it('admin can update a product', function () {
    actingAsAdmin();

    $product = Product::factory()->create();
    $newBrand = Brand::factory()->create();

    $updatedData = [
        'name' => 'Updated Product Name',
        'brand_id' => $newBrand->id,
        'is_active' => false,
    ];

    $response = $this->put("/admin/products/{$product->id}", $updatedData);

    $response->assertRedirect();
    $this->assertDatabaseHas('products', array_merge(['id' => $product->id], $updatedData));
});

it('admin can delete a product', function () {
    actingAsAdmin();

    $product = Product::factory()->create();

    $response = $this->delete("/admin/products/{$product->id}");

    $response->assertRedirect();
    $this->assertSoftDeleted($product);
});

it('admin can view product details', function () {
    actingAsAdmin();

    $product = Product::factory()->create();

    $response = $this->get("/admin/products/{$product->id}");

    $response->assertStatus(200);
    $response->assertSee($product->name);
});

it('admin can create product variants', function () {
    actingAsAdmin();

    $product = Product::factory()->create();

    $variantData = [
        'product_id' => $product->id,
        'variant_name' => '50ml',
        'sku' => 'TEST001',
        'cost_price' => 15.00,
        'selling_price' => 25.00,
        'stock_quantity' => 100,
        'min_stock_level' => 10,
        'barcode' => '9876543210987',
        'is_active' => true,
    ];

    $response = $this->post('/admin/product-variants', $variantData);

    $response->assertRedirect();
    $this->assertDatabaseHas('product_variants', $variantData);
});

it('admin can update product variant stock', function () {
    actingAsAdmin();

    $variant = ProductVariant::factory()->create(['stock_quantity' => 50]);

    $response = $this->put("/admin/product-variants/{$variant->id}", [
        'stock_quantity' => 75,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'stock_quantity' => 75,
    ]);
});

it('admin can upload product image', function () {
    actingAsAdmin();
    Storage::fake('public');

    $product = Product::factory()->create();

    $image = UploadedFile::fake()->image('product.jpg');

    $response = $this->post("/admin/products/{$product->id}/upload-image", [
        'image' => $image,
    ]);

    $response->assertRedirect();

    // Check if image was stored
    $product->refresh();
    expect($product->image)->not->toBeNull();
    Storage::disk('public')->assertExists($product->image);
});

it('admin can activate and deactivate products', function () {
    actingAsAdmin();

    $product = Product::factory()->create(['is_active' => true]);

    // Deactivate
    $response = $this->put("/admin/products/{$product->id}", ['is_active' => false]);
    $response->assertRedirect();
    $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);

    // Activate
    $response = $this->put("/admin/products/{$product->id}", ['is_active' => true]);
    $response->assertRedirect();
    $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => true]);
});

it('admin can bulk activate products', function () {
    actingAsAdmin();

    $products = Product::factory()->count(3)->create(['is_active' => false]);

    $response = $this->post('/admin/products/bulk-activate', [
        'ids' => $products->pluck('id')->toArray(),
    ]);

    $response->assertRedirect();

    foreach ($products as $product) {
        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => true]);
    }
});

it('admin can bulk deactivate products', function () {
    actingAsAdmin();

    $products = Product::factory()->count(3)->create(['is_active' => true]);

    $response = $this->post('/admin/products/bulk-deactivate', [
        'ids' => $products->pluck('id')->toArray(),
    ]);

    $response->assertRedirect();

    foreach ($products as $product) {
        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);
    }
});

it('admin can filter products by brand', function () {
    actingAsAdmin();

    $brand1 = Brand::factory()->create();
    $brand2 = Brand::factory()->create();

    Product::factory()->count(2)->create(['brand_id' => $brand1->id]);
    Product::factory()->count(3)->create(['brand_id' => $brand2->id]);

    $response = $this->get("/admin/products?brand_id={$brand1->id}");

    $response->assertStatus(200);
    // This would depend on how Filament handles filtering
});

it('admin can filter products by category', function () {
    actingAsAdmin();

    $category1 = Category::factory()->create();
    $category2 = Category::factory()->create();

    Product::factory()->count(2)->create(['category_id' => $category1->id]);
    Product::factory()->count(3)->create(['category_id' => $category2->id]);

    $response = $this->get("/admin/products?category_id={$category1->id}");

    $response->assertStatus(200);
});

it('admin can filter products by active status', function () {
    actingAsAdmin();

    Product::factory()->count(3)->create(['is_active' => true]);
    Product::factory()->count(2)->create(['is_active' => false]);

    $response = $this->get('/admin/products?active=1');

    $response->assertStatus(200);
});

it('admin can search products by name', function () {
    actingAsAdmin();

    Product::factory()->create(['name' => 'Premium Shampoo']);
    Product::factory()->create(['name' => 'Luxury Conditioner']);
    Product::factory()->create(['name' => 'Basic Soap']);

    $response = $this->get('/admin/products?search=Premium');

    $response->assertStatus(200);
    $response->assertSee('Premium Shampoo');
    $response->assertDontSee('Luxury Conditioner');
    $response->assertDontSee('Basic Soap');
});

it('admin can search products by barcode', function () {
    actingAsAdmin();

    Product::factory()->create(['barcode' => '1234567890123']);
    Product::factory()->create(['barcode' => '9876543210987']);

    $response = $this->get('/admin/products?search=123456789');

    $response->assertStatus(200);
});

it('admin cannot create product with invalid data', function () {
    actingAsAdmin();

    $invalidData = [
        'name' => '', // Required field empty
        'brand_id' => 999, // Non-existent brand
        'category_id' => 999, // Non-existent category
    ];

    $response = $this->post('/admin/products', $invalidData);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['name', 'brand_id', 'category_id']);
});

it('admin cannot update product with invalid data', function () {
    actingAsAdmin();

    $product = Product::factory()->create();

    $invalidData = [
        'name' => '', // Required field empty
        'selling_price' => -10, // Invalid price
    ];

    $response = $this->put("/admin/products/{$product->id}", $invalidData);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['name', 'selling_price']);
});

it('admin can view product stock levels', function () {
    actingAsAdmin();

    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'stock_quantity' => 50,
        'min_stock_level' => 10,
    ]);

    $response = $this->get("/admin/products/{$product->id}");

    $response->assertStatus(200);
    $response->assertSee('50'); // Stock quantity
    $response->assertSee('10'); // Min stock level
});

it('admin can view low stock alerts for products', function () {
    actingAsAdmin();

    $product = Product::factory()->create();
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'stock_quantity' => 5,
        'min_stock_level' => 10,
        'is_active' => true,
    ]);

    $response = $this->get('/admin/stock-alerts');

    $response->assertStatus(200);
    $response->assertSee($product->name);
});

it('admin can export products data', function () {
    actingAsAdmin();

    Product::factory()->count(5)->create();

    $response = $this->get('/admin/products/export');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/csv');
});

it('admin can import products data', function () {
    actingAsAdmin();

    $csvContent = "name,brand_id,category_id,is_active\nTest Product,1,1,1";
    $csvFile = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

    $response = $this->post('/admin/products/import', [
        'file' => $csvFile,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('products', ['name' => 'Test Product']);
});
