<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockMovementResource\Pages;
use App\Models\StockMovement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    
    protected static ?string $navigationGroup = 'Inventory';
    
    protected static ?int $navigationSort = 4;
    
    protected static ?string $navigationLabel = 'Stock History';
    
    protected static ?string $modelLabel = 'Stock Movement';
    
    protected static ?string $pluralModelLabel = 'Stock Movements';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Stock Movement Details')
                    ->schema([
                        Forms\Components\Select::make('product_variant_id')
                            ->relationship('productVariant', 'sku')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->product->name} - {$record->variant_name} ({$record->sku})")
                            ->searchable(['sku', 'variant_name'])
                            ->preload()
                            ->required(),
                            
                        Forms\Components\Select::make('movement_type')
                            ->options([
                                'sale' => 'Sale (Stock Out)',
                                'purchase' => 'Purchase (Stock In)',
                                'adjustment' => 'Manual Adjustment',
                                'return' => 'Return',
                            ])
                            ->required()
                            ->native(false),
                            
                        Forms\Components\TextInput::make('quantity_change')
                            ->label('Quantity Change')
                            ->required()
                            ->numeric()
                            ->helperText('Positive for stock in, negative for stock out'),
                            
                        Forms\Components\TextInput::make('previous_quantity')
                            ->label('Previous Stock')
                            ->required()
                            ->numeric()
                            ->disabled(),
                            
                        Forms\Components\TextInput::make('new_quantity')
                            ->label('New Stock')
                            ->required()
                            ->numeric()
                            ->disabled(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->default(auth()->id()),
                            
                        Forms\Components\DateTimePicker::make('movement_date')
                            ->required()
                            ->default(now())
                            ->native(false),
                            
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('productVariant.product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('productVariant.variant_name')
                    ->label('Variant')
                    ->searchable()
                    ->description(fn (StockMovement $record): string => $record->productVariant->sku),
                    
                Tables\Columns\TextColumn::make('movement_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sale' => 'danger',
                        'purchase' => 'success',
                        'adjustment' => 'warning',
                        'return' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'sale' => 'Sale (Out)',
                        'purchase' => 'Purchase (In)',
                        'adjustment' => 'Adjustment',
                        'return' => 'Return',
                        default => ucfirst($state),
                    }),
                    
                Tables\Columns\TextColumn::make('quantity_change')
                    ->label('Change')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn (int $state): string => 
                        ($state > 0 ? '+' : '') . $state
                    ),
                    
                Tables\Columns\TextColumn::make('previous_quantity')
                    ->label('Previous')
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('new_quantity')
                    ->label('New Stock')
                    ->alignCenter()
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('movement_date')
                    ->label('Date & Time')
                    ->dateTime()
                    ->since()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(30)
                    ->tooltip(fn (StockMovement $record): ?string => $record->notes)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('movement_type')
                    ->label('Movement Type')
                    ->options([
                        'sale' => 'Sales',
                        'purchase' => 'Purchases',
                        'adjustment' => 'Adjustments',
                        'return' => 'Returns',
                    ]),
                    
                Tables\Filters\SelectFilter::make('product_variant')
                    ->label('Product')
                    ->relationship('productVariant.product', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\Filter::make('stock_in')
                    ->label('Stock In Only')
                    ->query(fn (Builder $query): Builder => $query->where('quantity_change', '>', 0))
                    ->toggle(),
                    
                Tables\Filters\Filter::make('stock_out')
                    ->label('Stock Out Only')
                    ->query(fn (Builder $query): Builder => $query->where('quantity_change', '<', 0))
                    ->toggle(),
                    
                Tables\Filters\Filter::make('today')
                    ->label('Today Only')
                    ->query(fn (Builder $query): Builder => $query->whereDate('movement_date', today()))
                    ->toggle(),
                    
                Tables\Filters\Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('movement_date', [now()->startOfWeek(), now()->endOfWeek()]))
                    ->toggle(),
            ])
            ->actions([
                // View only - no edit/delete for audit trail
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\ExportBulkAction::make()
                        ->label('Export Selected'),
                ]),
            ])
            ->defaultSort('movement_date', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockMovements::route('/'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false; // Stock movements should only be created automatically
    }
    
    public static function canDelete($record): bool
    {
        return false; // Stock movements should not be deleted for audit purposes
    }
}