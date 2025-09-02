<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Setting;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function show(Sale $sale)
    {
        $sale->load(['customer', 'saleItems.productVariant.product.brand', 'user']);
        
        $storeSettings = [
            'name' => Setting::getValue('store_name', ['value' => 'Cosmetics Store'])['value'] ?? 'Cosmetics Store',
            'address' => Setting::getValue('store_address', ['value' => ''])['value'] ?? '',
            'phone' => Setting::getValue('store_phone', ['value' => ''])['value'] ?? '',
            'footer' => Setting::getValue('receipt_footer', ['value' => 'Thank you for shopping with us!'])['value'] ?? 'Thank you for shopping with us!',
        ];
        
        return view('receipt.show', compact('sale', 'storeSettings'));
    }
    
    public function print(Sale $sale)
    {
        $sale->load(['customer', 'saleItems.productVariant.product.brand', 'user']);
        
        $storeSettings = [
            'name' => Setting::getValue('store_name', ['value' => 'Cosmetics Store'])['value'] ?? 'Cosmetics Store',
            'address' => Setting::getValue('store_address', ['value' => ''])['value'] ?? '',
            'phone' => Setting::getValue('store_phone', ['value' => ''])['value'] ?? '',
            'footer' => Setting::getValue('receipt_footer', ['value' => 'Thank you for shopping with us!'])['value'] ?? 'Thank you for shopping with us!',
        ];
        
        return view('receipt.print', compact('sale', 'storeSettings'));
    }
}