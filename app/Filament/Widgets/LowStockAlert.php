<?php

namespace App\Filament\Widgets;

use App\Models\ProductVariant;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockAlert extends BaseWidget
{
    protected static ?string $heading = 'Low Stock Alerts';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ProductVariant::query()
                    ->with(['product.brand', 'product.category'])
                    ->where(function (Builder $query) {
                        $query->lowStock()->orWhere('stock_quantity', '<=', 0);
                    })
                    ->where('is_active', true)
            )
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('variant_name')
                    ->label('Variant')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->copyable(),
                    
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Current Stock')
                    ->badge()
                    ->color(fn (ProductVariant $record): string => match ($record->stock_status) {
                        'out_of_stock' => 'danger',
                        'low_stock' => 'warning',
                        default => 'success',
                    })
                    ->suffix(fn (ProductVariant $record): string => ' / ' . $record->min_stock_level),
                    
                Tables\Columns\TextColumn::make('stock_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'out_of_stock' => 'danger',
                        'low_stock' => 'warning',
                        'in_stock' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'out_of_stock' => 'Out of Stock',
                        'low_stock' => 'Low Stock',
                        'in_stock' => 'In Stock',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('product.brand.name')
                    ->label('Brand')
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\Action::make('reorder')
                    ->label('Reorder')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('primary')
                    ->url(fn (ProductVariant $record): string => 
                        route('filament.admin.resources.purchases.create', [
                            'product_variant_id' => $record->id
                        ])
                    ),
                    
                Tables\Actions\Action::make('adjust_stock')
                    ->label('Quick Adjust')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('warning')
                    ->url(fn (ProductVariant $record): string => 
                        route('filament.admin.resources.product-variants.edit', $record)
                    ),
            ])
            ->defaultSort('stock_quantity', 'asc')
            ->paginated([10, 25, 50])
            ->poll('30s'); // Auto-refresh every 30 seconds
    }
}

