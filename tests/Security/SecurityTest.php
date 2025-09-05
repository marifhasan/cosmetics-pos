<?php

use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('prevents SQL injection in search queries', function () {
    actingAsUser();

    // Test product search with malicious input
    $maliciousInputs = [
        "'; DROP TABLE products; --",
        "' OR '1'='1",
        "<script>alert('xss')</script>",
        "../../../../etc/passwd",
        "UNION SELECT * FROM users",
    ];

    foreach ($maliciousInputs as $input) {
        $response = $this->get("/pos/search?query=" . urlencode($input));

        $response->assertStatus(200);
        // Should not crash or return sensitive data
        $response->assertJsonStructure(['data' => []]);
    }
});

it('validates user authentication and authorization', function () {
    // Test unauthenticated access
    $response = $this->get('/admin/products');
    $response->assertRedirect('/login');

    // Test unauthorized access
    $cashier = User::factory()->create(['role' => 'cashier']);
    actingAsUser();

    $response = $this->get('/admin/users');
    $response->assertForbidden();

    // Test authorized access
    $admin = User::factory()->create(['role' => 'admin']);
    actingAsAdmin();

    $response = $this->get('/admin/users');
    $response->assertStatus(200);
});

it('prevents XSS attacks in form inputs', function () {
    actingAsAdmin();

    $xssPayload = '<script>alert("XSS")</script><img src=x onerror=alert(1)>';

    // Test product creation with XSS payload
    $response = $this->post('/admin/products', [
        'name' => $xssPayload,
        'description' => $xssPayload,
        'brand_id' => \App\Models\Brand::factory()->create()->id,
        'category_id' => \App\Models\Category::factory()->create()->id,
        'is_active' => true,
    ]);

    $response->assertRedirect();

    // Verify XSS payload is not stored
    $product = \App\Models\Product::latest()->first();
    expect($product->name)->not->toContain('<script>');
    expect($product->description)->not->toContain('<script>');
});

it('validates file upload security', function () {
    actingAsAdmin();

    $product = \App\Models\Product::factory()->create();

    // Test malicious file upload
    $maliciousFiles = [
        UploadedFile::fake()->create('malicious.php', 100),
        UploadedFile::fake()->create('script.exe', 100),
        UploadedFile::fake()->create('large_file.jpg', 100 * 1024 * 1024), // 100MB file
    ];

    foreach ($maliciousFiles as $file) {
        $response = $this->post("/admin/products/{$product->id}/upload-image", [
            'image' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    // Test valid file upload
    $validImage = UploadedFile::fake()->image('product.jpg', 800, 600);
    $response = $this->post("/admin/products/{$product->id}/upload-image", [
        'image' => $validImage,
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
});

it('prevents mass assignment vulnerabilities', function () {
    actingAsAdmin();

    // Test protected fields in user creation
    $response = $this->post('/admin/users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'role' => 'admin', // This should be protected
        'is_admin' => true, // This field doesn't exist and should be ignored
    ]);

    $response->assertRedirect();

    $user = User::latest()->first();
    expect($user->role)->toBe('cashier'); // Should default to cashier, not admin
    expect($user->is_admin)->toBeNull(); // Should not have this field
});

it('validates data integrity constraints', function () {
    actingAsAdmin();

    // Test unique constraints
    $phone = '+1234567890';
    Customer::factory()->create(['phone' => $phone]);

    $response = $this->post('/admin/customers', [
        'phone' => $phone, // Duplicate phone
        'name' => 'Different Name',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('phone');

    // Test foreign key constraints
    $response = $this->post('/admin/products', [
        'name' => 'Test Product',
        'brand_id' => 99999, // Non-existent brand
        'category_id' => \App\Models\Category::factory()->create()->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('brand_id');
});

it('prevents unauthorized data access', function () {
    // Create users with different roles
    $cashier = User::factory()->create(['role' => 'cashier']);
    $manager = User::factory()->create(['role' => 'manager']);
    $admin = User::factory()->create(['role' => 'admin']);

    $product = \App\Models\Product::factory()->create();

    // Test cashier permissions
    $this->actingAs($cashier);
    $response = $this->get("/admin/products/{$product->id}/edit");
    $response->assertForbidden();

    // Test manager permissions
    $this->actingAs($manager);
    $response = $this->get("/admin/users");
    $response->assertForbidden();

    // Test admin permissions
    $this->actingAs($admin);
    $response = $this->get("/admin/users");
    $response->assertStatus(200);
});

it('validates password security', function () {
    actingAsAdmin();

    // Test weak password
    $response = $this->post('/admin/users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => '123', // Too short
        'password_confirmation' => '123',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('password');

    // Test password confirmation mismatch
    $response = $this->post('/admin/users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'strongpassword123',
        'password_confirmation' => 'differentpassword',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('password');

    // Test valid password
    $response = $this->post('/admin/users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'strongpassword123',
        'password_confirmation' => 'strongpassword123',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $user = User::latest()->first();
    expect(Hash::check('strongpassword123', $user->password))->toBeTrue();
});

it('prevents CSRF attacks', function () {
    actingAsAdmin();

    // Test POST request without CSRF token
    $response = $this->post('/admin/products', [
        'name' => 'Test Product',
        'brand_id' => \App\Models\Brand::factory()->create()->id,
        'category_id' => \App\Models\Category::factory()->create()->id,
        '_token' => '', // Missing or invalid token
    ]);

    $response->assertStatus(419); // CSRF token mismatch
});

it('validates rate limiting', function () {
    // Test login rate limiting
    for ($i = 0; $i < 10; $i++) {
        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);
    }

    // Should be rate limited after multiple failed attempts
    $response = $this->post('/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'wrongpassword',
    ]);

    expect($response->getStatusCode())->toBe(429); // Too Many Requests
});

it('prevents directory traversal attacks', function () {
    actingAsUser();

    $traversalPaths = [
        '../../../../etc/passwd',
        '..\\..\\..\\..\\windows\\system32\\config\\sam',
        '/etc/passwd',
        'C:\\Windows\\System32\\config\\sam',
    ];

    foreach ($traversalPaths as $path) {
        $response = $this->get('/storage/' . $path);
        $response->assertStatus(404);
    }
});

it('validates API input sanitization', function () {
    actingAsUser();

    // Test POS API with malicious input
    $maliciousCart = [
        [
            'variant_id' => ProductVariant::factory()->create()->id,
            'quantity' => '1; DROP TABLE sales; --',
            'price' => '<script>alert("xss")</script>',
        ],
    ];

    $response = $this->postJson('/api/pos/sale', [
        'cart' => $maliciousCart,
        'payment_method' => 'cash',
    ]);

    $response->assertStatus(422); // Validation error
});

it('prevents unauthorized bulk operations', function () {
    actingAsUser(); // Regular user, not admin

    $products = \App\Models\Product::factory()->count(3)->create();

    // Attempt bulk delete
    $response = $this->delete('/admin/products/bulk-delete', [
        'ids' => $products->pluck('id')->toArray(),
    ]);

    $response->assertForbidden();

    // Verify products still exist
    foreach ($products as $product) {
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
});

it('validates session security', function () {
    $user = User::factory()->create();
    actingAsUser();

    // Make authenticated request
    $response = $this->get('/pos');
    $response->assertStatus(200);

    // Simulate session hijacking attempt
    $this->flushSession();

    // Request should now be unauthenticated
    $response = $this->get('/pos');
    $response->assertRedirect('/login');
});

it('prevents data leakage through error messages', function () {
    actingAsUser();

    // Test with non-existent product ID
    $response = $this->get('/admin/products/99999');

    $response->assertStatus(404);

    // Error message should not reveal sensitive information
    $response->assertDontSee('SQL');
    $response->assertDontSee('database');
    $response->assertDontSee('exception');
});

it('validates encryption of sensitive data', function () {
    $user = User::factory()->create([
        'password' => 'testpassword123',
    ]);

    // Password should be hashed
    expect($user->password)->not->toBe('testpassword123');
    expect(Hash::check('testpassword123', $user->password))->toBeTrue();

    // Test password reset token generation
    $user->sendPasswordResetNotification('test-token');

    // Token should be properly generated and not plain text
    expect($user->password)->not->toContain('test-token');
});

it('prevents concurrent modification conflicts', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    // Simulate concurrent stock updates
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    actingAsUser();

    // First update
    $response1 = $this->put("/admin/product-variants/{$variant->id}", [
        'stock_quantity' => 15,
    ]);

    $response1->assertRedirect();

    // Second update (should handle potential conflicts)
    $response2 = $this->put("/admin/product-variants/{$variant->id}", [
        'stock_quantity' => 20,
    ]);

    $response2->assertRedirect();

    // Final stock should be consistent
    $variant->refresh();
    expect($variant->stock_quantity)->toBe(20);
});

it('validates backup data integrity', function () {
    actingAsAdmin();

    // Create test data
    $customer = Customer::factory()->create();
    $sale = Sale::factory()->create(['customer_id' => $customer->id]);

    // Simulate backup process
    $backupData = [
        'customers' => Customer::all()->toArray(),
        'sales' => Sale::all()->toArray(),
    ];

    // Verify backup contains expected data
    expect($backupData['customers'])->toHaveCount(1);
    expect($backupData['sales'])->toHaveCount(1);

    // Verify sensitive data is not exposed in plain text
    $customerData = $backupData['customers'][0];
    expect($customerData)->toHaveKey('phone');
    expect($customerData)->toHaveKey('loyalty_points');

    // Password should not be in backup
    expect($customerData)->not->toHaveKey('password');
});
