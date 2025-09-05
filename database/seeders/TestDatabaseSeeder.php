<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds for testing.
     */
    public function run(): void
    {
        // Create admin user for testing
        User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Create cashier user for testing
        User::factory()->create([
            'name' => 'Test Cashier',
            'email' => 'cashier@test.com',
            'role' => 'cashier',
            'is_active' => true,
        ]);

        // Create test brands
        $brands = Brand::factory()->count(5)->create();

        // Create test categories with hierarchy
        $parentCategories = Category::factory()->count(3)->create();
        $childCategories = collect();

        foreach ($parentCategories as $parent) {
            $children = Category::factory()->count(2)->create([
                'parent_id' => $parent->id,
            ]);
            $childCategories = $childCategories->merge($children);
        }

        $allCategories = $parentCategories->merge($childCategories);

        // Create test suppliers
        $suppliers = Supplier::factory()->count(3)->create();

        // Create test products
        $products = collect();
        foreach ($brands as $brand) {
            $brandProducts = Product::factory()->count(10)->create([
                'brand_id' => $brand->id,
                'category_id' => $allCategories->random()->id,
            ]);
            $products = $products->merge($brandProducts);
        }

        // Create product variants
        $variants = collect();
        foreach ($products as $product) {
            $productVariants = ProductVariant::factory()->count(3)->create([
                'product_id' => $product->id,
            ]);
            $variants = $variants->merge($productVariants);
        }

        // Create test customers
        $customers = Customer::factory()->count(20)->create();

        // Create some VIP customers
        Customer::factory()->count(5)->create([
            'loyalty_points' => fake()->numberBetween(1000, 5000),
        ]);

        // Create historical sales data
        $this->createHistoricalSales($customers, $variants);

        // Create stock movements
        $this->createStockMovements($variants);

        // Create some low stock items for testing alerts
        $this->createLowStockScenarios($variants);
    }

    /**
     * Create historical sales data for testing.
     */
    private function createHistoricalSales($customers, $variants): void
    {
        // Create sales over the past 6 months
        $startDate = now()->subMonths(6);

        for ($i = 0; $i < 200; $i++) {
            $saleDate = fake()->dateTimeBetween($startDate, now());
            $customer = fake()->optional(0.7)->randomElement($customers); // 70% have customers

            $sale = Sale::factory()->create([
                'customer_id' => $customer?->id,
                'user_id' => User::where('role', 'cashier')->first()->id,
                'sale_date' => $saleDate,
                'created_at' => $saleDate,
                'updated_at' => $saleDate,
            ]);

            // Create 1-5 sale items per sale
            $saleItemCount = fake()->numberBetween(1, 5);
            $saleVariants = $variants->random(min($saleItemCount, $variants->count()));

            foreach ($saleVariants as $variant) {
                $quantity = fake()->numberBetween(1, 3);
                $unitPrice = $variant->selling_price;

                SaleItem::factory()->create([
                    'sale_id' => $sale->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $quantity * $unitPrice,
                    'created_at' => $saleDate,
                    'updated_at' => $saleDate,
                ]);

                // Update stock (simulate stock reduction)
                $variant->decrement('stock_quantity', $quantity);

                // Create stock movement
                StockMovement::create([
                    'product_variant_id' => $variant->id,
                    'movement_type' => 'sale',
                    'reference_id' => $sale->id,
                    'quantity_change' => -$quantity,
                    'previous_quantity' => $variant->stock_quantity + $quantity,
                    'new_quantity' => $variant->stock_quantity,
                    'movement_date' => $saleDate,
                    'user_id' => $sale->user_id,
                    'created_at' => $saleDate,
                    'updated_at' => $saleDate,
                ]);
            }

            // Update sale totals
            $saleItems = $sale->saleItems;
            $subtotal = $saleItems->sum('total_price');
            $taxAmount = $subtotal * 0.085; // 8.5% tax
            $totalAmount = $subtotal + $taxAmount;

            $sale->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'points_earned' => $customer ? floor($totalAmount) : 0,
            ]);

            // Award loyalty points
            if ($customer) {
                $customer->increment('loyalty_points', floor($totalAmount));
            }
        }
    }

    /**
     * Create stock movements for testing.
     */
    private function createStockMovements($variants): void
    {
        foreach ($variants->take(50) as $variant) {
            // Create purchase movements
            $purchaseMovements = fake()->numberBetween(2, 5);

            for ($i = 0; $i < $purchaseMovements; $i++) {
                $quantity = fake()->numberBetween(10, 50);
                $previousQuantity = $variant->stock_quantity;

                $variant->increment('stock_quantity', $quantity);

                StockMovement::create([
                    'product_variant_id' => $variant->id,
                    'movement_type' => 'purchase',
                    'reference_id' => fake()->numberBetween(1, 100),
                    'quantity_change' => $quantity,
                    'previous_quantity' => $previousQuantity,
                    'new_quantity' => $variant->stock_quantity,
                    'movement_date' => fake()->dateTimeBetween('-3 months', 'now'),
                    'user_id' => User::where('role', 'admin')->first()->id,
                ]);
            }

            // Create adjustment movements
            $adjustmentMovements = fake()->numberBetween(0, 2);

            for ($i = 0; $i < $adjustmentMovements; $i++) {
                $quantity = fake()->numberBetween(-5, 10);
                $previousQuantity = $variant->stock_quantity;

                $variant->increment('stock_quantity', $quantity);

                StockMovement::create([
                    'product_variant_id' => $variant->id,
                    'movement_type' => 'adjustment',
                    'quantity_change' => $quantity,
                    'previous_quantity' => $previousQuantity,
                    'new_quantity' => $variant->stock_quantity,
                    'notes' => fake()->sentence(),
                    'movement_date' => fake()->dateTimeBetween('-1 month', 'now'),
                    'user_id' => User::where('role', 'admin')->first()->id,
                ]);
            }
        }
    }

    /**
     * Create low stock scenarios for testing alerts.
     */
    private function createLowStockScenarios($variants): void
    {
        // Create low stock items
        foreach ($variants->take(10) as $variant) {
            $variant->update([
                'stock_quantity' => fake()->numberBetween(1, 5),
                'min_stock_level' => 10,
            ]);
        }

        // Create out of stock items
        foreach ($variants->skip(10)->take(5) as $variant) {
            $variant->update([
                'stock_quantity' => 0,
                'min_stock_level' => fake()->numberBetween(5, 15),
            ]);
        }
    }
}
