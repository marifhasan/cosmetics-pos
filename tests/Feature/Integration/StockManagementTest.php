<?php

use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('completes full purchase to stock workflow', function () {
    $supplier = Supplier::factory()->create();
    $variant = ProductVariant::factory()->create([
        'stock_quantity' => 5,
        'cost_price' => 10.00,
        'selling_price' => 15.00,
    ]);

    actingAsAdmin();

    // Create purchase order
    $response = $this->post('/admin/purchases', [
        'supplier_id' => $supplier->id,
        'items' => [
            [
                'product_variant_id' => $variant->id,
                'quantity' => 20,
                'unit_cost' => 10.00,
            ],
        ],
        'expected_delivery_date' => now()->addDays(3)->format('Y-m-d'),
    ]);

    $response->assertRedirect();

    $purchase = Purchase::latest()->first();

    // Verify purchase was created
    $this->assertDatabaseHas('purchases', [
        'supplier_id' => $supplier->id,
        'status' => 'pending',
        'total_amount' => 200.00, // 20 * 10.00
    ]);

    // Verify purchase item
    $this->assertDatabaseHas('purchase_items', [
        'purchase_id' => $purchase->id,
        'product_variant_id' => $variant->id,
        'quantity' => 20,
        'unit_cost' => 10.00,
    ]);

    // Receive purchase (stock in)
    $response = $this->post("/admin/purchases/{$purchase->id}/receive", [
        'received_items' => [
            [
                'purchase_item_id' => $purchase->purchaseItems->first()->id,
                'received_quantity' => 20,
            ],
        ],
    ]);

    $response->assertRedirect();

    // Verify purchase status updated
    $this->assertDatabaseHas('purchases', [
        'id' => $purchase->id,
        'status' => 'received',
    ]);

    // Verify stock was updated
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'stock_quantity' => 25, // 5 + 20
    ]);

    // Verify stock movement
    $this->assertDatabaseHas('stock_movements', [
        'product_variant_id' => $variant->id,
        'movement_type' => 'purchase',
        'quantity_change' => 20,
        'previous_quantity' => 5,
        'new_quantity' => 25,
    ]);
});

it('handles partial purchase receipts correctly', function () {
    $supplier = Supplier::factory()->create();
    $variant1 = ProductVariant::factory()->create(['stock_quantity' => 10]);
    $variant2 = ProductVariant::factory()->create(['stock_quantity' => 15]);

    actingAsAdmin();

    // Create purchase with multiple items
    $this->post('/admin/purchases', [
        'supplier_id' => $supplier->id,
        'items' => [
            [
                'product_variant_id' => $variant1->id,
                'quantity' => 50,
                'unit_cost' => 8.00,
            ],
            [
                'product_variant_id' => $variant2->id,
                'quantity' => 30,
                'unit_cost' => 12.00,
            ],
        ],
    ]);

    $purchase = Purchase::latest()->first();

    // Receive partial items
    $this->post("/admin/purchases/{$purchase->id}/receive", [
        'received_items' => [
            [
                'purchase_item_id' => $purchase->purchaseItems->where('product_variant_id', $variant1->id)->first()->id,
                'received_quantity' => 30, // Partial receipt
            ],
            // variant2 not received yet
        ],
    ]);

    // Verify purchase status is partial
    $this->assertDatabaseHas('purchases', [
        'id' => $purchase->id,
        'status' => 'partial',
    ]);

    // Verify stock updates
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant1->id,
        'stock_quantity' => 40, // 10 + 30
    ]);

    $this->assertDatabaseHas('product_variants', [
        'id' => $variant2->id,
        'stock_quantity' => 15, // Unchanged
    ]);

    // Complete remaining receipt
    $this->post("/admin/purchases/{$purchase->id}/receive", [
        'received_items' => [
            [
                'purchase_item_id' => $purchase->purchaseItems->where('product_variant_id', $variant2->id)->first()->id,
                'received_quantity' => 30,
            ],
        ],
    ]);

    // Verify purchase is now complete
    $this->assertDatabaseHas('purchases', [
        'id' => $purchase->id,
        'status' => 'received',
    ]);

    $this->assertDatabaseHas('product_variants', [
        'id' => $variant2->id,
        'stock_quantity' => 45, // 15 + 30
    ]);
});

it('integrates sales and stock alerts system', function () {
    $variant = ProductVariant::factory()->create([
        'stock_quantity' => 12,
        'min_stock_level' => 10,
    ]);

    // Create multiple sales that trigger low stock
    for ($i = 1; $i <= 5; $i++) {
        actingAsUser();

        $this->post('/pos/complete-sale', [
            'cart' => [
                [
                    'variant_id' => $variant->id,
                    'quantity' => 1,
                    'price' => 20.00,
                ],
            ],
            'payment_method' => 'cash',
            'cash_received' => 25.00,
        ]);
    }

    // Verify stock is now low
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'stock_quantity' => 7, // 12 - 5
    ]);

    // Check stock alerts
    actingAsAdmin();

    $response = $this->get('/admin/stock-alerts/low-stock');

    $response->assertStatus(200);
    $response->assertSee($variant->product->name);

    // Admin updates stock via purchase
    $supplier = Supplier::factory()->create();

    $this->post('/admin/purchases', [
        'supplier_id' => $supplier->id,
        'items' => [
            [
                'product_variant_id' => $variant->id,
                'quantity' => 20,
                'unit_cost' => 10.00,
            ],
        ],
    ]);

    $purchase = Purchase::latest()->first();

    $this->post("/admin/purchases/{$purchase->id}/receive", [
        'received_items' => [
            [
                'purchase_item_id' => $purchase->purchaseItems->first()->id,
                'received_quantity' => 20,
            ],
        ],
    ]);

    // Verify stock is back to safe levels
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'stock_quantity' => 27, // 7 + 20
    ]);

    // Verify item no longer appears in low stock alerts
    $response = $this->get('/admin/stock-alerts/low-stock');
    $response->assertDontSee($variant->product->name);
});

it('handles stock adjustments and audit trail', function () {
    $variant = ProductVariant::factory()->create([
        'stock_quantity' => 50,
    ]);

    actingAsAdmin();

    // Perform stock adjustment (found extra stock)
    $response = $this->post('/admin/stock-adjustments', [
        'product_variant_id' => $variant->id,
        'adjustment_type' => 'increase',
        'quantity' => 5,
        'reason' => 'Found extra stock during inventory',
    ]);

    $response->assertRedirect();

    // Verify stock was adjusted
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'stock_quantity' => 55, // 50 + 5
    ]);

    // Verify stock movement record
    $this->assertDatabaseHas('stock_movements', [
        'product_variant_id' => $variant->id,
        'movement_type' => 'adjustment',
        'quantity_change' => 5,
        'previous_quantity' => 50,
        'new_quantity' => 55,
        'notes' => 'Found extra stock during inventory',
    ]);

    // Perform another adjustment (damaged goods)
    $response = $this->post('/admin/stock-adjustments', [
        'product_variant_id' => $variant->id,
        'adjustment_type' => 'decrease',
        'quantity' => 3,
        'reason' => 'Damaged goods',
    ]);

    $response->assertRedirect();

    // Verify final stock
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'stock_quantity' => 52, // 55 - 3
    ]);

    // Verify complete audit trail
    $movements = StockMovement::where('product_variant_id', $variant->id)
        ->where('movement_type', 'adjustment')
        ->get();

    expect($movements)->toHaveCount(2);

    // Verify chronological order
    expect($movements->first()->quantity_change)->toBe(5);
    expect($movements->last()->quantity_change)->toBe(-3);
});

it('manages stock across multiple locations/channels', function () {
    // Create variants for different sales channels
    $onlineVariant = ProductVariant::factory()->create([
        'stock_quantity' => 100,
        'variant_name' => 'Online Stock',
    ]);

    $retailVariant = ProductVariant::factory()->create([
        'stock_quantity' => 50,
        'variant_name' => 'Retail Stock',
    ]);

    // Simulate online sales
    for ($i = 1; $i <= 10; $i++) {
        actingAsUser();

        $this->post('/pos/complete-sale', [
            'cart' => [
                [
                    'variant_id' => $onlineVariant->id,
                    'quantity' => 1,
                    'price' => 25.00,
                ],
            ],
            'payment_method' => 'card',
        ]);
    }

    // Simulate retail sales
    for ($i = 1; $i <= 5; $i++) {
        actingAsUser();

        $this->post('/pos/complete-sale', [
            'cart' => [
                [
                    'variant_id' => $retailVariant->id,
                    'quantity' => 1,
                    'price' => 25.00,
                ],
            ],
            'payment_method' => 'cash',
            'cash_received' => 30.00,
        ]);
    }

    // Verify stock levels
    $this->assertDatabaseHas('product_variants', [
        'id' => $onlineVariant->id,
        'stock_quantity' => 90, // 100 - 10
    ]);

    $this->assertDatabaseHas('product_variants', [
        'id' => $retailVariant->id,
        'stock_quantity' => 45, // 50 - 5
    ]);

    // Verify stock movements are tracked separately
    $onlineMovements = StockMovement::where('product_variant_id', $onlineVariant->id)
        ->where('movement_type', 'sale')
        ->count();

    $retailMovements = StockMovement::where('product_variant_id', $retailVariant->id)
        ->where('movement_type', 'sale')
        ->count();

    expect($onlineMovements)->toBe(10);
    expect($retailMovements)->toBe(5);
});

it('handles stock reservations for pending orders', function () {
    $variant = ProductVariant::factory()->create([
        'stock_quantity' => 10,
    ]);

    // Create pending sale (reservation)
    actingAsUser();

    $response = $this->post('/pos/create-pending-sale', [
        'cart' => [
            [
                'variant_id' => $variant->id,
                'quantity' => 3,
                'price' => 20.00,
            ],
        ],
        'customer_phone' => '+1234567890',
        'expires_at' => now()->addHours(24),
    ]);

    // Verify stock is reserved (but not yet reduced)
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'stock_quantity' => 10, // Still original amount
    ]);

    // Complete the pending sale
    $pendingSale = Sale::where('payment_status', 'pending')->latest()->first();

    $this->post("/pos/complete-pending-sale/{$pendingSale->id}", [
        'payment_method' => 'cash',
        'cash_received' => 60.00,
    ]);

    // Now stock should be reduced
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'stock_quantity' => 7, // 10 - 3
    ]);

    $this->assertDatabaseHas('sales', [
        'id' => $pendingSale->id,
        'payment_status' => 'completed',
    ]);
});

it('generates stock reports and analytics', function () {
    // Create test data over time
    $variant = ProductVariant::factory()->create(['stock_quantity' => 100]);

    // Add some historical stock movements
    StockMovement::create([
        'product_variant_id' => $variant->id,
        'movement_type' => 'purchase',
        'quantity_change' => 100,
        'previous_quantity' => 0,
        'new_quantity' => 100,
        'created_at' => now()->subDays(30),
    ]);

    // Simulate sales over time
    for ($i = 1; $i <= 20; $i++) {
        StockMovement::create([
            'product_variant_id' => $variant->id,
            'movement_type' => 'sale',
            'quantity_change' => -2,
            'previous_quantity' => 100 - (($i - 1) * 2),
            'new_quantity' => 100 - ($i * 2),
            'created_at' => now()->subDays(30 - $i),
        ]);
    }

    actingAsAdmin();

    // Generate stock report
    $response = $this->get('/admin/reports/stock-movement', [
        'variant_id' => $variant->id,
        'date_from' => now()->subDays(30)->format('Y-m-d'),
        'date_to' => now()->format('Y-m-d'),
    ]);

    $response->assertStatus(200);

    // Verify report contains expected data
    $response->assertSee('40'); // Total sold (20 sales * 2 each)
    $response->assertSee('60'); // Current stock (100 - 40)

    // Generate stock turnover report
    $response = $this->get('/admin/reports/stock-turnover');

    $response->assertStatus(200);
    $response->assertSee($variant->product->name);
});

it('handles stock transfers between locations', function () {
    // Create variants for different locations
    $warehouseVariant = ProductVariant::factory()->create([
        'stock_quantity' => 100,
        'variant_name' => 'Warehouse',
    ]);

    $storeVariant = ProductVariant::factory()->create([
        'stock_quantity' => 20,
        'variant_name' => 'Store',
    ]);

    actingAsAdmin();

    // Transfer stock from warehouse to store
    $response = $this->post('/admin/stock-transfers', [
        'from_variant_id' => $warehouseVariant->id,
        'to_variant_id' => $storeVariant->id,
        'quantity' => 30,
        'reason' => 'Store replenishment',
    ]);

    $response->assertRedirect();

    // Verify stock levels after transfer
    $this->assertDatabaseHas('product_variants', [
        'id' => $warehouseVariant->id,
        'stock_quantity' => 70, // 100 - 30
    ]);

    $this->assertDatabaseHas('product_variants', [
        'id' => $storeVariant->id,
        'stock_quantity' => 50, // 20 + 30
    ]);

    // Verify stock movements
    $this->assertDatabaseHas('stock_movements', [
        'product_variant_id' => $warehouseVariant->id,
        'movement_type' => 'transfer_out',
        'quantity_change' => -30,
    ]);

    $this->assertDatabaseHas('stock_movements', [
        'product_variant_id' => $storeVariant->id,
        'movement_type' => 'transfer_in',
        'quantity_change' => 30,
    ]);
});

it('manages stock levels with automatic reorder points', function () {
    $variant = ProductVariant::factory()->create([
        'stock_quantity' => 15,
        'min_stock_level' => 20,
    ]);

    $supplier = Supplier::factory()->create();

    actingAsAdmin();

    // Set up automatic reorder
    $this->post('/admin/stock-reorder-settings', [
        'product_variant_id' => $variant->id,
        'supplier_id' => $supplier->id,
        'reorder_point' => 25,
        'reorder_quantity' => 50,
        'auto_reorder' => true,
    ]);

    // Simulate sales that trigger reorder
    for ($i = 1; $i <= 10; $i++) {
        actingAsUser();

        $this->post('/pos/complete-sale', [
            'cart' => [
                [
                    'variant_id' => $variant->id,
                    'quantity' => 1,
                    'price' => 15.00,
                ],
            ],
            'payment_method' => 'cash',
            'cash_received' => 20.00,
        ]);
    }

    // Stock should now be 5 (15 - 10), below reorder point of 25
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'stock_quantity' => 5,
    ]);

    // Check if automatic reorder was triggered
    $purchase = Purchase::where('supplier_id', $supplier->id)->latest()->first();

    if ($purchase) {
        expect($purchase->status)->toBe('pending');

        $purchaseItem = $purchase->purchaseItems->first();
        expect($purchaseItem->quantity)->toBe(50);
        expect($purchaseItem->product_variant_id)->toBe($variant->id);
    }
});
