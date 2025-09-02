<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Customer;
use App\Models\Supplier;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@cosmetics-pos.com',
        ]);

        // Create brands
        $brands = [
            ['name' => 'L\'Oréal', 'slug' => 'loreal', 'description' => 'French cosmetics company'],
            ['name' => 'MAC', 'slug' => 'mac', 'description' => 'Professional makeup brand'],
            ['name' => 'Maybelline', 'slug' => 'maybelline', 'description' => 'American cosmetics brand'],
            ['name' => 'Revlon', 'slug' => 'revlon', 'description' => 'American multinational cosmetics company'],
            ['name' => 'CoverGirl', 'slug' => 'covergirl', 'description' => 'American cosmetics brand'],
        ];

        foreach ($brands as $brandData) {
            Brand::create($brandData);
        }

        // Create categories
        $categories = [
            ['name' => 'Face', 'slug' => 'face', 'description' => 'Face makeup products'],
            ['name' => 'Eyes', 'slug' => 'eyes', 'description' => 'Eye makeup products'],
            ['name' => 'Lips', 'slug' => 'lips', 'description' => 'Lip makeup products'],
            ['name' => 'Nails', 'slug' => 'nails', 'description' => 'Nail care and polish'],
            ['name' => 'Skincare', 'slug' => 'skincare', 'description' => 'Skincare products'],
        ];

        foreach ($categories as $categoryData) {
            Category::create($categoryData);
        }

        // Create subcategories
        $faceCategory = Category::where('slug', 'face')->first();
        $eyesCategory = Category::where('slug', 'eyes')->first();
        $lipsCategory = Category::where('slug', 'lips')->first();

        $subcategories = [
            ['name' => 'Foundation', 'slug' => 'foundation', 'parent_id' => $faceCategory->id],
            ['name' => 'Concealer', 'slug' => 'concealer', 'parent_id' => $faceCategory->id],
            ['name' => 'Blush', 'slug' => 'blush', 'parent_id' => $faceCategory->id],
            ['name' => 'Mascara', 'slug' => 'mascara', 'parent_id' => $eyesCategory->id],
            ['name' => 'Eyeshadow', 'slug' => 'eyeshadow', 'parent_id' => $eyesCategory->id],
            ['name' => 'Eyeliner', 'slug' => 'eyeliner', 'parent_id' => $eyesCategory->id],
            ['name' => 'Lipstick', 'slug' => 'lipstick', 'parent_id' => $lipsCategory->id],
            ['name' => 'Lip Gloss', 'slug' => 'lip-gloss', 'parent_id' => $lipsCategory->id],
        ];

        foreach ($subcategories as $subcategoryData) {
            Category::create($subcategoryData);
        }

        // Create sample customers
        $customers = [
            ['phone' => '+1234567890', 'name' => 'Sarah Johnson', 'email' => 'sarah@example.com', 'loyalty_points' => 150],
            ['phone' => '+1234567891', 'name' => 'Emily Davis', 'email' => 'emily@example.com', 'loyalty_points' => 75],
            ['phone' => '+1234567892', 'name' => 'Jessica Wilson', 'email' => 'jessica@example.com', 'loyalty_points' => 200],
        ];

        foreach ($customers as $customerData) {
            Customer::create($customerData);
        }

        // Create suppliers
        $suppliers = [
            ['name' => 'Beauty Supply Co.', 'contact_person' => 'John Smith', 'email' => 'orders@beautysupply.com', 'phone' => '+1555123456'],
            ['name' => 'Cosmetics Wholesale', 'contact_person' => 'Jane Doe', 'email' => 'sales@cosmeticswholesale.com', 'phone' => '+1555654321'],
            ['name' => 'Makeup Distributors Inc.', 'contact_person' => 'Mike Johnson', 'email' => 'info@makeupdist.com', 'phone' => '+1555789012'],
        ];

        foreach ($suppliers as $supplierData) {
            Supplier::create($supplierData);
        }

        // Create products and variants
        $this->createSampleProducts();
        
        // Seed settings
        $this->call(SettingsSeeder::class);
    }

    private function createSampleProducts()
    {
        $loreal = Brand::where('slug', 'loreal')->first();
        $mac = Brand::where('slug', 'mac')->first();
        $maybelline = Brand::where('slug', 'maybelline')->first();

        $foundation = Category::where('slug', 'foundation')->first();
        $mascara = Category::where('slug', 'mascara')->first();
        $lipstick = Category::where('slug', 'lipstick')->first();
        $eyeshadow = Category::where('slug', 'eyeshadow')->first();

        // L'Oréal True Match Foundation
        $product1 = Product::create([
            'name' => 'True Match Foundation',
            'slug' => 'true-match-foundation',
            'description' => 'Perfect match foundation with SPF protection',
            'brand_id' => $loreal->id,
            'category_id' => $foundation->id,
            'barcode' => '3600523351831',
        ]);

        // Create variants with different stock levels for testing
        ProductVariant::create([
            'product_id' => $product1->id,
            'variant_name' => 'Ivory (W1)',
            'sku' => 'LOR-TM-W1',
            'cost_price' => 8.50,
            'selling_price' => 14.99,
            'stock_quantity' => 2, // Low stock
            'min_stock_level' => 5,
            'barcode' => '3600523351831001',
        ]);

        ProductVariant::create([
            'product_id' => $product1->id,
            'variant_name' => 'Porcelain (W2)',
            'sku' => 'LOR-TM-W2',
            'cost_price' => 8.50,
            'selling_price' => 14.99,
            'stock_quantity' => 0, // Out of stock
            'min_stock_level' => 5,
            'barcode' => '3600523351831002',
        ]);

        ProductVariant::create([
            'product_id' => $product1->id,
            'variant_name' => 'Beige (W3)',
            'sku' => 'LOR-TM-W3',
            'cost_price' => 8.50,
            'selling_price' => 14.99,
            'stock_quantity' => 15, // Good stock
            'min_stock_level' => 5,
            'barcode' => '3600523351831003',
        ]);

        // MAC Lipstick
        $product2 = Product::create([
            'name' => 'MAC Lipstick',
            'slug' => 'mac-lipstick',
            'description' => 'Iconic matte lipstick with rich color payoff',
            'brand_id' => $mac->id,
            'category_id' => $lipstick->id,
            'barcode' => '773602077205',
        ]);

        ProductVariant::create([
            'product_id' => $product2->id,
            'variant_name' => 'Ruby Woo',
            'sku' => 'MAC-LS-RW',
            'cost_price' => 12.00,
            'selling_price' => 19.00,
            'stock_quantity' => 3, // Low stock
            'min_stock_level' => 8,
            'barcode' => '773602077205001',
        ]);

        ProductVariant::create([
            'product_id' => $product2->id,
            'variant_name' => 'Velvet Teddy',
            'sku' => 'MAC-LS-VT',
            'cost_price' => 12.00,
            'selling_price' => 19.00,
            'stock_quantity' => 25,
            'min_stock_level' => 8,
            'barcode' => '773602077205002',
        ]);

        // Maybelline Great Lash Mascara
        $product3 = Product::create([
            'name' => 'Great Lash Mascara',
            'slug' => 'great-lash-mascara',
            'description' => 'America\'s favorite mascara for volume and length',
            'brand_id' => $maybelline->id,
            'category_id' => $mascara->id,
            'barcode' => '041554269307',
        ]);

        ProductVariant::create([
            'product_id' => $product3->id,
            'variant_name' => 'Black',
            'sku' => 'MAY-GL-BK',
            'cost_price' => 3.50,
            'selling_price' => 6.99,
            'stock_quantity' => 1, // Very low stock
            'min_stock_level' => 10,
            'barcode' => '041554269307001',
        ]);

        ProductVariant::create([
            'product_id' => $product3->id,
            'variant_name' => 'Brown',
            'sku' => 'MAY-GL-BR',
            'cost_price' => 3.50,
            'selling_price' => 6.99,
            'stock_quantity' => 0, // Out of stock
            'min_stock_level' => 10,
            'barcode' => '041554269307002',
        ]);
    }
}
