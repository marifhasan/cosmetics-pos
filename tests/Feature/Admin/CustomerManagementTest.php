<?php

use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('admin can view customers list', function () {
    actingAsAdmin();

    Customer::factory()->count(5)->create();

    $response = $this->get('/admin/customers');

    $response->assertStatus(200);
    $response->assertSee('Customers');
});

it('admin can create a new customer', function () {
    actingAsAdmin();

    $customerData = [
        'phone' => '+1234567890',
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'address' => '123 Main St, City, State',
        'birthdate' => '1990-01-01',
        'is_active' => true,
    ];

    $response = $this->post('/admin/customers', $customerData);

    $response->assertRedirect();
    $this->assertDatabaseHas('customers', $customerData);
});

it('admin can update a customer', function () {
    actingAsAdmin();

    $customer = Customer::factory()->create();

    $updatedData = [
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'loyalty_points' => 150,
    ];

    $response = $this->put("/admin/customers/{$customer->id}", $updatedData);

    $response->assertRedirect();
    $this->assertDatabaseHas('customers', array_merge(['id' => $customer->id], $updatedData));
});

it('admin can delete a customer', function () {
    actingAsAdmin();

    $customer = Customer::factory()->create();

    $response = $this->delete("/admin/customers/{$customer->id}");

    $response->assertRedirect();
    $this->assertSoftDeleted($customer);
});

it('admin can view customer details', function () {
    actingAsAdmin();

    $customer = Customer::factory()->create();

    $response = $this->get("/admin/customers/{$customer->id}");

    $response->assertStatus(200);
    $response->assertSee($customer->name);
    $response->assertSee($customer->phone);
});

it('admin can view customer sales history', function () {
    actingAsAdmin();

    $customer = Customer::factory()->create();
    $sales = Sale::factory()->count(3)->create(['customer_id' => $customer->id]);

    $response = $this->get("/admin/customers/{$customer->id}/sales");

    $response->assertStatus(200);
    $response->assertSee($customer->name);

    foreach ($sales as $sale) {
        $response->assertSee($sale->sale_number);
    }
});

it('admin can view customer loyalty points history', function () {
    actingAsAdmin();

    $customer = Customer::factory()->create(['loyalty_points' => 100]);

    $response = $this->get("/admin/customers/{$customer->id}/loyalty-points");

    $response->assertStatus(200);
    $response->assertSee($customer->name);
    $response->assertSee('100'); // Loyalty points
});

it('admin can add loyalty points to customer', function () {
    actingAsAdmin();

    $customer = Customer::factory()->create(['loyalty_points' => 50]);

    $response = $this->post("/admin/customers/{$customer->id}/add-points", [
        'points' => 25,
        'description' => 'Bonus points for loyalty',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'loyalty_points' => 75,
    ]);
});

it('admin can redeem loyalty points for customer', function () {
    actingAsAdmin();

    $customer = Customer::factory()->create(['loyalty_points' => 100]);

    $response = $this->post("/admin/customers/{$customer->id}/redeem-points", [
        'points' => 50,
        'description' => 'Discount redemption',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'loyalty_points' => 50,
    ]);
});

it('admin cannot redeem more points than customer has', function () {
    actingAsAdmin();

    $customer = Customer::factory()->create(['loyalty_points' => 30]);

    $response = $this->post("/admin/customers/{$customer->id}/redeem-points", [
        'points' => 50,
        'description' => 'Discount redemption',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors();

    // Points should remain unchanged
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'loyalty_points' => 30,
    ]);
});

it('admin can activate and deactivate customers', function () {
    actingAsAdmin();

    $customer = Customer::factory()->create(['is_active' => true]);

    // Deactivate
    $response = $this->put("/admin/customers/{$customer->id}", ['is_active' => false]);
    $response->assertRedirect();
    $this->assertDatabaseHas('customers', ['id' => $customer->id, 'is_active' => false]);

    // Activate
    $response = $this->put("/admin/customers/{$customer->id}", ['is_active' => true]);
    $response->assertRedirect();
    $this->assertDatabaseHas('customers', ['id' => $customer->id, 'is_active' => true]);
});

it('admin can search customers by phone', function () {
    actingAsAdmin();

    Customer::factory()->create(['phone' => '+1234567890', 'name' => 'John Doe']);
    Customer::factory()->create(['phone' => '+0987654321', 'name' => 'Jane Smith']);

    $response = $this->get('/admin/customers?search=1234567890');

    $response->assertStatus(200);
    $response->assertSee('John Doe');
    $response->assertDontSee('Jane Smith');
});

it('admin can search customers by name', function () {
    actingAsAdmin();

    Customer::factory()->create(['name' => 'John Doe']);
    Customer::factory()->create(['name' => 'Jane Smith']);
    Customer::factory()->create(['name' => 'Bob Johnson']);

    $response = $this->get('/admin/customers?search=John');

    $response->assertStatus(200);
    $response->assertSee('John Doe');
    $response->assertSee('Bob Johnson');
    $response->assertDontSee('Jane Smith');
});

it('admin can search customers by email', function () {
    actingAsAdmin();

    Customer::factory()->create(['email' => 'john@example.com']);
    Customer::factory()->create(['email' => 'jane@example.com']);

    $response = $this->get('/admin/customers?search=john@example.com');

    $response->assertStatus(200);
    $response->assertSee('john@example.com');
    $response->assertDontSee('jane@example.com');
});

it('admin can filter customers by active status', function () {
    actingAsAdmin();

    Customer::factory()->count(3)->create(['is_active' => true]);
    Customer::factory()->count(2)->create(['is_active' => false]);

    $response = $this->get('/admin/customers?active=1');

    $response->assertStatus(200);
});

it('admin can view customer purchase statistics', function () {
    actingAsAdmin();

    $customer = Customer::factory()->create();

    // Create sales for the customer
    Sale::factory()->count(5)->create([
        'customer_id' => $customer->id,
        'total_amount' => 50.00,
    ]);

    $response = $this->get("/admin/customers/{$customer->id}/statistics");

    $response->assertStatus(200);
    $response->assertSee('5'); // Number of purchases
    $response->assertSee('250.00'); // Total spent (5 * 50.00)
});

it('admin can export customers data', function () {
    actingAsAdmin();

    Customer::factory()->count(5)->create();

    $response = $this->get('/admin/customers/export');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/csv');
});

it('admin can import customers data', function () {
    actingAsAdmin();

    $csvContent = "phone,name,email,is_active\n+1234567890,John Doe,john@example.com,1";
    $csvFile = UploadedFile::fake()->createWithContent('customers.csv', $csvContent);

    $response = $this->post('/admin/customers/import', [
        'file' => $csvFile,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('customers', [
        'phone' => '+1234567890',
        'name' => 'John Doe',
    ]);
});

it('admin cannot create customer with invalid phone', function () {
    actingAsAdmin();

    $invalidData = [
        'phone' => 'invalid-phone',
        'name' => 'John Doe',
    ];

    $response = $this->post('/admin/customers', $invalidData);

    $response->assertRedirect();
    $response->assertSessionHasErrors('phone');
});

it('admin cannot create customer with duplicate phone', function () {
    actingAsAdmin();

    $phone = '+1234567890';
    Customer::factory()->create(['phone' => $phone]);

    $duplicateData = [
        'phone' => $phone,
        'name' => 'Jane Smith',
    ];

    $response = $this->post('/admin/customers', $duplicateData);

    $response->assertRedirect();
    $response->assertSessionHasErrors('phone');
});

it('admin can view customer loyalty points balance', function () {
    actingAsAdmin();

    $customer = Customer::factory()->create(['loyalty_points' => 250]);

    $response = $this->get("/admin/customers/{$customer->id}");

    $response->assertStatus(200);
    $response->assertSee('250');
});

it('admin can adjust customer loyalty points manually', function () {
    actingAsAdmin();

    $customer = Customer::factory()->create(['loyalty_points' => 100]);

    // Add bonus points
    $response = $this->post("/admin/customers/{$customer->id}/adjust-points", [
        'adjustment' => 50,
        'reason' => 'Customer service bonus',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'loyalty_points' => 150,
    ]);

    // Deduct points
    $response = $this->post("/admin/customers/{$customer->id}/adjust-points", [
        'adjustment' => -25,
        'reason' => 'Points correction',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'loyalty_points' => 125,
    ]);
});

it('admin can view customer birthdate and age', function () {
    actingAsAdmin();

    $birthdate = '1990-05-15';
    $customer = Customer::factory()->create(['birthdate' => $birthdate]);

    $response = $this->get("/admin/customers/{$customer->id}");

    $response->assertStatus(200);
    $response->assertSee('1990-05-15');
});

it('admin can bulk activate customers', function () {
    actingAsAdmin();

    $customers = Customer::factory()->count(3)->create(['is_active' => false]);

    $response = $this->post('/admin/customers/bulk-activate', [
        'ids' => $customers->pluck('id')->toArray(),
    ]);

    $response->assertRedirect();

    foreach ($customers as $customer) {
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'is_active' => true]);
    }
});

it('admin can bulk deactivate customers', function () {
    actingAsAdmin();

    $customers = Customer::factory()->count(3)->create(['is_active' => true]);

    $response = $this->post('/admin/customers/bulk-deactivate', [
        'ids' => $customers->pluck('id')->toArray(),
    ]);

    $response->assertRedirect();

    foreach ($customers as $customer) {
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'is_active' => false]);
    }
});
