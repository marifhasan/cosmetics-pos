<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductVariantResource\Pages;
use App\Filament\Resources\ProductVariantResource\RelationManagers;
use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Colors\Color;
use Filament\Notifications\Notification;

class ProductVariantResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    
    protected static ?string $navigationGroup = 'Inventory';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('variant_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('cost_price')
                    ->required()
                    ->numeric()
                    ->prefix('৳')
                    ->step(0.01),
                Forms\Components\TextInput::make('selling_price')
                    ->required()
                    ->numeric()
                    ->prefix('৳')
                    ->step(0.01),
                Forms\Components\TextInput::make('stock_quantity')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->helperText('Current stock quantity'),
                Forms\Components\TextInput::make('min_stock_level')
                    ->required()
                    ->numeric()
                    ->default(5)
                    ->minValue(1)
                    ->helperText('Minimum stock level for alerts'),
                Forms\Components\TextInput::make('barcode')
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->sortable()
                    ->searchable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('variant_name')
                    ->searchable()
                    ->description(fn (ProductVariant $record): string => $record->sku),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (ProductVariant $record): string => match ($record->stock_status) {
                        'out_of_stock' => 'danger',
                        'low_stock' => 'warning',
                        default => 'success',
                    })
                    ->suffix(fn (ProductVariant $record): string => ' / ' . $record->min_stock_level)
                    ->tooltip('Current Stock / Min Level'),
                Tables\Columns\TextColumn::make('stock_status')
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
                Tables\Columns\TextColumn::make('selling_price')
                    ->money('BDT')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('stock_status')
                    ->label('Stock Status')
                    ->options([
                        'out_of_stock' => 'Out of Stock',
                        'low_stock' => 'Low Stock',
                        'in_stock' => 'In Stock',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'out_of_stock' => $query->where('stock_quantity', '<=', 0),
                            'low_stock' => $query->whereColumn('stock_quantity', '<=', 'min_stock_level')
                                                ->where('stock_quantity', '>', 0),
                            'in_stock' => $query->whereColumn('stock_quantity', '>', 'min_stock_level'),
                            default => $query,
                        };
                    }),
                Filter::make('low_stock_alert')
                    ->label('Low Stock Alert')
                    ->query(fn (Builder $query): Builder => $query->lowStock())
                    ->toggle(),
                Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query): Builder => $query->outOfStock())
                    ->toggle(),
                SelectFilter::make('product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('is_active')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('adjust_stock')
                    ->label('Adjust Stock')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('adjustment')
                            ->label('Stock Adjustment')
                            ->helperText('Use positive numbers to add stock, negative to reduce')
                            ->numeric()
                            ->required(),
                        Forms\Components\Textarea::make('notes')
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
                            
                            Notification::make()
                                ->title('Stock Updated')
                                ->body("Stock adjusted by {$data['adjustment']} units")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Stock Update Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('bulk_deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['is_active' => false])),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('stock_quantity', 'asc'); // Show low stock items first
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductVariants::route('/'),
            'create' => Pages\CreateProductVariant::route('/create'),
            'edit' => Pages\EditProductVariant::route('/{record}/edit'),
        ];
    }
}
