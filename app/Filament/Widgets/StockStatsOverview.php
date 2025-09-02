<?php

namespace App\Filament\Widgets;

use App\Models\ProductVariant;
use App\Models\Product;
use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StockStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalProducts = Product::count();
        $totalVariants = ProductVariant::count();
        $lowStockCount = ProductVariant::lowStock()->count();
        $outOfStockCount = ProductVariant::outOfStock()->count();
        $todaySales = Sale::whereDate('sale_date', today())->sum('total_amount');
        $todaySalesCount = Sale::whereDate('sale_date', today())->count();

        return [
            Stat::make('Total Products', $totalProducts)
                ->description('Active product variants')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),
                
            Stat::make('Low Stock Alerts', $lowStockCount)
                ->description('Items below minimum level')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.product-variants.index', [
                    'tableFilters' => ['low_stock_alert' => ['isActive' => true]]
                ])),
                
            Stat::make('Out of Stock', $outOfStockCount)
                ->description('Items with zero stock')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($outOfStockCount > 0 ? 'danger' : 'success')
                ->url(route('filament.admin.resources.product-variants.index', [
                    'tableFilters' => ['out_of_stock' => ['isActive' => true]]
                ])),
                
            Stat::make('Today\'s Sales', '৳' . number_format($todaySales, 2))
                ->description($todaySalesCount . ' transactions today')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
        ];
    }
}

