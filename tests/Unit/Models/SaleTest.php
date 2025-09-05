<?php

use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a sale', function () {
    $sale = Sale::factory()->create();

    expect($sale)->toBeInstanceOf(Sale::class);
    expect($sale->sale_number)->toBeString();
    expect($sale->total_amount)->toBeFloat();
});

it('belongs to a customer', function () {
    $customer = Customer::factory()->create();
    $sale = Sale::factory()->create(['customer_id' => $customer->id]);

    expect($sale->customer)->toBeInstanceOf(Customer::class);
    expect($sale->customer->id)->toBe($customer->id);
});

it('belongs to a user', function () {
    $user = User::factory()->create();
    $sale = Sale::factory()->create(['user_id' => $user->id]);

    expect($sale->user)->toBeInstanceOf(User::class);
    expect($sale->user->id)->toBe($user->id);
});

it('has many sale items', function () {
    $sale = Sale::factory()->create();
    $saleItems = SaleItem::factory()->count(3)->create([
        'sale_id' => $sale->id,
    ]);

    expect($sale->saleItems)->toHaveCount(3);
});

it('has many loyalty point transactions', function () {
    $sale = Sale::factory()->create();
    // This would normally be tested with LoyaltyPointTransaction factory
    // but we'll test the relationship structure
    expect($sale->loyaltyPointTransactions())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

it('generates unique sale number', function () {
    $sale1 = Sale::factory()->create();
    $sale2 = Sale::factory()->create();

    expect($sale1->sale_number)->not->toBe($sale2->sale_number);
    expect($sale1->sale_number)->toMatch('/^SALE\d{8}\d{4}$/');
});

it('auto generates sale number on creation', function () {
    $sale = Sale::factory()->create(['sale_number' => null]);

    expect($sale->sale_number)->toBeString();
    expect($sale->sale_number)->toMatch('/^SALE\d{8}\d{4}$/');
});

it('has correct fillable attributes', function () {
    $fillable = [
        'sale_number',
        'customer_id',
        'user_id',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'points_earned',
        'payment_method',
        'payment_status',
        'notes',
        'sale_date',
    ];

    $sale = new Sale();

    foreach ($fillable as $attribute) {
        expect(in_array($attribute, $sale->getFillable()))->toBeTrue();
    }
});

it('has correct cast attributes', function () {
    $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'points_earned' => 'integer',
        'sale_date' => 'datetime',
    ];

    $sale = new Sale();

    foreach ($casts as $attribute => $cast) {
        expect($sale->getCasts()[$attribute])->toBe($cast);
    }
});

it('calculates total amount correctly', function () {
    $sale = Sale::factory()->create([
        'subtotal' => 100.00,
        'tax_amount' => 8.50,
        'discount_amount' => 5.00,
    ]);

    expect($sale->total_amount)->toBe(103.50); // 100 + 8.50 - 5.00
});

it('handles zero discount correctly', function () {
    $sale = Sale::factory()->create([
        'subtotal' => 50.00,
        'tax_amount' => 4.25,
        'discount_amount' => 0.00,
    ]);

    expect($sale->total_amount)->toBe(54.25);
});

it('handles zero tax correctly', function () {
    $sale = Sale::factory()->create([
        'subtotal' => 75.00,
        'tax_amount' => 0.00,
        'discount_amount' => 10.00,
    ]);

    expect($sale->total_amount)->toBe(65.00);
});

it('can have null customer for walk-in sales', function () {
    $sale = Sale::factory()->create(['customer_id' => null]);

    expect($sale->customer_id)->toBeNull();
    expect($sale->customer)->toBeNull();
});

it('validates payment method', function () {
    $validMethods = ['cash', 'card', 'digital'];

    foreach ($validMethods as $method) {
        $sale = Sale::factory()->create(['payment_method' => $method]);
        expect($sale->payment_method)->toBe($method);
    }
});

it('validates payment status', function () {
    $validStatuses = ['completed', 'pending', 'refunded'];

    foreach ($validStatuses as $status) {
        $sale = Sale::factory()->create(['payment_status' => $status]);
        expect($sale->payment_status)->toBe($status);
    }
});

it('tracks points earned', function () {
    $sale = Sale::factory()->create(['points_earned' => 25]);

    expect($sale->points_earned)->toBe(25);
});

it('has sale date', function () {
    $sale = Sale::factory()->create();

    expect($sale->sale_date)->toBeInstanceOf(\Carbon\Carbon::class);
});

it('can create sale with notes', function () {
    $notes = 'Customer requested gift wrapping';
    $sale = Sale::factory()->create(['notes' => $notes]);

    expect($sale->notes)->toBe($notes);
});
