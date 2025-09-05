<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

// Remove default example expectation
// expect()->extend('toBeOne', function () {
//     return $this->toBe(1);
// });

// Custom expectations for Cosmetics POS system

expect()->extend('toHaveStockStatus', function (string $status) {
    return $this->stock_status === $status;
});

expect()->extend('toBeLowStock', function () {
    return $this->stock_quantity <= $this->min_stock_level;
});

expect()->extend('toBeOutOfStock', function () {
    return $this->stock_quantity <= 0;
});

expect()->extend('toBeInStock', function () {
    return $this->stock_quantity > $this->min_stock_level;
});

expect()->extend('toHaveLoyaltyPoints', function (int $points) {
    return $this->loyalty_points === $points;
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

// Helper function to create authenticated user for testing
function actingAsUser()
{
    $user = \App\Models\User::factory()->create();
    return test()->actingAs($user);
}

// Helper function to create admin user
function actingAsAdmin()
{
    $user = \App\Models\User::factory()->create([
        'role' => 'admin',
        'status' => 'active'
    ]);
    return test()->actingAs($user);
}

// Helper function to create test product with variants
function createTestProductWithVariants($overrides = [])
{
    $product = \App\Models\Product::factory()->create($overrides);

    $variants = \App\Models\ProductVariant::factory()->count(3)->create([
        'product_id' => $product->id,
    ]);

    return [$product, $variants];
}

// Helper function to create test sale
function createTestSale($overrides = [])
{
    return \App\Models\Sale::factory()->create($overrides);
}
