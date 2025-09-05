<?php

use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('admin can view stock alerts dashboard', function () {
    actingAsAdmin();

    // Create various stock scenarios
    ProductVariant::factory()->count(3)->create([
        'stock_quantity' => 5,
        'min_stock_level' => 10,
        'is_active' => true,
    ]); // Low stock

    ProductVariant::factory()->count(2)->create([
        'stock_quantity' => 0,
        'min_stock_level' => 5,
        'is_active' => true,
    ]); // Out of stock

    ProductVariant::factory()->count(4)->create([
        'stock_quantity' => 50,
        'min_stock_level' => 10,
        'is_active' => true,
    ]); // In stock

    $response = $this->get('/admin/stock-alerts');

    $response->assertStatus(200);
    $response->assertSee('Stock Alerts');
});

it('admin can view low stock items', function () {
    actingAsAdmin();

    $lowStockItems = ProductVariant::factory()->count(5)->create([
        'stock_quantity' => 3,
        'min_stock_level' => 10,
        'is_active' => true,
    ]);

    $response = $this->get('/admin/stock-alerts/low-stock');

    $response->assertStatus(200);

    foreach ($lowStockItems as $item) {
        $response->assertSee($item->product->name);
    }
});

it('admin can view out of stock items', function () {
    actingAsAdmin();

    $outOfStockItems = ProductVariant::factory()->count(3)->create([
        'stock_quantity' => 0,
        'min_stock_level' => 5,
        'is_active' => true,
    ]);

    $response = $this->get('/admin/stock-alerts/out-of-stock');

    $response->assertStatus(200);

    foreach ($outOfStockItems as $item) {
        $response->assertSee($item->product->name);
    }
});

it('admin can view critical stock items', function () {
    actingAsAdmin();

    $criticalItems = ProductVariant::factory()->count(2)->create([
        'stock_quantity' => 1,
        'min_stock_level' => 10,
        'is_active' => true,
    ]);

    $response = $this->get('/admin/stock-alerts/critical');

    $response->assertStatus(200);
});

it('admin can filter stock alerts by category', function () {
    actingAsAdmin();

    $category1 = \App\Models\Category::factory()->create();
    $category2 = \App\Models\Category::factory()->create();

    $product1 = \App\Models\Product::factory()->create(['category_id' => $category1->id]);
    $product2 = \App\Models\Product::factory()->create(['category_id' => $category2->id]);

    ProductVariant::factory()->create([
        'product_id' => $product1->id,
        'stock_quantity' => 2,
        'min_stock_level' => 10,
        'is_active' => true,
    ]);

    ProductVariant::factory()->create([
        'product_id' => $product2->id,
        'stock_quantity' => 3,
        'min_stock_level' => 10,
        'is_active' => true,
    ]);

    $response = $this->get("/admin/stock-alerts?category_id={$category1->id}");

    $response->assertStatus(200);
});

it('admin can filter stock alerts by brand', function () {
    actingAsAdmin();

    $brand1 = \App\Models\Brand::factory()->create();
    $brand2 = \App\Models\Brand::factory()->create();

    $product1 = \App\Models\Product::factory()->create(['brand_id' => $brand1->id]);
    $product2 = \App\Models\Product::factory()->create(['brand_id' => $brand2->id]);

    ProductVariant::factory()->create([
        'product_id' => $product1->id,
        'stock_quantity' => 2,
        'min_stock_level' => 10,
        'is_active' => true,
    ]);

    ProductVariant::factory()->create([
        'product_id' => $product2->id,
        'stock_quantity' => 3,
        'min_stock_level' => 10,
        'is_active' => true,
    ]);

    $response = $this->get("/admin/stock-alerts?brand_id={$brand1->id}");

    $response->assertStatus(200);
});

it('admin can update stock levels from alerts', function () {
    actingAsAdmin();

    $variant = ProductVariant::factory()->create([
        'stock_quantity' => 2,
        'min_stock_level' => 10,
    ]);

    $response = $this->put("/admin/product-variants/{$variant->id}", [
        'stock_quantity' => 15,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'stock_quantity' => 15,
    ]);
});

it('admin can create stock adjustment from alerts', function () {
    actingAsAdmin();

    $variant = ProductVariant::factory()->create([
        'stock_quantity' => 5,
        'min_stock_level' => 10,
    ]);

    $response = $this->post('/admin/stock-adjustments', [
        'product_variant_id' => $variant->id,
        'adjustment_type' => 'increase',
        'quantity' => 10,
        'reason' => 'Stock replenishment',
    ]);

    $response->assertRedirect();

    // Check if stock was updated
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'stock_quantity' => 15,
    ]);

    // Check if stock movement was recorded
    $this->assertDatabaseHas('stock_movements', [
        'product_variant_id' => $variant->id,
        'movement_type' => 'adjustment',
        'quantity_change' => 10,
    ]);
});

it('admin can view stock movement history from alerts', function () {
    actingAsAdmin();

    $variant = ProductVariant::factory()->create();

    // Create some stock movements
    StockMovement::factory()->count(3)->create([
        'product_variant_id' => $variant->id,
        'movement_type' => 'sale',
    ]);

    StockMovement::factory()->count(2)->create([
        'product_variant_id' => $variant->id,
        'movement_type' => 'purchase',
    ]);

    $response = $this->get("/admin/product-variants/{$variant->id}/stock-history");

    $response->assertStatus(200);
});

it('admin can export stock alerts report', function () {
    actingAsAdmin();

    ProductVariant::factory()->count(5)->create([
        'stock_quantity' => 3,
        'min_stock_level' => 10,
        'is_active' => true,
    ]);

    $response = $this->get('/admin/stock-alerts/export');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/csv');
});

it('admin can set up automatic stock alerts', function () {
    actingAsAdmin();

    $settings = [
        'low_stock_threshold' => 20, // Alert when stock drops below 20%
        'auto_notify_admin' => true,
        'auto_notify_managers' => true,
        'alert_frequency' => 'daily',
    ];

    $response = $this->post('/admin/stock-alert-settings', $settings);

    $response->assertRedirect();

    // Verify settings were saved
    foreach ($settings as $key => $value) {
        $this->assertDatabaseHas('settings', [
            'key' => "stock_alert.{$key}",
            'value' => (string)$value,
        ]);
    }
});

it('admin can view stock levels overview', function () {
    actingAsAdmin();

    // Create various stock scenarios
    ProductVariant::factory()->count(10)->create([
        'stock_quantity' => 50,
        'min_stock_level' => 10,
        'is_active' => true,
    ]); // In stock

    ProductVariant::factory()->count(5)->create([
        'stock_quantity' => 5,
        'min_stock_level' => 10,
        'is_active' => true,
    ]); // Low stock

    ProductVariant::factory()->count(2)->create([
        'stock_quantity' => 0,
        'min_stock_level' => 10,
        'is_active' => true,
    ]); // Out of stock

    $response = $this->get('/admin/stock-overview');

    $response->assertStatus(200);
    $response->assertSee('10'); // In stock count
    $response->assertSee('5');  // Low stock count
    $response->assertSee('2');  // Out of stock count
});

it('admin can generate stock replenishment report', function () {
    actingAsAdmin();

    $lowStockItems = ProductVariant::factory()->count(5)->create([
        'stock_quantity' => 2,
        'min_stock_level' => 10,
        'is_active' => true,
    ]);

    $response = $this->get('/admin/reports/stock-replenishment');

    $response->assertStatus(200);

    foreach ($lowStockItems as $item) {
        $response->assertSee($item->product->name);
    }
});

it('admin can set minimum stock levels', function () {
    actingAsAdmin();

    $variant = ProductVariant::factory()->create([
        'stock_quantity' => 20,
        'min_stock_level' => 5,
    ]);

    $response = $this->put("/admin/product-variants/{$variant->id}", [
        'min_stock_level' => 15,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'min_stock_level' => 15,
    ]);
});

it('admin can view stock trends', function () {
    actingAsAdmin();

    $variant = ProductVariant::factory()->create();

    // Create stock movements over time
    StockMovement::factory()->create([
        'product_variant_id' => $variant->id,
        'movement_type' => 'purchase',
        'quantity_change' => 100,
        'created_at' => now()->subDays(30),
    ]);

    StockMovement::factory()->create([
        'product_variant_id' => $variant->id,
        'movement_type' => 'sale',
        'quantity_change' => -20,
        'created_at' => now()->subDays(20),
    ]);

    StockMovement::factory()->create([
        'product_variant_id' => $variant->id,
        'movement_type' => 'sale',
        'quantity_change' => -15,
        'created_at' => now()->subDays(10),
    ]);

    $response = $this->get("/admin/product-variants/{$variant->id}/stock-trends");

    $response->assertStatus(200);
});

it('admin can configure stock alert notifications', function () {
    actingAsAdmin();

    $notificationSettings = [
        'email_notifications' => true,
        'sms_notifications' => false,
        'notification_recipients' => ['admin@example.com', 'manager@example.com'],
        'low_stock_percentage' => 25,
        'critical_stock_percentage' => 10,
    ];

    $response = $this->post('/admin/stock-alert-notifications', $notificationSettings);

    $response->assertRedirect();
});

it('admin can mark stock alerts as read', function () {
    actingAsAdmin();

    $variant = ProductVariant::factory()->create([
        'stock_quantity' => 2,
        'min_stock_level' => 10,
    ]);

    // Assuming there's an alert read status
    $response = $this->post("/admin/stock-alerts/{$variant->id}/mark-read");

    $response->assertRedirect();
});

it('admin can bulk update stock levels', function () {
    actingAsAdmin();

    $variants = ProductVariant::factory()->count(3)->create([
        'stock_quantity' => 5,
        'min_stock_level' => 10,
    ]);

    $stockUpdates = [];
    foreach ($variants as $index => $variant) {
        $stockUpdates[$variant->id] = 20 + $index;
    }

    $response = $this->post('/admin/stock-alerts/bulk-update', [
        'stock_updates' => $stockUpdates,
    ]);

    $response->assertRedirect();

    foreach ($variants as $index => $variant) {
        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'stock_quantity' => 20 + $index,
        ]);
    }
});

it('admin can view stock alert history', function () {
    actingAsAdmin();

    $variant = ProductVariant::factory()->create();

    // Create alert history (assuming alerts are tracked)
    // This would depend on how alerts are implemented in the system

    $response = $this->get('/admin/stock-alerts/history');

    $response->assertStatus(200);
});

it('admin can set up automated stock reordering', function () {
    actingAsAdmin();

    $variant = ProductVariant::factory()->create([
        'stock_quantity' => 5,
        'min_stock_level' => 10,
    ]);

    $supplier = \App\Models\Supplier::factory()->create();

    $reorderSettings = [
        'product_variant_id' => $variant->id,
        'supplier_id' => $supplier->id,
        'reorder_point' => 8,
        'reorder_quantity' => 50,
        'auto_reorder' => true,
    ];

    $response = $this->post('/admin/stock-reorder-settings', $reorderSettings);

    $response->assertRedirect();
});

it('admin can view stock turnover rate', function () {
    actingAsAdmin();

    $variant = ProductVariant::factory()->create(['stock_quantity' => 100]);

    // Create sales movements
    StockMovement::factory()->count(10)->create([
        'product_variant_id' => $variant->id,
        'movement_type' => 'sale',
        'quantity_change' => -5,
        'created_at' => now()->subDays(rand(1, 30)),
    ]);

    $response = $this->get("/admin/product-variants/{$variant->id}/turnover-rate");

    $response->assertStatus(200);
});
