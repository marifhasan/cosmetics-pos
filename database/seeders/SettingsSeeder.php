<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'store_name',
                'value' => ['value' => 'Glamour Cosmetics Store'],
                'description' => 'The name of the store displayed on receipts and reports'
            ],
            [
                'key' => 'store_address',
                'value' => ['value' => '123 Beauty Lane, Cosmetics City, CC 12345'],
                'description' => 'Store address for receipts and invoices'
            ],
            [
                'key' => 'store_phone',
                'value' => ['value' => '+1 (555) 123-4567'],
                'description' => 'Store contact phone number'
            ],
            [
                'key' => 'tax_rate',
                'value' => ['value' => 8.5],
                'description' => 'Tax rate percentage applied to sales'
            ],
            [
                'key' => 'points_per_dollar',
                'value' => ['value' => 1],
                'description' => 'Loyalty points earned per BDT spent'
            ],
            [
                'key' => 'default_min_stock_level',
                'value' => ['value' => 5],
                'description' => 'Default minimum stock level for new products'
            ],
            [
                'key' => 'currency',
                'value' => ['value' => 'BDT'],
                'description' => 'Store currency code (Bangladeshi Taka)'
            ],
            [
                'key' => 'receipt_footer',
                'value' => ['value' => 'Thank you for shopping with us! Visit us again soon.'],
                'description' => 'Footer message displayed on receipts'
            ],
            [
                'key' => 'low_stock_email_alerts',
                'value' => ['value' => true],
                'description' => 'Send email alerts when products are low in stock'
            ],
            [
                'key' => 'alert_email',
                'value' => ['value' => 'alerts@cosmetics-pos.com'],
                'description' => 'Email address to receive stock alerts'
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'description' => $setting['description']
                ]
            );
        }
    }
}