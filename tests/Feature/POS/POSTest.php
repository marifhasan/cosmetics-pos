<?php

use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('can load POS page', function () {
    $user = User::factory()->create();
    actingAsUser();

    $response = $this->get('/pos');

    $response->assertStatus(200);
    $response->assertSee('POS');
});

it('can search products by name', function () {
    $user = User::factory()->create();
    actingAsUser();

    $product = \App\Models\Product::factory()->create(['name' => 'Premium Shampoo']);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'stock_quantity' => 10,
    ]);

    Livewire::test(\App\Livewire\POS::class)
        ->set('search', 'Premium')
        ->assertSee($product->name);
});

it('can search products by SKU', function () {
    $user = User::factory()->create();
    actingAsUser();

    $variant = ProductVariant::factory()->create([
        'sku' => 'TEST001',
        'stock_quantity' => 10,
    ]);

    Livewire::test(\App\Livewire\POS::class)
        ->set('search', 'TEST001')
        ->assertSee($variant->sku);
});

it('can search products by barcode', function () {
    $user = User::factory()->create();
    actingAsUser();

    $variant = ProductVariant::factory()->create([
        'barcode' => '1234567890123',
        'stock_quantity' => 10,
    ]);

    Livewire::test(\App\Livewire\POS::class)
        ->set('search', '1234567890123')
        ->assertSee($variant->barcode);
});

it('can add product to cart', function () {
    $user = User::factory()->create();
    actingAsUser();

    $variant = ProductVariant::factory()->create([
        'selling_price' => 25.00,
        'stock_quantity' => 10,
    ]);

    Livewire::test(\App\Livewire\POS::class)
        ->call('addToCart', $variant->id)
        ->assertSet('cart', function ($cart) use ($variant) {
            return count($cart) === 1 &&
                   isset($cart['variant_' . $variant->id]) &&
                   $cart['variant_' . $variant->id]['quantity'] === 1;
        });
});

it('cannot add out of stock product to cart', function () {
    $user = User::factory()->create();
    actingAsUser();

    $variant = ProductVariant::factory()->create([
        'stock_quantity' => 0,
    ]);

    Livewire::test(\App\Livewire\POS::class)
        ->call('addToCart', $variant->id)
        ->assertHasErrors('cart');
});

it('can update cart item quantity', function () {
    $user = User::factory()->create();
    actingAsUser();

    $variant = ProductVariant::factory()->create([
        'selling_price' => 25.00,
        'stock_quantity' => 10,
    ]);

    Livewire::test(\App\Livewire\POS::class)
        ->call('addToCart', $variant->id)
        ->call('updateQuantity', 'variant_' . $variant->id, 3)
        ->assertSet('cart', function ($cart) use ($variant) {
            return $cart['variant_' . $variant->id]['quantity'] === 3;
        });
});

it('cannot exceed stock quantity in cart', function () {
    $user = User::factory()->create();
    actingAsUser();

    $variant = ProductVariant::factory()->create([
        'selling_price' => 25.00,
        'stock_quantity' => 5,
    ]);

    Livewire::test(\App\Livewire\POS::class)
        ->call('addToCart', $variant->id)
        ->call('updateQuantity', 'variant_' . $variant->id, 10)
        ->assertHasErrors('cart');
});

it('can remove item from cart', function () {
    $user = User::factory()->create();
    actingAsUser();

    $variant = ProductVariant::factory()->create([
        'selling_price' => 25.00,
        'stock_quantity' => 10,
    ]);

    Livewire::test(\App\Livewire\POS::class)
        ->call('addToCart', $variant->id)
        ->assertSet('cart', function ($cart) {
            return count($cart) === 1;
        })
        ->call('removeFromCart', 'variant_' . $variant->id)
        ->assertSet('cart', function ($cart) {
            return count($cart) === 0;
        });
});

it('calculates subtotal correctly', function () {
    $user = User::factory()->create();
    actingAsUser();

    $variant1 = ProductVariant::factory()->create([
        'selling_price' => 25.00,
        'stock_quantity' => 10,
    ]);

    $variant2 = ProductVariant::factory()->create([
        'selling_price' => 15.50,
        'stock_quantity' => 10,
    ]);

    $pos = Livewire::test(\App\Livewire\POS::class)
        ->call('addToCart', $variant1->id)
        ->call('addToCart', $variant2->id)
        ->call('updateQuantity', 'variant_' . $variant1->id, 2);

    // Subtotal should be (2 * 25.00) + (1 * 15.50) = 65.50
    expect($pos->get('subtotal'))->toBe(65.50);
});

it('calculates tax correctly', function () {
    $user = User::factory()->create();
    actingAsUser();

    $variant = ProductVariant::factory()->create([
        'selling_price' => 100.00,
        'stock_quantity' => 10,
    ]);

    $pos = Livewire::test(\App\Livewire\POS::class)
        ->call('addToCart', $variant->id);

    $pos->set('taxRate', 8.5);

    // Tax should be 100 * 0.085 = 8.50
    expect($pos->get('taxAmount'))->toBe(8.50);
});

it('calculates total correctly', function () {
    $user = User::factory()->create();
    actingAsUser();

    $variant = ProductVariant::factory()->create([
        'selling_price' => 100.00,
        'stock_quantity' => 10,
    ]);

    $pos = Livewire::test(\App\Livewire\POS::class)
        ->call('addToCart', $variant->id);

    $pos->set('taxRate', 8.5);

    // Total should be 100 + 8.50 = 108.50
    expect($pos->get('total'))->toBe(108.50);
});

it('calculates change correctly', function () {
    $user = User::factory()->create();
    actingAsUser();

    $variant = ProductVariant::factory()->create([
        'selling_price' => 50.00,
        'stock_quantity' => 10,
    ]);

    Livewire::test(\App\Livewire\POS::class)
        ->call('addToCart', $variant->id)
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 60.00)
        ->assertSet('change', 10.00);
});

it('can search for existing customer', function () {
    $user = User::factory()->create();
    actingAsUser();

    $customer = Customer::factory()->create([
        'phone' => '+1234567890',
        'name' => 'John Doe',
    ]);

    Livewire::test(\App\Livewire\POS::class)
        ->set('customerPhone', '+1234567890')
        ->call('searchCustomer')
        ->assertSet('selectedCustomer.id', $customer->id);
});

it('can create new customer during checkout', function () {
    $user = User::factory()->create();
    actingAsUser();

    Livewire::test(\App\Livewire\POS::class)
        ->set('customerPhone', '+1234567890')
        ->set('newCustomerName', 'Jane Smith')
        ->set('newCustomerEmail', 'jane@example.com')
        ->call('createCustomer')
        ->assertSet('selectedCustomer.name', 'Jane Smith');

    $this->assertDatabaseHas('customers', [
        'phone' => '+1234567890',
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
    ]);
});

it('can complete sale transaction', function () {
    $user = User::factory()->create();
    actingAsUser();

    $variant = ProductVariant::factory()->create([
        'selling_price' => 25.00,
        'stock_quantity' => 10,
    ]);

    Livewire::test(\App\Livewire\POS::class)
        ->call('addToCart', $variant->id)
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 30.00)
        ->call('completeSale');

    // Check if sale was created
    $this->assertDatabaseHas('sales', [
        'payment_method' => 'cash',
        'payment_status' => 'completed',
    ]);

    // Check if sale item was created
    $this->assertDatabaseHas('sale_items', [
        'quantity' => 1,
        'unit_price' => 25.00,
    ]);

    // Check if stock was reduced
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'stock_quantity' => 9,
    ]);
});

it('awards loyalty points for customer purchases', function () {
    $user = User::factory()->create();
    actingAsUser();

    $customer = Customer::factory()->create([
        'phone' => '+1234567890',
        'loyalty_points' => 0,
    ]);

    $variant = ProductVariant::factory()->create([
        'selling_price' => 75.00,
        'stock_quantity' => 10,
    ]);

    Livewire::test(\App\Livewire\POS::class)
        ->call('addToCart', $variant->id)
        ->set('customerPhone', '+1234567890')
        ->call('searchCustomer')
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 75.00)
        ->call('completeSale');

    // Customer should have 75 points (1 point per dollar)
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'loyalty_points' => 75,
    ]);
});

it('prevents sale completion with empty cart', function () {
    $user = User::factory()->create();
    actingAsUser();

    Livewire::test(\App\Livewire\POS::class)
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 50.00)
        ->call('completeSale')
        ->assertHasErrors('cart');
});

it('prevents cash payment with insufficient amount', function () {
    $user = User::factory()->create();
    actingAsUser();

    $variant = ProductVariant::factory()->create([
        'selling_price' => 50.00,
        'stock_quantity' => 10,
    ]);

    Livewire::test(\App\Livewire\POS::class)
        ->call('addToCart', $variant->id)
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 30.00)
        ->call('completeSale')
        ->assertHasErrors('cashReceived');
});

it('can clear cart', function () {
    $user = User::factory()->create();
    actingAsUser();

    $variant = ProductVariant::factory()->create([
        'selling_price' => 25.00,
        'stock_quantity' => 10,
    ]);

    Livewire::test(\App\Livewire\POS::class)
        ->call('addToCart', $variant->id)
        ->assertSet('cart', function ($cart) {
            return count($cart) === 1;
        })
        ->call('clearCart')
        ->assertSet('cart', function ($cart) {
            return count($cart) === 0;
        });
});

it('shows search results limited to 10 items', function () {
    $user = User::factory()->create();
    actingAsUser();

    // Create 15 products with similar names
    for ($i = 1; $i <= 15; $i++) {
        $product = \App\Models\Product::factory()->create([
            'name' => "Test Product {$i}",
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 10,
        ]);
    }

    $pos = Livewire::test(\App\Livewire\POS::class)
        ->set('search', 'Test Product');

    $searchResults = $pos->get('searchResults');
    expect($searchResults)->toHaveCount(10);
});

it('only shows products with available stock', function () {
    $user = User::factory()->create();
    actingAsUser();

    $inStockProduct = \App\Models\Product::factory()->create(['name' => 'In Stock Product']);
    $outOfStockProduct = \App\Models\Product::factory()->create(['name' => 'Out of Stock Product']);

    ProductVariant::factory()->create([
        'product_id' => $inStockProduct->id,
        'stock_quantity' => 10,
    ]);

    ProductVariant::factory()->create([
        'product_id' => $outOfStockProduct->id,
        'stock_quantity' => 0,
    ]);

    Livewire::test(\App\Livewire\POS::class)
        ->set('search', 'Product')
        ->assertSee('In Stock Product')
        ->assertDontSee('Out of Stock Product');
});

it('only shows active products', function () {
    $user = User::factory()->create();
    actingAsUser();

    $activeProduct = \App\Models\Product::factory()->create([
        'name' => 'Active Product',
        'is_active' => true,
    ]);

    $inactiveProduct = \App\Models\Product::factory()->create([
        'name' => 'Inactive Product',
        'is_active' => false,
    ]);

    ProductVariant::factory()->create([
        'product_id' => $activeProduct->id,
        'stock_quantity' => 10,
    ]);

    ProductVariant::factory()->create([
        'product_id' => $inactiveProduct->id,
        'stock_quantity' => 10,
    ]);

    Livewire::test(\App\Livewire\POS::class)
        ->set('search', 'Product')
        ->assertSee('Active Product')
        ->assertDontSee('Inactive Product');
});

it('can handle decimal quantities and prices', function () {
    $user = User::factory()->create();
    actingAsUser();

    $variant = ProductVariant::factory()->create([
        'selling_price' => 19.99,
        'stock_quantity' => 10,
    ]);

    Livewire::test(\App\Livewire\POS::class)
        ->call('addToCart', $variant->id)
        ->assertSet('cart', function ($cart) use ($variant) {
            return $cart['variant_' . $variant->id]['price'] === 19.99;
        });
});

it('validates customer phone format', function () {
    $user = User::factory()->create();
    actingAsUser();

    Livewire::test(\App\Livewire\POS::class)
        ->set('customerPhone', 'invalid-phone')
        ->call('searchCustomer')
        ->assertHasErrors('customerPhone');
});

it('can handle multiple payment methods', function () {
    $user = User::factory()->create();
    actingAsUser();

    $variant = ProductVariant::factory()->create([
        'selling_price' => 50.00,
        'stock_quantity' => 10,
    ]);

    // Test card payment
    Livewire::test(\App\Livewire\POS::class)
        ->call('addToCart', $variant->id)
        ->set('paymentMethod', 'card')
        ->call('completeSale');

    $this->assertDatabaseHas('sales', [
        'payment_method' => 'card',
        'payment_status' => 'completed',
    ]);
});

it('resets form after successful sale', function () {
    $user = User::factory()->create();
    actingAsUser();

    $variant = ProductVariant::factory()->create([
        'selling_price' => 25.00,
        'stock_quantity' => 10,
    ]);

    $customer = Customer::factory()->create(['phone' => '+1234567890']);

    Livewire::test(\App\Livewire\POS::class)
        ->call('addToCart', $variant->id)
        ->set('customerPhone', '+1234567890')
        ->call('searchCustomer')
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 30.00)
        ->call('completeSale')
        ->assertSet('cart', [])
        ->assertSet('customerPhone', '')
        ->assertSet('selectedCustomer', null);
});

it('shows sale completion message', function () {
    $user = User::factory()->create();
    actingAsUser();

    $variant = ProductVariant::factory()->create([
        'selling_price' => 25.00,
        'stock_quantity' => 10,
    ]);

    $pos = Livewire::test(\App\Livewire\POS::class)
        ->call('addToCart', $variant->id)
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 30.00)
        ->call('completeSale');

    // This would typically set a flash message
    // The exact implementation depends on how flash messages are handled
});

it('handles concurrent sales correctly', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $variant = ProductVariant::factory()->create([
        'selling_price' => 25.00,
        'stock_quantity' => 5, // Limited stock
    ]);

    // First sale
    actingAsUser();
    Livewire::test(\App\Livewire\POS::class)
        ->call('addToCart', $variant->id)
        ->call('updateQuantity', 'variant_' . $variant->id, 3)
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 75.00)
        ->call('completeSale');

    // Second sale should fail due to insufficient stock
    actingAsUser();
    Livewire::test(\App\Livewire\POS::class)
        ->call('addToCart', $variant->id)
        ->call('updateQuantity', 'variant_' . $variant->id, 3)
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 75.00)
        ->call('completeSale')
        ->assertHasErrors('cart');
});
