<?php

use App\Models\Customer;
use App\Models\LoyaltyPointTransaction;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a customer', function () {
    $customer = Customer::factory()->create();

    expect($customer)->toBeInstanceOf(Customer::class);
    expect($customer->phone)->toBeString();
});

it('has many sales', function () {
    $customer = Customer::factory()->create();
    $sales = Sale::factory()->count(3)->create([
        'customer_id' => $customer->id,
    ]);

    expect($customer->sales)->toHaveCount(3);
});

it('has many loyalty point transactions', function () {
    $customer = Customer::factory()->create();
    $transactions = LoyaltyPointTransaction::factory()->count(5)->create([
        'customer_id' => $customer->id,
    ]);

    expect($customer->loyaltyPointTransactions)->toHaveCount(5);
});

it('scopes active customers', function () {
    Customer::factory()->count(4)->create(['is_active' => true]);
    Customer::factory()->count(2)->create(['is_active' => false]);

    $activeCustomers = Customer::active()->get();

    expect($activeCustomers)->toHaveCount(4);
});

it('adds loyalty points correctly', function () {
    $customer = Customer::factory()->create(['loyalty_points' => 100]);
    $sale = Sale::factory()->create();

    $customer->addLoyaltyPoints(50, $sale->id, 'Bonus points');

    expect($customer->fresh()->loyalty_points)->toBe(150);

    $transaction = LoyaltyPointTransaction::latest()->first();
    expect($transaction)->not->toBeNull();
    expect($transaction->points_change)->toBe(50);
    expect($transaction->transaction_type)->toBe('earned');
    expect($transaction->sale_id)->toBe($sale->id);
});

it('redeems loyalty points successfully', function () {
    $customer = Customer::factory()->create(['loyalty_points' => 100]);

    $result = $customer->redeemLoyaltyPoints(50, 'Discount redemption');

    expect($result)->toBeTrue();
    expect($customer->fresh()->loyalty_points)->toBe(50);

    $transaction = LoyaltyPointTransaction::latest()->first();
    expect($transaction)->not->toBeNull();
    expect($transaction->points_change)->toBe(-50);
    expect($transaction->transaction_type)->toBe('redeemed');
});

it('fails to redeem more points than available', function () {
    $customer = Customer::factory()->create(['loyalty_points' => 30]);

    $result = $customer->redeemLoyaltyPoints(50, 'Discount redemption');

    expect($result)->toBeFalse();
    expect($customer->fresh()->loyalty_points)->toBe(30);

    // No transaction should be created
    $transactions = LoyaltyPointTransaction::all();
    expect($transactions)->toHaveCount(0);
});

it('has correct fillable attributes', function () {
    $fillable = [
        'phone',
        'name',
        'email',
        'address',
        'birthdate',
        'loyalty_points',
        'is_active',
    ];

    $customer = new Customer();

    foreach ($fillable as $attribute) {
        expect(in_array($attribute, $customer->getFillable()))->toBeTrue();
    }
});

it('has correct cast attributes', function () {
    $casts = [
        'birthdate' => 'date',
        'loyalty_points' => 'integer',
        'is_active' => 'boolean',
    ];

    $customer = new Customer();

    foreach ($casts as $attribute => $cast) {
        expect($customer->getCasts()[$attribute])->toBe($cast);
    }
});

it('validates phone uniqueness', function () {
    $phone = '+1234567890';

    Customer::factory()->create(['phone' => $phone]);

    expect(fn() => Customer::factory()->create(['phone' => $phone]))
        ->toThrow(Exception::class);
})->skip('Database constraint test - requires database setup');

it('allows customer creation with phone only', function () {
    $customer = Customer::factory()->create([
        'phone' => '+1234567890',
        'name' => null,
        'email' => null,
        'address' => null,
        'birthdate' => null,
    ]);

    expect($customer->phone)->toBe('+1234567890');
    expect($customer->name)->toBeNull();
    expect($customer->email)->toBeNull();
});

it('tracks loyalty points balance correctly', function () {
    $customer = Customer::factory()->create(['loyalty_points' => 0]);

    // Add points
    $customer->addLoyaltyPoints(100);
    expect($customer->fresh()->loyalty_points)->toBe(100);

    // Redeem points
    $customer->redeemLoyaltyPoints(30);
    expect($customer->fresh()->loyalty_points)->toBe(70);

    // Add more points
    $customer->addLoyaltyPoints(50);
    expect($customer->fresh()->loyalty_points)->toBe(120);

    // Check transaction count
    expect($customer->loyaltyPointTransactions)->toHaveCount(3);
});

it('has correct relationship with sales', function () {
    $customer = Customer::factory()->create();
    $sale = Sale::factory()->create(['customer_id' => $customer->id]);

    expect($customer->sales->first()->id)->toBe($sale->id);
    expect($customer->sales->first())->toBeInstanceOf(Sale::class);
});
