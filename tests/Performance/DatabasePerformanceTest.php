<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('handles large product catalog efficiently', function () {
    // Create large dataset
    $startTime = microtime(true);

    Brand::factory()->count(50)->create();
    Category::factory()->count(20)->create();

    // Create 1000 products with variants
    $products = Product::factory()->count(1000)->create();

    foreach ($products as $product) {
        ProductVariant::factory()->count(3)->create([
            'product_id' => $product->id,
        ]);
    }

    $creationTime = microtime(true) - $startTime;

    // Query performance test
    $queryStartTime = microtime(true);

    $activeProducts = Product::with(['variants', 'brand', 'category'])
        ->active()
        ->whereHas('variants', function ($query) {
            $query->where('stock_quantity', '>', 0);
        })
        ->get();

    $queryTime = microtime(true) - $queryStartTime;

    expect($creationTime)->toBeLessThan(30.0); // Should complete within 30 seconds
    expect($queryTime)->toBeLessThan(5.0); // Query should complete within 5 seconds
    expect($activeProducts)->toHaveCount(1000);
});

it('performs bulk stock updates efficiently', function () {
    // Create test data
    $variants = ProductVariant::factory()->count(500)->create([
        'stock_quantity' => 100,
    ]);

    $startTime = microtime(true);

    // Bulk update stock
    DB::transaction(function () use ($variants) {
        foreach ($variants as $variant) {
            $variant->update(['stock_quantity' => 150]);
        }
    });

    $updateTime = microtime(true) - $startTime;

    expect($updateTime)->toBeLessThan(10.0); // Should complete within 10 seconds

    // Verify all updates were successful
    $updatedCount = ProductVariant::where('stock_quantity', 150)->count();
    expect($updatedCount)->toBe(500);
});

it('handles concurrent sales efficiently', function () {
    $variant = ProductVariant::factory()->create([
        'stock_quantity' => 1000,
        'selling_price' => 25.00,
    ]);

    $customer = Customer::factory()->create();

    $startTime = microtime(true);

    // Simulate 50 concurrent sales
    for ($i = 0; $i < 50; $i++) {
        actingAsUser();

        $this->post('/pos/complete-sale', [
            'customer_phone' => $customer->phone,
            'cart' => [
                [
                    'variant_id' => $variant->id,
                    'quantity' => 1,
                    'price' => 25.00,
                ],
            ],
            'payment_method' => 'cash',
            'cash_received' => 25.00,
        ]);
    }

    $salesTime = microtime(true) - $startTime;

    expect($salesTime)->toBeLessThan(60.0); // Should complete within 60 seconds

    // Verify final stock
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'stock_quantity' => 950, // 1000 - 50
    ]);

    // Verify sales count
    $salesCount = Sale::count();
    expect($salesCount)->toBe(50);
});

it('optimizes product search queries', function () {
    // Create search test data
    Product::factory()->count(2000)->create();

    $searchTerms = ['face', 'cream', 'premium', 'luxe'];

    $totalSearchTime = 0;

    foreach ($searchTerms as $term) {
        $startTime = microtime(true);

        $results = Product::where('name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%")
            ->limit(50)
            ->get();

        $searchTime = microtime(true) - $startTime;
        $totalSearchTime += $searchTime;

        expect($searchTime)->toBeLessThan(1.0); // Each search should be under 1 second
    }

    $averageSearchTime = $totalSearchTime / count($searchTerms);
    expect($averageSearchTime)->toBeLessThan(0.5); // Average should be under 0.5 seconds
});

it('handles large customer database queries', function () {
    // Create large customer dataset
    Customer::factory()->count(5000)->create();

    $startTime = microtime(true);

    // Complex customer query with loyalty points
    $vipCustomers = Customer::where('loyalty_points', '>', 1000)
        ->where('is_active', true)
        ->orderBy('loyalty_points', 'desc')
        ->limit(100)
        ->get();

    $queryTime = microtime(true) - $startTime;

    expect($queryTime)->toBeLessThan(3.0); // Should complete within 3 seconds

    // Verify query results
    expect($vipCustomers)->toHaveCount(100);
    expect($vipCustomers->first()->loyalty_points)->toBeGreaterThanOrEqual($vipCustomers->last()->loyalty_points);
});

it('optimizes sales reporting queries', function () {
    // Create sales data for reporting
    $customers = Customer::factory()->count(100)->create();
    $variants = ProductVariant::factory()->count(50)->create([
        'stock_quantity' => 1000,
    ]);

    // Generate sales over 30 days
    for ($day = 1; $day <= 30; $day++) {
        $dailySales = rand(20, 50);

        for ($i = 0; $i < $dailySales; $i++) {
            actingAsUser();

            $this->post('/pos/complete-sale', [
                'customer_phone' => $customers->random()->phone,
                'cart' => [
                    [
                        'variant_id' => $variants->random()->id,
                        'quantity' => rand(1, 3),
                        'price' => rand(10, 100),
                    ],
                ],
                'payment_method' => 'cash',
                'cash_received' => 200.00,
            ]);
        }
    }

    actingAsAdmin();

    // Test daily sales report performance
    $startTime = microtime(true);

    $dailySales = Sale::selectRaw('DATE(created_at) as date, COUNT(*) as sales_count, SUM(total_amount) as total_amount')
        ->where('created_at', '>=', now()->subDays(30))
        ->groupBy('date')
        ->orderBy('date')
        ->get();

    $reportTime = microtime(true) - $startTime;

    expect($reportTime)->toBeLessThan(5.0); // Report should generate within 5 seconds
    expect($dailySales)->toHaveCount(30); // Should have 30 days of data
});

it('handles bulk import operations efficiently', function () {
    $startTime = microtime(true);

    // Bulk create products
    $products = [];
    for ($i = 1; $i <= 1000; $i++) {
        $products[] = [
            'name' => "Bulk Product {$i}",
            'slug' => "bulk-product-{$i}",
            'description' => "Description for bulk product {$i}",
            'brand_id' => Brand::factory()->create()->id,
            'category_id' => Category::factory()->create()->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    DB::table('products')->insert($products);

    // Bulk create variants
    $variants = [];
    $productIds = DB::table('products')->pluck('id');

    foreach ($productIds as $productId) {
        for ($j = 1; $j <= 2; $j++) {
            $variants[] = [
                'product_id' => $productId,
                'variant_name' => "Variant {$j}",
                'sku' => "BULK-{$productId}-{$j}",
                'cost_price' => rand(5, 50),
                'selling_price' => rand(10, 100),
                'stock_quantity' => rand(10, 200),
                'min_stock_level' => rand(5, 20),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
    }

    DB::table('product_variants')->insert($variants);

    $importTime = microtime(true) - $startTime;

    expect($importTime)->toBeLessThan(20.0); // Should complete within 20 seconds

    // Verify data integrity
    $productCount = Product::count();
    $variantCount = ProductVariant::count();

    expect($productCount)->toBe(1000);
    expect($variantCount)->toBe(2000);
});

it('optimizes stock alert queries', function () {
    // Create large stock dataset
    $variants = ProductVariant::factory()->count(2000)->create([
        'is_active' => true,
    ]);

    // Make some low stock and out of stock
    ProductVariant::whereIn('id', $variants->pluck('id')->take(200))
        ->update(['stock_quantity' => 2, 'min_stock_level' => 10]);

    ProductVariant::whereIn('id', $variants->pluck('id')->skip(200)->take(100))
        ->update(['stock_quantity' => 0, 'min_stock_level' => 5]);

    actingAsAdmin();

    $startTime = microtime(true);

    // Query low stock items
    $lowStockItems = ProductVariant::lowStock()
        ->active()
        ->with(['product.brand', 'product.category'])
        ->get();

    $lowStockQueryTime = microtime(true) - $startTime;

    $startTime = microtime(true);

    // Query out of stock items
    $outOfStockItems = ProductVariant::outOfStock()
        ->active()
        ->with(['product.brand', 'product.category'])
        ->get();

    $outOfStockQueryTime = microtime(true) - $startTime;

    expect($lowStockQueryTime)->toBeLessThan(2.0);
    expect($outOfStockQueryTime)->toBeLessThan(2.0);

    expect($lowStockItems)->toHaveCount(200);
    expect($outOfStockItems)->toHaveCount(100);
});

it('handles database backup and restore operations', function () {
    // Create test data
    Brand::factory()->count(10)->create();
    Category::factory()->count(5)->create();
    Customer::factory()->count(100)->create();
    Product::factory()->count(200)->create();

    $variants = ProductVariant::factory()->count(500)->create();

    // Create sales
    foreach ($variants->take(100) as $variant) {
        $variant->update(['stock_quantity' => 50]);
    }

    actingAsUser();

    for ($i = 0; $i < 200; $i++) {
        $this->post('/pos/complete-sale', [
            'cart' => [
                [
                    'variant_id' => $variants->random()->id,
                    'quantity' => 1,
                    'price' => 25.00,
                ],
            ],
            'payment_method' => 'cash',
            'cash_received' => 25.00,
        ]);
    }

    // Simulate backup operation (in real scenario, this would use database backup tools)
    $startTime = microtime(true);

    // Count all records for backup verification
    $recordCounts = [
        'brands' => Brand::count(),
        'categories' => Category::count(),
        'customers' => Customer::count(),
        'products' => Product::count(),
        'product_variants' => ProductVariant::count(),
        'sales' => Sale::count(),
        'sale_items' => SaleItem::count(),
    ];

    $backupTime = microtime(true) - $startTime;

    expect($backupTime)->toBeLessThan(5.0);

    // Verify data consistency
    expect($recordCounts['brands'])->toBe(10);
    expect($recordCounts['customers'])->toBe(100);
    expect($recordCounts['products'])->toBe(200);
    expect($recordCounts['sales'])->toBe(200);
});

it('optimizes loyalty points calculations', function () {
    // Create large customer base
    Customer::factory()->count(2000)->create();

    $variant = ProductVariant::factory()->create([
        'selling_price' => 50.00,
        'stock_quantity' => 5000,
    ]);

    actingAsUser();

    $startTime = microtime(true);

    // Process purchases for all customers
    $customers = Customer::all();

    foreach ($customers as $customer) {
        $this->post('/pos/complete-sale', [
            'customer_phone' => $customer->phone,
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
    }

    $processingTime = microtime(true) - $startTime;

    expect($processingTime)->toBeLessThan(300.0); // Should complete within 5 minutes

    // Verify all customers have correct points
    $customersWithPoints = Customer::where('loyalty_points', '>', 0)->count();
    expect($customersWithPoints)->toBe(2000);

    // Verify total points calculation
    $totalPoints = Customer::sum('loyalty_points');
    expect($totalPoints)->toBe(100000); // 2000 customers * 50 points each
});

it('handles memory efficiently with large datasets', function () {
    $initialMemory = memory_get_usage();

    // Create large dataset
    $products = Product::factory()->count(5000)->create();

    foreach ($products->chunk(500) as $chunk) {
        foreach ($chunk as $product) {
            ProductVariant::factory()->count(2)->create([
                'product_id' => $product->id,
            ]);
        }
    }

    $peakMemory = memory_get_peak_usage();
    $memoryUsed = $peakMemory - $initialMemory;

    // Memory usage should be reasonable (under 50MB for this operation)
    expect($memoryUsed)->toBeLessThan(50 * 1024 * 1024);

    // Verify data integrity
    $productCount = Product::count();
    $variantCount = ProductVariant::count();

    expect($productCount)->toBe(5000);
    expect($variantCount)->toBe(10000);
});
