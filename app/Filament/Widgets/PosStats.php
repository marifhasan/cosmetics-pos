<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PosStats extends BaseWidget
{
	protected function getStats(): array
	{
		return [
			Stat::make("Today's Sales", \App\Models\Sale::whereDate('sale_date', today())->count())
				->description('Completed today')
				->descriptionIcon('heroicon-m-check-circle')
				->color('success'),

			Stat::make("Today's Revenue", '৳' . number_format(\App\Models\Sale::whereDate('sale_date', today())->sum('total_amount')))
				->description('Total income today')
				->descriptionIcon('heroicon-m-banknotes')
				->color('primary'),

			Stat::make('Products Available', \App\Models\ProductVariant::where('stock_quantity', '>', 0)->count())
				->description('In stock')
				->descriptionIcon('heroicon-m-cube')
				->color('purple'),

			Stat::make('Low Stock Alerts', \App\Models\ProductVariant::lowStock()->count())
				->description('Needs restocking')
				->descriptionIcon('heroicon-m-exclamation-triangle')
				->color('danger'),
		];
	}
}
