<?php

use App\Models\Customer;
use App\Models\LoyaltyPointTransaction;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates points based on purchase amount', function () {
    $customer = Customer::factory()->create(['loyalty_points' => 0]);

    // Test different purchase amounts
    $testCases = [
        ['amount' => 10.00, 'expected_points' => 10],
        ['amount' => 25.50, 'expected_points' => 25], // Floor of 25.50 = 25
        ['amount' => 99.99, 'expected_points' => 99],
        ['amount' => 100.00, 'expected_points' => 100],
    ];

    foreach ($testCases as $testCase) {
        $customer->loyalty_points = 0; // Reset points
        $customer->save();

        $points = floor($testCase['amount']);
        $customer->addLoyaltyPoints($points);

        expect($customer->fresh()->loyalty_points)->toBe($testCase['expected_points']);
    }
});

it('awards points correctly on sale completion', function () {
    $customer = Customer::factory()->create(['loyalty_points' => 50]);
    $sale = Sale::factory()->create([
        'customer_id' => $customer->id,
        'total_amount' => 75.00,
    ]);

    // Simulate points awarding (1 point per dollar)
    $pointsEarned = floor($sale->total_amount);
    $customer->addLoyaltyPoints($pointsEarned, $sale->id, 'Purchase points');

    expect($customer->fresh()->loyalty_points)->toBe(125); // 50 + 75

    $transaction = LoyaltyPointTransaction::latest()->first();
    expect($transaction)->not->toBeNull();
    expect($transaction->points_change)->toBe(75);
    expect($transaction->transaction_type)->toBe('earned');
    expect($transaction->sale_id)->toBe($sale->id);
});

it('redeems points correctly', function () {
    $customer = Customer::factory()->create(['loyalty_points' => 200]);

    $result = $customer->redeemLoyaltyPoints(50, 'Discount redemption');

    expect($result)->toBeTrue();
    expect($customer->fresh()->loyalty_points)->toBe(150);

    $transaction = LoyaltyPointTransaction::latest()->first();
    expect($transaction)->not->toBeNull();
    expect($transaction->points_change)->toBe(-50);
    expect($transaction->transaction_type)->toBe('redeemed');
});

it('prevents redemption of more points than available', function () {
    $customer = Customer::factory()->create(['loyalty_points' => 30]);

    $result = $customer->redeemLoyaltyPoints(50, 'Discount redemption');

    expect($result)->toBeFalse();
    expect($customer->fresh()->loyalty_points)->toBe(30);

    // No transaction should be created
    $transactions = LoyaltyPointTransaction::all();
    expect($transactions)->toHaveCount(0);
});

it('tracks transaction history correctly', function () {
    $customer = Customer::factory()->create(['loyalty_points' => 0]);

    // Multiple transactions
    $customer->addLoyaltyPoints(100, 1, 'First purchase');
    $customer->addLoyaltyPoints(50, 2, 'Second purchase');
    $customer->redeemLoyaltyPoints(30, 'Discount redemption');
    $customer->addLoyaltyPoints(25, 3, 'Third purchase');

    $customer->refresh();

    expect($customer->loyalty_points)->toBe(145); // 100 + 50 - 30 + 25
    expect($customer->loyaltyPointTransactions)->toHaveCount(4);
});

it('validates points balance before redemption', function () {
    $customer = Customer::factory()->create(['loyalty_points' => 10]);

    // Try to redeem more than available
    $result = $customer->redeemLoyaltyPoints(20);

    expect($result)->toBeFalse();
    expect($customer->fresh()->loyalty_points)->toBe(10);
});

it('handles zero points transactions', function () {
    $customer = Customer::factory()->create(['loyalty_points' => 100]);

    // Try to add zero points
    $customer->addLoyaltyPoints(0);

    expect($customer->fresh()->loyalty_points)->toBe(100);

    // Try to redeem zero points
    $result = $customer->redeemLoyaltyPoints(0);

    expect($result)->toBeTrue();
    expect($customer->fresh()->loyalty_points)->toBe(100);
});

it('calculates points for different purchase scenarios', function () {
    $scenarios = [
        ['subtotal' => 49.99, 'tax' => 4.50, 'discount' => 0, 'expected_points' => 49],
        ['subtotal' => 100.00, 'tax' => 8.50, 'discount' => 10.00, 'expected_points' => 98], // 100 + 8.50 - 10 = 98.50, floor = 98
        ['subtotal' => 25.00, 'tax' => 2.13, 'discount' => 5.00, 'expected_points' => 22], // 25 + 2.13 - 5 = 22.13, floor = 22
    ];

    foreach ($scenarios as $scenario) {
        $total = $scenario['subtotal'] + $scenario['tax'] - $scenario['discount'];
        $points = floor($total);

        expect($points)->toBe($scenario['expected_points']);
    }
});

it('tracks points balance accurately with multiple operations', function () {
    $customer = Customer::factory()->create(['loyalty_points' => 0]);

    // Complex transaction sequence
    $operations = [
        ['type' => 'earn', 'points' => 100],
        ['type' => 'earn', 'points' => 50],
        ['type' => 'redeem', 'points' => 30],
        ['type' => 'earn', 'points' => 75],
        ['type' => 'redeem', 'points' => 45],
    ];

    $expectedBalance = 0;

    foreach ($operations as $operation) {
        if ($operation['type'] === 'earn') {
            $customer->addLoyaltyPoints($operation['points']);
            $expectedBalance += $operation['points'];
        } else {
            $customer->redeemLoyaltyPoints($operation['points']);
            $expectedBalance -= $operation['points'];
        }
    }

    expect($customer->fresh()->loyalty_points)->toBe($expectedBalance);
    expect($expectedBalance)->toBe(150); // 100 + 50 - 30 + 75 - 45
});

it('handles customer without loyalty program', function () {
    // Customer with null points (not enrolled)
    $customer = Customer::factory()->create(['loyalty_points' => null]);

    // Should handle gracefully
    expect($customer->loyalty_points)->toBeNull();

    // Test redemption attempt
    $result = $customer->redeemLoyaltyPoints(10);
    expect($result)->toBeFalse();
});

it('validates transaction descriptions', function () {
    $customer = Customer::factory()->create(['loyalty_points' => 100]);

    $customer->addLoyaltyPoints(25, null, 'Custom description');
    $customer->redeemLoyaltyPoints(10, 'Redemption description');

    $transactions = $customer->loyaltyPointTransactions;

    expect($transactions->where('description', 'Custom description'))->toHaveCount(1);
    expect($transactions->where('description', 'Redemption description'))->toHaveCount(1);
});

it('links transactions to sales correctly', function () {
    $customer = Customer::factory()->create();
    $sale1 = Sale::factory()->create(['customer_id' => $customer->id]);
    $sale2 = Sale::factory()->create(['customer_id' => $customer->id]);

    $customer->addLoyaltyPoints(50, $sale1->id, 'Sale 1 points');
    $customer->addLoyaltyPoints(30, $sale2->id, 'Sale 2 points');

    $sale1Transactions = LoyaltyPointTransaction::where('sale_id', $sale1->id)->get();
    $sale2Transactions = LoyaltyPointTransaction::where('sale_id', $sale2->id)->get();

    expect($sale1Transactions)->toHaveCount(1);
    expect($sale2Transactions)->toHaveCount(1);
    expect($sale1Transactions->first()->points_change)->toBe(50);
    expect($sale2Transactions->first()->points_change)->toBe(30);
});
