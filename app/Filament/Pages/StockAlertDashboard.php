<?php

namespace App\Filament\Pages;

use App\Models\ProductVariant;
use Filament\Forms\Components;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;
use Filament\Support\Enums\MaxWidth;

class StockAlertDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    
    protected static ?string $navigationGroup = 'Inventory';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $title = 'Stock Alerts';

    protected static string $view = 'filament.pages.stock-alert-dashboard';
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_low_stock')
                ->label('Export Low Stock Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->action(function () {
                    // Export functionality would go here
                    $this->notify('success', 'Export functionality coming soon!');
                }),
                
            Action::make('bulk_reorder')
                ->label('Bulk Reorder')
                ->icon('heroicon-o-shopping-cart')
                ->color('warning')
                ->action(function () {
                    // Bulk reorder functionality would go here
                    $this->notify('success', 'Bulk reorder functionality coming soon!');
                }),
        ];
    }

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
                    ->sortable()
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('variant_name')
                    ->label('Variant')
                    ->searchable()
                    ->description(fn (ProductVariant $record): string => $record->sku),
                    
                Tables\Columns\TextColumn::make('product.brand.name')
                    ->label('Brand')
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('product.category.name')
                    ->label('Category')
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Current Stock')
                    ->badge()
                    ->color(fn (ProductVariant $record): string => match ($record->stock_status) {
                        'out_of_stock' => 'danger',
                        'low_stock' => 'warning',
                        default => 'success',
                    })
                    ->suffix(fn (ProductVariant $record): string => ' / ' . $record->min_stock_level)
                    ->tooltip('Current Stock / Min Level'),
                    
                Tables\Columns\TextColumn::make('stock_status')
                    ->label('Alert Level')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'out_of_stock' => 'danger',
                        'low_stock' => 'warning',
                        'in_stock' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'out_of_stock' => 'CRITICAL - Out of Stock',
                        'low_stock' => 'WARNING - Low Stock',
                        'in_stock' => 'OK - In Stock',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Price')
                    ->money('BDT')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stock_status')
                    ->label('Alert Level')
                    ->options([
                        'out_of_stock' => 'Out of Stock',
                        'low_stock' => 'Low Stock',
                        'all_alerts' => 'All Alerts',
                    ])
                    ->default('all_alerts')
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? 'all_alerts') {
                            'out_of_stock' => $query->where('stock_quantity', '<=', 0),
                            'low_stock' => $query->whereColumn('stock_quantity', '<=', 'min_stock_level')
                                                ->where('stock_quantity', '>', 0),
                            default => $query->where(function (Builder $query) {
                                $query->whereColumn('stock_quantity', '<=', 'min_stock_level')
                                      ->orWhere('stock_quantity', '<=', 0);
                            }),
                        };
                    }),
                    
                Tables\Filters\SelectFilter::make('brand')
                    ->relationship('product.brand', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('product.category', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('quick_adjust')
                    ->label('Quick Adjust')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('warning')
                    ->form([
                        Components\TextInput::make('adjustment')
                            ->label('Stock Adjustment')
                            ->helperText('Use positive numbers to add stock, negative to reduce')
                            ->numeric()
                            ->required(),
                        Components\Textarea::make('notes')
                            ->label('Adjustment Notes')
                            ->placeholder('Reason for stock adjustment...'),
                    ])
                    ->action(function (ProductVariant $record, array $data): void {
                        try {
                            $record->updateStock(
                                quantityChange: (int) $data['adjustment'],
                                movementType: 'adjustment',
                                referenceId: null,
                                userId: auth()->id() ?? 1,
                                notes: $data['notes'] ?? null
                            );
                            
                            $this->notify('success', "Stock adjusted by {$data['adjustment']} units");
                        } catch (\Exception $e) {
                            $this->notify('danger', 'Stock update failed: ' . $e->getMessage());
                        }
                    }),
                    
                Tables\Actions\Action::make('reorder')
                    ->label('Create Purchase Order')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('primary')
                    ->url(fn (ProductVariant $record): string => 
                        route('filament.admin.resources.purchases.create')
                    ),
                    
                Tables\Actions\Action::make('view_movements')
                    ->label('Stock History')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->url(fn (ProductVariant $record): string => 
                        route('filament.admin.resources.stock-movements.index', [
                            'tableFilters' => [
                                'product_variant' => ['value' => $record->id]
                            ]
                        ])
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('mark_for_reorder')
                    ->label('Mark for Reorder')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('primary')
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        $this->notify('success', $records->count() . ' items marked for reorder');
                        // Add logic to create purchase order with selected items
                    }),
            ])
            ->defaultSort('stock_quantity', 'asc')
            ->poll('30s')
            ->striped();
    }
    
    protected function notify(string $type, string $message): void
    {
        \Filament\Notifications\Notification::make()
            ->title($message)
            ->{$type}()
            ->send();
    }
}

