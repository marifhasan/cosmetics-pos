<?php

use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('admin can view sales list', function () {
    actingAsAdmin();

    Sale::factory()->count(5)->create();

    $response = $this->get('/admin/sales');

    $response->assertStatus(200);
    $response->assertSee('Sales');
});

it('admin can view sale details', function () {
    actingAsAdmin();

    $sale = Sale::factory()->create();

    $response = $this->get("/admin/sales/{$sale->id}");

    $response->assertStatus(200);
    $response->assertSee($sale->sale_number);
    $response->assertSee($sale->total_amount);
});

it('admin can create a new sale', function () {
    actingAsAdmin();

    $customer = Customer::factory()->create();
    $user = User::factory()->create();
    $variant = ProductVariant::factory()->create([
        'selling_price' => 25.00,
        'stock_quantity' => 10,
    ]);

    $saleData = [
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'payment_method' => 'cash',
        'payment_status' => 'completed',
        'items' => [
            [
                'product_variant_id' => $variant->id,
                'quantity' => 2,
                'unit_price' => 25.00,
            ]
        ],
    ];

    $response = $this->post('/admin/sales', $saleData);

    $response->assertRedirect();
    $this->assertDatabaseHas('sales', [
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'payment_method' => 'cash',
        'payment_status' => 'completed',
    ]);
});

it('admin can update sale payment status', function () {
    actingAsAdmin();

    $sale = Sale::factory()->create(['payment_status' => 'pending']);

    $response = $this->put("/admin/sales/{$sale->id}", [
        'payment_status' => 'completed',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('sales', [
        'id' => $sale->id,
        'payment_status' => 'completed',
    ]);
});

it('admin can refund a sale', function () {
    actingAsAdmin();

    $sale = Sale::factory()->create(['payment_status' => 'completed']);
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    // Create sale item
    SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    $response = $this->post("/admin/sales/{$sale->id}/refund");

    $response->assertRedirect();
    $this->assertDatabaseHas('sales', [
        'id' => $sale->id,
        'payment_status' => 'refunded',
    ]);

    // Check if stock was restored
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'stock_quantity' => 12, // Original 10 + 2 refunded
    ]);
});

it('admin can filter sales by date range', function () {
    actingAsAdmin();

    Sale::factory()->create(['created_at' => '2024-01-01']);
    Sale::factory()->create(['created_at' => '2024-01-15']);
    Sale::factory()->create(['created_at' => '2024-02-01']);

    $response = $this->get('/admin/sales?date_from=2024-01-01&date_to=2024-01-31');

    $response->assertStatus(200);
});

it('admin can filter sales by customer', function () {
    actingAsAdmin();

    $customer1 = Customer::factory()->create();
    $customer2 = Customer::factory()->create();

    Sale::factory()->count(3)->create(['customer_id' => $customer1->id]);
    Sale::factory()->count(2)->create(['customer_id' => $customer2->id]);

    $response = $this->get("/admin/sales?customer_id={$customer1->id}");

    $response->assertStatus(200);
});

it('admin can filter sales by payment method', function () {
    actingAsAdmin();

    Sale::factory()->count(3)->create(['payment_method' => 'cash']);
    Sale::factory()->count(2)->create(['payment_method' => 'card']);

    $response = $this->get('/admin/sales?payment_method=cash');

    $response->assertStatus(200);
});

it('admin can filter sales by payment status', function () {
    actingAsAdmin();

    Sale::factory()->count(4)->create(['payment_status' => 'completed']);
    Sale::factory()->count(2)->create(['payment_status' => 'pending']);

    $response = $this->get('/admin/sales?payment_status=completed');

    $response->assertStatus(200);
});

it('admin can search sales by sale number', function () {
    actingAsAdmin();

    $sale1 = Sale::factory()->create();
    $sale2 = Sale::factory()->create();

    $response = $this->get("/admin/sales?search={$sale1->sale_number}");

    $response->assertStatus(200);
    $response->assertSee($sale1->sale_number);
    $response->assertDontSee($sale2->sale_number);
});

it('admin can view sale receipt', function () {
    actingAsAdmin();

    $sale = Sale::factory()->create();

    $response = $this->get("/admin/sales/{$sale->id}/receipt");

    $response->assertStatus(200);
    $response->assertSee($sale->sale_number);
});

it('admin can print sale receipt', function () {
    actingAsAdmin();

    $sale = Sale::factory()->create();

    $response = $this->get("/admin/sales/{$sale->id}/print");

    $response->assertStatus(200);
});

it('admin can export sales data', function () {
    actingAsAdmin();

    Sale::factory()->count(5)->create();

    $response = $this->get('/admin/sales/export');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/csv');
});

it('admin can view sales statistics', function () {
    actingAsAdmin();

    // Create sales with different statuses and amounts
    Sale::factory()->count(5)->create([
        'total_amount' => 100.00,
        'payment_status' => 'completed',
    ]);

    Sale::factory()->count(2)->create([
        'total_amount' => 50.00,
        'payment_status' => 'pending',
    ]);

    $response = $this->get('/admin/sales/statistics');

    $response->assertStatus(200);
    $response->assertSee('500.00'); // Total from completed sales
});

it('admin can view daily sales report', function () {
    actingAsAdmin();

    Sale::factory()->count(5)->create([
        'created_at' => today(),
        'total_amount' => 50.00,
    ]);

    $response = $this->get('/admin/reports/daily-sales');

    $response->assertStatus(200);
    $response->assertSee('250.00'); // 5 * 50.00
});

it('admin can view sales by payment method report', function () {
    actingAsAdmin();

    Sale::factory()->count(3)->create(['payment_method' => 'cash', 'total_amount' => 50.00]);
    Sale::factory()->count(2)->create(['payment_method' => 'card', 'total_amount' => 75.00]);

    $response = $this->get('/admin/reports/sales-by-payment-method');

    $response->assertStatus(200);
    $response->assertSee('150.00'); // Cash total: 3 * 50.00
    $response->assertSee('150.00'); // Card total: 2 * 75.00
});

it('admin can view top selling products report', function () {
    actingAsAdmin();

    $variant1 = ProductVariant::factory()->create();
    $variant2 = ProductVariant::factory()->create();

    // Create sales items for variant1
    Sale::factory()->count(3)->create()->each(function ($sale) use ($variant1) {
        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_variant_id' => $variant1->id,
            'quantity' => 2,
        ]);
    });

    // Create sales items for variant2
    Sale::factory()->count(2)->create()->each(function ($sale) use ($variant2) {
        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_variant_id' => $variant2->id,
            'quantity' => 1,
        ]);
    });

    $response = $this->get('/admin/reports/top-products');

    $response->assertStatus(200);
});

it('admin can void a sale', function () {
    actingAsAdmin();

    $sale = Sale::factory()->create(['payment_status' => 'completed']);

    $response = $this->post("/admin/sales/{$sale->id}/void");

    $response->assertRedirect();
    $this->assertDatabaseHas('sales', [
        'id' => $sale->id,
        'payment_status' => 'cancelled',
    ]);
});

it('admin cannot modify completed sale amounts', function () {
    actingAsAdmin();

    $sale = Sale::factory()->create([
        'total_amount' => 100.00,
        'payment_status' => 'completed',
    ]);

    $response = $this->put("/admin/sales/{$sale->id}", [
        'total_amount' => 150.00,
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors();

    // Amount should remain unchanged
    $this->assertDatabaseHas('sales', [
        'id' => $sale->id,
        'total_amount' => 100.00,
    ]);
});

it('admin can add notes to sale', function () {
    actingAsAdmin();

    $sale = Sale::factory()->create();

    $notes = 'Customer requested special packaging';

    $response = $this->put("/admin/sales/{$sale->id}", [
        'notes' => $notes,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('sales', [
        'id' => $sale->id,
        'notes' => $notes,
    ]);
});

it('admin can view sale item details', function () {
    actingAsAdmin();

    $sale = Sale::factory()->create();
    $variant = ProductVariant::factory()->create(['selling_price' => 25.00]);

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price' => 25.00,
        'total_price' => 50.00,
    ]);

    $response = $this->get("/admin/sales/{$sale->id}");

    $response->assertStatus(200);
    $response->assertSee('2'); // Quantity
    $response->assertSee('25.00'); // Unit price
    $response->assertSee('50.00'); // Total price
});

it('admin can adjust sale item quantities', function () {
    actingAsAdmin();

    $sale = Sale::factory()->create();
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    // Adjust quantity
    $response = $this->put("/admin/sale-items/{$saleItem->id}", [
        'quantity' => 3,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('sale_items', [
        'id' => $saleItem->id,
        'quantity' => 3,
    ]);
});

it('admin can view sales by user/cashier', function () {
    actingAsAdmin();

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Sale::factory()->count(4)->create(['user_id' => $user1->id]);
    Sale::factory()->count(2)->create(['user_id' => $user2->id]);

    $response = $this->get("/admin/sales?user_id={$user1->id}");

    $response->assertStatus(200);
});
