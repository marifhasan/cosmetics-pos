<?php

use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('completes full sales workflow from product selection to receipt', function () {
    // Setup test data
    $user = User::factory()->create();
    $customer = Customer::factory()->create([
        'phone' => '+1234567890',
        'loyalty_points' => 50,
    ]);

    $variant1 = ProductVariant::factory()->create([
        'selling_price' => 25.00,
        'stock_quantity' => 10,
    ]);

    $variant2 = ProductVariant::factory()->create([
        'selling_price' => 15.50,
        'stock_quantity' => 8,
    ]);

    actingAsUser();

    // Simulate POS workflow
    $response = $this->post('/pos/complete-sale', [
        'customer_phone' => '+1234567890',
        'cart' => [
            [
                'variant_id' => $variant1->id,
                'quantity' => 2,
                'price' => 25.00,
            ],
            [
                'variant_id' => $variant2->id,
                'quantity' => 1,
                'price' => 15.50,
            ],
        ],
        'payment_method' => 'cash',
        'cash_received' => 70.00,
    ]);

    $response->assertRedirect();

    // Verify sale was created
    $this->assertDatabaseHas('sales', [
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'subtotal' => 65.50, // (2 * 25.00) + (1 * 15.50)
        'payment_method' => 'cash',
        'payment_status' => 'completed',
    ]);

    $sale = Sale::latest()->first();

    // Verify sale items
    $this->assertDatabaseHas('sale_items', [
        'sale_id' => $sale->id,
        'product_variant_id' => $variant1->id,
        'quantity' => 2,
        'unit_price' => 25.00,
        'total_price' => 50.00,
    ]);

    $this->assertDatabaseHas('sale_items', [
        'sale_id' => $sale->id,
        'product_variant_id' => $variant2->id,
        'quantity' => 1,
        'unit_price' => 15.50,
        'total_price' => 15.50,
    ]);

    // Verify stock was updated
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant1->id,
        'stock_quantity' => 8, // 10 - 2
    ]);

    $this->assertDatabaseHas('product_variants', [
        'id' => $variant2->id,
        'stock_quantity' => 7, // 8 - 1
    ]);

    // Verify stock movements were recorded
    $this->assertDatabaseHas('stock_movements', [
        'product_variant_id' => $variant1->id,
        'movement_type' => 'sale',
        'quantity_change' => -2,
        'previous_quantity' => 10,
        'new_quantity' => 8,
    ]);

    $this->assertDatabaseHas('stock_movements', [
        'product_variant_id' => $variant2->id,
        'movement_type' => 'sale',
        'quantity_change' => -1,
        'previous_quantity' => 8,
        'new_quantity' => 7,
    ]);

    // Verify loyalty points were awarded
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'loyalty_points' => 115, // 50 + 65 (floor of 65.50)
    ]);

    // Verify loyalty point transaction
    $this->assertDatabaseHas('loyalty_point_transactions', [
        'customer_id' => $customer->id,
        'sale_id' => $sale->id,
        'transaction_type' => 'earned',
        'points_change' => 65,
    ]);
});

it('handles walk-in customer sales without loyalty points', function () {
    $user = User::factory()->create();

    $variant = ProductVariant::factory()->create([
        'selling_price' => 30.00,
        'stock_quantity' => 5,
    ]);

    actingAsUser();

    $response = $this->post('/pos/complete-sale', [
        'cart' => [
            [
                'variant_id' => $variant->id,
                'quantity' => 1,
                'price' => 30.00,
            ],
        ],
        'payment_method' => 'card',
    ]);

    $response->assertRedirect();

    // Verify sale was created without customer
    $this->assertDatabaseHas('sales', [
        'customer_id' => null,
        'user_id' => $user->id,
        'subtotal' => 30.00,
        'payment_method' => 'card',
        'payment_status' => 'completed',
        'points_earned' => 0, // No points for walk-in customers
    ]);

    $sale = Sale::latest()->first();

    // Verify no loyalty point transactions were created
    $loyaltyTransactions = $sale->loyaltyPointTransactions;
    expect($loyaltyTransactions)->toHaveCount(0);
});

it('handles sales with discounts and tax calculations', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['phone' => '+1234567890']);

    $variant = ProductVariant::factory()->create([
        'selling_price' => 100.00,
        'stock_quantity' => 10,
    ]);

    actingAsUser();

    $response = $this->post('/pos/complete-sale', [
        'customer_phone' => '+1234567890',
        'cart' => [
            [
                'variant_id' => $variant->id,
                'quantity' => 1,
                'price' => 100.00,
            ],
        ],
        'payment_method' => 'cash',
        'cash_received' => 100.00,
        'discount_amount' => 10.00,
        'tax_rate' => 8.5,
    ]);

    $response->assertRedirect();

    // Verify calculations: subtotal 100, tax 8.50, discount 10, total 98.50
    $this->assertDatabaseHas('sales', [
        'customer_id' => $customer->id,
        'subtotal' => 100.00,
        'tax_amount' => 8.50,
        'discount_amount' => 10.00,
        'total_amount' => 98.50,
    ]);

    // Verify loyalty points: floor(98.50) = 98 points
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'loyalty_points' => 98,
    ]);
});

it('prevents overselling when stock becomes unavailable', function () {
    $user = User::factory()->create();
    $variant = ProductVariant::factory()->create([
        'selling_price' => 20.00,
        'stock_quantity' => 2, // Only 2 items available
    ]);

    actingAsUser();

    // First sale - should succeed
    $response1 = $this->post('/pos/complete-sale', [
        'cart' => [
            [
                'variant_id' => $variant->id,
                'quantity' => 2,
                'price' => 20.00,
            ],
        ],
        'payment_method' => 'cash',
        'cash_received' => 40.00,
    ]);

    $response1->assertRedirect();

    // Verify stock is now 0
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'stock_quantity' => 0,
    ]);

    // Second sale attempt - should fail
    $response2 = $this->post('/pos/complete-sale', [
        'cart' => [
            [
                'variant_id' => $variant->id,
                'quantity' => 1,
                'price' => 20.00,
            ],
        ],
        'payment_method' => 'cash',
        'cash_received' => 20.00,
    ]);

    $response2->assertRedirect();
    $response2->assertSessionHasErrors();

    // Verify no additional sale was created
    $saleCount = Sale::count();
    expect($saleCount)->toBe(1);
});

it('handles multiple products in single sale correctly', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['phone' => '+1234567890']);

    // Create products from different brands/categories
    $brand1 = \App\Models\Brand::factory()->create();
    $brand2 = \App\Models\Brand::factory()->create();
    $category1 = \App\Models\Category::factory()->create();
    $category2 = \App\Models\Category::factory()->create();

    $product1 = \App\Models\Product::factory()->create([
        'brand_id' => $brand1->id,
        'category_id' => $category1->id,
    ]);

    $product2 = \App\Models\Product::factory()->create([
        'brand_id' => $brand2->id,
        'category_id' => $category2->id,
    ]);

    $variant1 = ProductVariant::factory()->create([
        'product_id' => $product1->id,
        'selling_price' => 25.00,
        'stock_quantity' => 10,
    ]);

    $variant2 = ProductVariant::factory()->create([
        'product_id' => $product2->id,
        'selling_price' => 35.00,
        'stock_quantity' => 5,
    ]);

    actingAsUser();

    $response = $this->post('/pos/complete-sale', [
        'customer_phone' => '+1234567890',
        'cart' => [
            [
                'variant_id' => $variant1->id,
                'quantity' => 2,
                'price' => 25.00,
            ],
            [
                'variant_id' => $variant2->id,
                'quantity' => 1,
                'price' => 35.00,
            ],
        ],
        'payment_method' => 'cash',
        'cash_received' => 100.00,
    ]);

    $response->assertRedirect();

    $sale = Sale::latest()->first();

    // Verify sale has correct totals
    expect($sale->subtotal)->toBe(85.00); // (2 * 25) + (1 * 35)
    expect($sale->saleItems)->toHaveCount(2);

    // Verify stock updates for both products
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant1->id,
        'stock_quantity' => 8, // 10 - 2
    ]);

    $this->assertDatabaseHas('product_variants', [
        'id' => $variant2->id,
        'stock_quantity' => 4, // 5 - 1
    ]);

    // Verify stock movements for both products
    $this->assertDatabaseHas('stock_movements', [
        'product_variant_id' => $variant1->id,
        'quantity_change' => -2,
    ]);

    $this->assertDatabaseHas('stock_movements', [
        'product_variant_id' => $variant2->id,
        'quantity_change' => -1,
    ]);
});

it('integrates with loyalty points redemption', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create([
        'phone' => '+1234567890',
        'loyalty_points' => 100,
    ]);

    $variant = ProductVariant::factory()->create([
        'selling_price' => 50.00,
        'stock_quantity' => 10,
    ]);

    actingAsUser();

    // Complete sale with loyalty points redemption
    $response = $this->post('/pos/complete-sale', [
        'customer_phone' => '+1234567890',
        'cart' => [
            [
                'variant_id' => $variant->id,
                'quantity' => 1,
                'price' => 50.00,
            ],
        ],
        'payment_method' => 'cash',
        'cash_received' => 50.00,
        'redeem_points' => 10, // Redeem 10 points
    ]);

    $response->assertRedirect();

    // Verify points were redeemed
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'loyalty_points' => 140, // 100 - 10 + 50 (earned from purchase)
    ]);

    // Verify redemption transaction
    $this->assertDatabaseHas('loyalty_point_transactions', [
        'customer_id' => $customer->id,
        'transaction_type' => 'redeemed',
        'points_change' => -10,
    ]);

    // Verify earning transaction
    $this->assertDatabaseHas('loyalty_point_transactions', [
        'customer_id' => $customer->id,
        'transaction_type' => 'earned',
        'points_change' => 50,
    ]);
});

it('handles sale cancellation and stock restoration', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['phone' => '+1234567890']);

    $variant = ProductVariant::factory()->create([
        'selling_price' => 30.00,
        'stock_quantity' => 10,
    ]);

    actingAsUser();

    // Complete sale
    $this->post('/pos/complete-sale', [
        'customer_phone' => '+1234567890',
        'cart' => [
            [
                'variant_id' => $variant->id,
                'quantity' => 2,
                'price' => 30.00,
            ],
        ],
        'payment_method' => 'cash',
        'cash_received' => 60.00,
    ]);

    $sale = Sale::latest()->first();

    // Verify initial state
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'stock_quantity' => 8, // 10 - 2
    ]);

    // Cancel sale
    $response = $this->post("/sales/{$sale->id}/cancel");

    $response->assertRedirect();

    // Verify sale was cancelled
    $this->assertDatabaseHas('sales', [
        'id' => $sale->id,
        'payment_status' => 'cancelled',
    ]);

    // Verify stock was restored
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'stock_quantity' => 10, // Back to original
    ]);

    // Verify stock movement for cancellation
    $this->assertDatabaseHas('stock_movements', [
        'product_variant_id' => $variant->id,
        'movement_type' => 'adjustment',
        'quantity_change' => 2, // Stock restored
    ]);
});

it('generates proper receipts for completed sales', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create([
        'phone' => '+1234567890',
        'name' => 'John Doe',
    ]);

    $variant = ProductVariant::factory()->create([
        'selling_price' => 25.00,
        'stock_quantity' => 10,
    ]);

    actingAsUser();

    $this->post('/pos/complete-sale', [
        'customer_phone' => '+1234567890',
        'cart' => [
            [
                'variant_id' => $variant->id,
                'quantity' => 1,
                'price' => 25.00,
            ],
        ],
        'payment_method' => 'cash',
        'cash_received' => 30.00,
    ]);

    $sale = Sale::latest()->first();

    // Generate receipt
    $response = $this->get("/sales/{$sale->id}/receipt");

    $response->assertStatus(200);
    $response->assertSee($sale->sale_number);
    $response->assertSee('John Doe');
    $response->assertSee('25.00');
    $response->assertSee('Cash');
    $response->assertSee('5.00'); // Change: 30.00 - 25.00
});

it('handles sales returns and stock adjustments', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['phone' => '+1234567890']);

    $variant = ProductVariant::factory()->create([
        'selling_price' => 40.00,
        'stock_quantity' => 10,
    ]);

    actingAsUser();

    // Complete original sale
    $this->post('/pos/complete-sale', [
        'customer_phone' => '+1234567890',
        'cart' => [
            [
                'variant_id' => $variant->id,
                'quantity' => 1,
                'price' => 40.00,
            ],
        ],
        'payment_method' => 'cash',
        'cash_received' => 40.00,
    ]);

    $originalSale = Sale::latest()->first();

    // Process return
    $response = $this->post("/sales/{$originalSale->id}/return", [
        'return_items' => [
            [
                'sale_item_id' => $originalSale->saleItems->first()->id,
                'quantity' => 1,
                'reason' => 'Customer dissatisfaction',
            ],
        ],
        'refund_amount' => 40.00,
    ]);

    $response->assertRedirect();

    // Verify stock was restored
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'stock_quantity' => 10, // Back to original
    ]);

    // Verify return transaction
    $this->assertDatabaseHas('stock_movements', [
        'product_variant_id' => $variant->id,
        'movement_type' => 'return',
        'quantity_change' => 1,
    ]);

    // Verify loyalty points were adjusted
    $this->assertDatabaseHas('loyalty_point_transactions', [
        'customer_id' => $customer->id,
        'transaction_type' => 'adjustment',
        'points_change' => -40, // Points deducted for return
    ]);
});
