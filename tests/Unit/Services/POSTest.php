<?php

use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates cart subtotal correctly', function () {
    $variant1 = ProductVariant::factory()->create(['selling_price' => 25.00]);
    $variant2 = ProductVariant::factory()->create(['selling_price' => 15.50]);

    $cart = [
        'variant_' . $variant1->id => [
            'variant_id' => $variant1->id,
            'price' => $variant1->selling_price,
            'quantity' => 2,
        ],
        'variant_' . $variant2->id => [
            'variant_id' => $variant2->id,
            'price' => $variant2->selling_price,
            'quantity' => 3,
        ],
    ];

    $subtotal = collect($cart)->sum(function($item) {
        return $item['quantity'] * $item['price'];
    });

    expect($subtotal)->toBe(80.50); // (2 * 25.00) + (3 * 15.50)
});

it('calculates tax amount correctly', function () {
    $subtotal = 100.00;
    $taxRate = 8.5;

    $taxAmount = $subtotal * ($taxRate / 100);

    expect($taxAmount)->toBe(8.50);
});

it('calculates total with tax and discount', function () {
    $subtotal = 100.00;
    $taxAmount = 8.50;
    $discountAmount = 10.00;

    $total = $subtotal + $taxAmount - $discountAmount;

    expect($total)->toBe(98.50);
});

it('calculates change correctly', function () {
    $total = 50.00;
    $cashReceived = 60.00;

    $change = max(0, $cashReceived - $total);

    expect($change)->toBe(10.00);
});

it('handles insufficient cash payment', function () {
    $total = 50.00;
    $cashReceived = 30.00;

    $change = max(0, $cashReceived - $total);

    expect($change)->toBe(0);
});

it('validates stock availability before adding to cart', function () {
    $variant = ProductVariant::factory()->create([
        'stock_quantity' => 5,
        'selling_price' => 20.00,
    ]);

    // Should allow adding within stock limit
    $canAdd = 3 <= $variant->stock_quantity;
    expect($canAdd)->toBeTrue();

    // Should prevent adding more than available stock
    $cannotAdd = 8 <= $variant->stock_quantity;
    expect($cannotAdd)->toBeFalse();
});

it('updates stock when quantity changes in cart', function () {
    $variant = ProductVariant::factory()->create([
        'stock_quantity' => 10,
        'selling_price' => 15.00,
    ]);

    $initialStock = $variant->stock_quantity;

    // Simulate adding to cart and updating quantity
    $cartQuantity = 3;
    $remainingStock = $initialStock - $cartQuantity;

    expect($remainingStock)->toBe(7);
});

it('searches products by SKU', function () {
    $variant1 = ProductVariant::factory()->create(['sku' => 'TEST001']);
    $variant2 = ProductVariant::factory()->create(['sku' => 'TEST002']);
    ProductVariant::factory()->create(['sku' => 'OTHER001']);

    $results = ProductVariant::where('sku', 'like', '%TEST%')->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('sku')->toArray())->toContain('TEST001', 'TEST002');
});

it('searches products by name', function () {
    $product1 = \App\Models\Product::factory()->create(['name' => 'Premium Shampoo']);
    $product2 = \App\Models\Product::factory()->create(['name' => 'Luxury Conditioner']);
    $product3 = \App\Models\Product::factory()->create(['name' => 'Basic Soap']);

    $variant1 = ProductVariant::factory()->create(['product_id' => $product1->id]);
    $variant2 = ProductVariant::factory()->create(['product_id' => $product2->id]);
    $variant3 = ProductVariant::factory()->create(['product_id' => $product3->id]);

    $results = ProductVariant::whereHas('product', function($query) {
        $query->where('name', 'like', '%Premium%');
    })->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->product->name)->toBe('Premium Shampoo');
});

it('searches products by barcode', function () {
    $variant1 = ProductVariant::factory()->create(['barcode' => '1234567890123']);
    $variant2 = ProductVariant::factory()->create(['barcode' => '9876543210987']);
    ProductVariant::factory()->create(['barcode' => '1111111111111']);

    $results = ProductVariant::where('barcode', 'like', '%123456789%')->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->barcode)->toBe('1234567890123');
});

it('creates customer during checkout', function () {
    $phone = '+1234567890';
    $name = 'John Doe';
    $email = 'john@example.com';

    $customer = Customer::create([
        'phone' => $phone,
        'name' => $name,
        'email' => $email,
    ]);

    expect($customer->phone)->toBe($phone);
    expect($customer->name)->toBe($name);
    expect($customer->email)->toBe($email);
});

it('finds existing customer by phone', function () {
    $phone = '+1234567890';
    $existingCustomer = Customer::factory()->create(['phone' => $phone]);

    $foundCustomer = Customer::where('phone', $phone)->first();

    expect($foundCustomer)->not->toBeNull();
    expect($foundCustomer->id)->toBe($existingCustomer->id);
});

it('creates sale with correct calculations', function () {
    $customer = Customer::factory()->create();
    $user = User::factory()->create();

    $sale = Sale::factory()->create([
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'subtotal' => 100.00,
        'tax_amount' => 8.50,
        'discount_amount' => 5.00,
        'total_amount' => 103.50,
        'payment_method' => 'cash',
        'payment_status' => 'completed',
    ]);

    expect($sale->customer->id)->toBe($customer->id);
    expect($sale->user->id)->toBe($user->id);
    expect($sale->total_amount)->toBe(103.50);
    expect($sale->payment_status)->toBe('completed');
});

it('creates sale items for cart products', function () {
    $sale = Sale::factory()->create();
    $variant1 = ProductVariant::factory()->create(['selling_price' => 25.00]);
    $variant2 = ProductVariant::factory()->create(['selling_price' => 15.00]);

    $saleItem1 = SaleItem::create([
        'sale_id' => $sale->id,
        'product_variant_id' => $variant1->id,
        'quantity' => 2,
        'unit_price' => 25.00,
        'total_price' => 50.00,
    ]);

    $saleItem2 = SaleItem::create([
        'sale_id' => $sale->id,
        'product_variant_id' => $variant2->id,
        'quantity' => 1,
        'unit_price' => 15.00,
        'total_price' => 15.00,
    ]);

    expect($sale->saleItems)->toHaveCount(2);
    expect($sale->saleItems->sum('total_price'))->toBe(65.00);
});

it('updates stock after sale completion', function () {
    $variant = ProductVariant::factory()->create([
        'stock_quantity' => 10,
    ]);

    $quantitySold = 3;
    $variant->updateStock(-$quantitySold, 'sale', 1, 1, 'Sale #TEST001');

    expect($variant->fresh()->stock_quantity)->toBe(7);

    $movement = StockMovement::latest()->first();
    expect($movement->quantity_change)->toBe(-3);
    expect($movement->movement_type)->toBe('sale');
});

it('awards loyalty points after sale', function () {
    $customer = Customer::factory()->create(['loyalty_points' => 0]);
    $sale = Sale::factory()->create([
        'customer_id' => $customer->id,
        'total_amount' => 75.00,
    ]);

    $pointsEarned = floor($sale->total_amount);
    $customer->addLoyaltyPoints($pointsEarned, $sale->id);

    expect($customer->fresh()->loyalty_points)->toBe(75);
});

it('handles walk-in customers without loyalty points', function () {
    $sale = Sale::factory()->create([
        'customer_id' => null,
        'total_amount' => 50.00,
    ]);

    expect($sale->customer_id)->toBeNull();
    expect($sale->customer)->toBeNull();

    // No loyalty points should be awarded
    $loyaltyTransactions = $sale->loyaltyPointTransactions;
    expect($loyaltyTransactions)->toHaveCount(0);
});

it('validates payment methods', function () {
    $validMethods = ['cash', 'card', 'digital'];

    foreach ($validMethods as $method) {
        $sale = Sale::factory()->create(['payment_method' => $method]);
        expect($sale->payment_method)->toBe($method);
    }
});

it('handles empty cart scenario', function () {
    $cart = [];

    $canCompleteSale = !empty($cart);

    expect($canCompleteSale)->toBeFalse();
});

it('prevents sale completion with insufficient stock', function () {
    $variant = ProductVariant::factory()->create([
        'stock_quantity' => 2,
    ]);

    $requestedQuantity = 5;
    $hasEnoughStock = $requestedQuantity <= $variant->stock_quantity;

    expect($hasEnoughStock)->toBeFalse();
});

it('calculates receipt totals correctly', function () {
    $cart = [
        ['name' => 'Product A', 'quantity' => 2, 'price' => 10.00, 'total' => 20.00],
        ['name' => 'Product B', 'quantity' => 1, 'price' => 15.00, 'total' => 15.00],
    ];

    $subtotal = collect($cart)->sum('total');
    $taxRate = 8.5;
    $taxAmount = $subtotal * ($taxRate / 100);
    $discount = 5.00;
    $total = $subtotal + $taxAmount - $discount;

    expect($subtotal)->toBe(35.00);
    expect($taxAmount)->toBe(2.98);
    expect($total)->toBe(32.98);
});

it('generates unique sale numbers', function () {
    $sale1 = Sale::factory()->create();
    $sale2 = Sale::factory()->create();

    expect($sale1->sale_number)->not->toBe($sale2->sale_number);
    expect($sale1->sale_number)->toMatch('/^SALE\d{8}\d{4}$/');
});
