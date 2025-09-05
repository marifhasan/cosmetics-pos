<?php

use App\Models\Customer;
use App\Models\LoyaltyPointTransaction;
use App\Models\ProductVariant;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('completes full loyalty points lifecycle', function () {
    $customer = Customer::factory()->create([
        'phone' => '+1234567890',
        'loyalty_points' => 0,
    ]);

    $variant = ProductVariant::factory()->create([
        'selling_price' => 50.00,
        'stock_quantity' => 10,
    ]);

    actingAsUser();

    // First purchase - earn points
    $this->post('/pos/complete-sale', [
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
    ]);

    // Verify points earned
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'loyalty_points' => 50,
    ]);

    $this->assertDatabaseHas('loyalty_point_transactions', [
        'customer_id' => $customer->id,
        'transaction_type' => 'earned',
        'points_change' => 50,
    ]);

    // Second purchase - earn more points
    $this->post('/pos/complete-sale', [
        'customer_phone' => '+1234567890',
        'cart' => [
            [
                'variant_id' => $variant->id,
                'quantity' => 1,
                'price' => 75.00,
            ],
        ],
        'payment_method' => 'cash',
        'cash_received' => 75.00,
    ]);

    // Verify accumulated points
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'loyalty_points' => 125, // 50 + 75
    ]);

    // Redeem points for discount
    $this->post('/pos/complete-sale', [
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
        'redeem_points' => 25, // Redeem 25 points
    ]);

    // Verify points after redemption
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'loyalty_points' => 200, // 125 - 25 + 100
    ]);

    // Verify redemption transaction
    $this->assertDatabaseHas('loyalty_point_transactions', [
        'customer_id' => $customer->id,
        'transaction_type' => 'redeemed',
        'points_change' => -25,
    ]);

    // Verify earning transaction from last purchase
    $this->assertDatabaseHas('loyalty_point_transactions', [
        'customer_id' => $customer->id,
        'transaction_type' => 'earned',
        'points_change' => 100,
    ]);
});

it('handles loyalty points with multiple customers', function () {
    $customer1 = Customer::factory()->create([
        'phone' => '+1111111111',
        'loyalty_points' => 0,
    ]);

    $customer2 = Customer::factory()->create([
        'phone' => '+2222222222',
        'loyalty_points' => 0,
    ]);

    $variant = ProductVariant::factory()->create([
        'selling_price' => 30.00,
        'stock_quantity' => 20,
    ]);

    actingAsUser();

    // Customer 1 makes purchase
    $this->post('/pos/complete-sale', [
        'customer_phone' => '+1111111111',
        'cart' => [
            [
                'variant_id' => $variant->id,
                'quantity' => 1,
                'price' => 30.00,
            ],
        ],
        'payment_method' => 'cash',
        'cash_received' => 30.00,
    ]);

    // Customer 2 makes purchase
    $this->post('/pos/complete-sale', [
        'customer_phone' => '+2222222222',
        'cart' => [
            [
                'variant_id' => $variant->id,
                'quantity' => 1,
                'price' => 30.00,
            ],
        ],
        'payment_method' => 'cash',
        'cash_received' => 30.00,
    ]);

    // Verify each customer has their own points
    $this->assertDatabaseHas('customers', [
        'id' => $customer1->id,
        'loyalty_points' => 30,
    ]);

    $this->assertDatabaseHas('customers', [
        'id' => $customer2->id,
        'loyalty_points' => 30,
    ]);

    // Verify separate transaction records
    $customer1Transactions = LoyaltyPointTransaction::where('customer_id', $customer1->id)->count();
    $customer2Transactions = LoyaltyPointTransaction::where('customer_id', $customer2->id)->count();

    expect($customer1Transactions)->toBe(1);
    expect($customer2Transactions)->toBe(1);
});

it('handles loyalty points with discounts and tax', function () {
    $customer = Customer::factory()->create([
        'phone' => '+1234567890',
        'loyalty_points' => 0,
    ]);

    $variant = ProductVariant::factory()->create([
        'selling_price' => 100.00,
        'stock_quantity' => 10,
    ]);

    actingAsUser();

    // Purchase with discount and tax
    $this->post('/pos/complete-sale', [
        'customer_phone' => '+1234567890',
        'cart' => [
            [
                'variant_id' => $variant->id,
                'quantity' => 1,
                'price' => 100.00,
            ],
        ],
        'payment_method' => 'cash',
        'cash_received' => 90.00,
        'discount_amount' => 15.00,
        'tax_rate' => 8.5,
    ]);

    // Calculate expected points: (100 + 8.50 - 15.00) = 93.50, floor = 93
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'loyalty_points' => 93,
    ]);

    $this->assertDatabaseHas('loyalty_point_transactions', [
        'customer_id' => $customer->id,
        'transaction_type' => 'earned',
        'points_change' => 93,
    ]);
});

it('prevents negative loyalty points balance', function () {
    $customer = Customer::factory()->create([
        'phone' => '+1234567890',
        'loyalty_points' => 10,
    ]);

    // Attempt to redeem more points than available
    $result = $customer->redeemLoyaltyPoints(20);

    expect($result)->toBeFalse();

    // Verify points remain unchanged
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'loyalty_points' => 10,
    ]);

    // Verify no transaction was created
    $transactions = LoyaltyPointTransaction::where('customer_id', $customer->id)->count();
    expect($transactions)->toBe(0);
});

it('tracks loyalty points transaction history accurately', function () {
    $customer = Customer::factory()->create([
        'phone' => '+1234567890',
        'loyalty_points' => 0,
    ]);

    $variant = ProductVariant::factory()->create([
        'selling_price' => 25.00,
        'stock_quantity' => 20,
    ]);

    actingAsUser();

    // Multiple transactions
    $transactions = [
        ['type' => 'earn', 'amount' => 25.00, 'expected_points' => 25],
        ['type' => 'earn', 'amount' => 50.00, 'expected_points' => 75],
        ['type' => 'redeem', 'points' => 10, 'expected_points' => 65],
        ['type' => 'earn', 'amount' => 35.00, 'expected_points' => 100],
        ['type' => 'redeem', 'points' => 25, 'expected_points' => 75],
    ];

    foreach ($transactions as $transaction) {
        if ($transaction['type'] === 'earn') {
            $this->post('/pos/complete-sale', [
                'customer_phone' => '+1234567890',
                'cart' => [
                    [
                        'variant_id' => $variant->id,
                        'quantity' => 1,
                        'price' => $transaction['amount'],
                    ],
                ],
                'payment_method' => 'cash',
                'cash_received' => $transaction['amount'],
            ]);
        } else {
            $customer->refresh();
            $customer->redeemLoyaltyPoints($transaction['points']);
        }
    }

    // Verify final balance
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'loyalty_points' => 75,
    ]);

    // Verify transaction count (3 earning + 2 redeeming)
    $transactionCount = LoyaltyPointTransaction::where('customer_id', $customer->id)->count();
    expect($transactionCount)->toBe(5);

    // Verify chronological order and amounts
    $allTransactions = LoyaltyPointTransaction::where('customer_id', $customer->id)
        ->orderBy('created_at')
        ->get();

    expect($allTransactions[0]->points_change)->toBe(25);
    expect($allTransactions[1]->points_change)->toBe(50);
    expect($allTransactions[2]->points_change)->toBe(-10);
    expect($allTransactions[3]->points_change)->toBe(35);
    expect($allTransactions[4]->points_change)->toBe(-25);
});

it('integrates loyalty points with customer management', function () {
    actingAsAdmin();

    $customer = Customer::factory()->create([
        'loyalty_points' => 100,
    ]);

    // Admin manually adjusts points
    $response = $this->post("/admin/customers/{$customer->id}/adjust-points", [
        'adjustment' => 50,
        'reason' => 'Bonus for loyalty',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'loyalty_points' => 150,
    ]);

    $this->assertDatabaseHas('loyalty_point_transactions', [
        'customer_id' => $customer->id,
        'transaction_type' => 'earned',
        'points_change' => 50,
        'description' => 'Bonus for loyalty',
    ]);

    // Admin redeems points
    $response = $this->post("/admin/customers/{$customer->id}/redeem-points", [
        'points' => 30,
        'reason' => 'Store credit redemption',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'loyalty_points' => 120,
    ]);

    $this->assertDatabaseHas('loyalty_point_transactions', [
        'customer_id' => $customer->id,
        'transaction_type' => 'redeemed',
        'points_change' => -30,
    ]);
});

it('handles loyalty points for bulk operations', function () {
    $customers = Customer::factory()->count(5)->create([
        'loyalty_points' => 0,
    ]);

    $variant = ProductVariant::factory()->create([
        'selling_price' => 20.00,
        'stock_quantity' => 50,
    ]);

    actingAsUser();

    // Each customer makes a purchase
    foreach ($customers as $customer) {
        $this->post('/pos/complete-sale', [
            'customer_phone' => $customer->phone,
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
    }

    // Verify all customers have points
    foreach ($customers as $customer) {
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'loyalty_points' => 20,
        ]);
    }

    // Verify transaction records
    $totalTransactions = LoyaltyPointTransaction::count();
    expect($totalTransactions)->toBe(5);

    $earnedTransactions = LoyaltyPointTransaction::where('transaction_type', 'earned')->count();
    expect($earnedTransactions)->toBe(5);
});

it('generates loyalty points reports and analytics', function () {
    actingAsAdmin();

    // Create test data
    $customers = Customer::factory()->count(10)->create();

    $variant = ProductVariant::factory()->create([
        'selling_price' => 25.00,
        'stock_quantity' => 100,
    ]);

    actingAsUser();

    // Generate transactions over time
    foreach ($customers as $index => $customer) {
        $purchaseAmount = (10 + $index) * 10; // 100, 110, 120, etc.

        $this->post('/pos/complete-sale', [
            'customer_phone' => $customer->phone,
            'cart' => [
                [
                    'variant_id' => $variant->id,
                    'quantity' => 1,
                    'price' => $purchaseAmount,
                ],
            ],
            'payment_method' => 'cash',
            'cash_received' => $purchaseAmount,
        ]);
    }

    // Generate loyalty points report
    $response = $this->get('/admin/reports/loyalty-points');

    $response->assertStatus(200);

    // Verify report contains expected data
    $response->assertSee('Top Customers');
    $response->assertSee('Points Earned');
    $response->assertSee('Points Redeemed');

    // Check individual customer data
    $topCustomer = Customer::orderBy('loyalty_points', 'desc')->first();
    $response->assertSee($topCustomer->name);
    $response->assertSee((string)$topCustomer->loyalty_points);
});

it('handles loyalty points expiration', function () {
    $customer = Customer::factory()->create([
        'loyalty_points' => 100,
    ]);

    // Simulate points earned 13 months ago
    LoyaltyPointTransaction::create([
        'customer_id' => $customer->id,
        'transaction_type' => 'earned',
        'points_change' => 50,
        'previous_points' => 0,
        'new_points' => 50,
        'created_at' => now()->subMonths(13),
    ]);

    // Simulate points earned 2 months ago
    LoyaltyPointTransaction::create([
        'customer_id' => $customer->id,
        'transaction_type' => 'earned',
        'points_change' => 50,
        'previous_points' => 50,
        'new_points' => 100,
        'created_at' => now()->subMonths(2),
    ]);

    // Run expiration process (assuming 12-month expiry)
    $this->artisan('loyalty:expire-points');

    // Verify expired points were deducted
    $customer->refresh();
    expect($customer->loyalty_points)->toBe(50); // Only recent points remain

    // Verify expiration transaction
    $this->assertDatabaseHas('loyalty_point_transactions', [
        'customer_id' => $customer->id,
        'transaction_type' => 'expired',
        'points_change' => -50,
    ]);
});

it('handles loyalty points transfer between customers', function () {
    $customer1 = Customer::factory()->create([
        'loyalty_points' => 200,
    ]);

    $customer2 = Customer::factory()->create([
        'loyalty_points' => 50,
    ]);

    actingAsAdmin();

    // Transfer points from customer1 to customer2
    $response = $this->post('/admin/loyalty-points/transfer', [
        'from_customer_id' => $customer1->id,
        'to_customer_id' => $customer2->id,
        'points' => 75,
        'reason' => 'Customer requested transfer',
    ]);

    $response->assertRedirect();

    // Verify points were transferred
    $this->assertDatabaseHas('customers', [
        'id' => $customer1->id,
        'loyalty_points' => 125, // 200 - 75
    ]);

    $this->assertDatabaseHas('customers', [
        'id' => $customer2->id,
        'loyalty_points' => 125, // 50 + 75
    ]);

    // Verify transfer transactions
    $this->assertDatabaseHas('loyalty_point_transactions', [
        'customer_id' => $customer1->id,
        'transaction_type' => 'transferred_out',
        'points_change' => -75,
    ]);

    $this->assertDatabaseHas('loyalty_point_transactions', [
        'customer_id' => $customer2->id,
        'transaction_type' => 'transferred_in',
        'points_change' => 75,
    ]);
});

it('manages loyalty points tiers and rewards', function () {
    $customer = Customer::factory()->create([
        'loyalty_points' => 0,
    ]);

    $variant = ProductVariant::factory()->create([
        'selling_price' => 10.00,
        'stock_quantity' => 100,
    ]);

    actingAsUser();

    // Build up points to reach different tiers
    $purchases = [50, 100, 200, 500]; // Different purchase amounts

    foreach ($purchases as $amount) {
        $this->post('/pos/complete-sale', [
            'customer_phone' => $customer->phone,
            'cart' => [
                [
                    'variant_id' => $variant->id,
                    'quantity' => $amount / 10, // Adjust quantity for amount
                    'price' => 10.00,
                ],
            ],
            'payment_method' => 'cash',
            'cash_received' => $amount,
        ]);

        // Check for tier upgrades (assuming tier system exists)
        $customer->refresh();

        if ($customer->loyalty_points >= 1000) {
            expect($customer->loyalty_tier)->toBe('platinum');
        } elseif ($customer->loyalty_points >= 500) {
            expect($customer->loyalty_tier)->toBe('gold');
        } elseif ($customer->loyalty_points >= 100) {
            expect($customer->loyalty_tier)->toBe('silver');
        }
    }

    // Verify final points accumulation
    $expectedPoints = array_sum($purchases); // 50 + 100 + 200 + 500 = 850
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'loyalty_points' => $expectedPoints,
    ]);
});
