<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseResource\Pages;
use App\Models\Purchase;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    
    protected static ?string $navigationGroup = 'Inventory';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Purchase Information')
                    ->schema([
                        Forms\Components\TextInput::make('purchase_number')
                            ->label('Purchase Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated on save'),
                            
                        Forms\Components\Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('contact_person')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->tel(),
                                Forms\Components\TextInput::make('email')
                                    ->email(),
                                Forms\Components\Textarea::make('address')
                                    ->rows(3),
                            ]),
                            
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->default(auth()->id())
                            ->required()
                            ->label('Purchasing Officer'),
                            
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'received' => 'Received',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending')
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Financial Details')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->required()
                            ->numeric()
                            ->prefix('৳')
                            ->step(0.01),
                            
                        Forms\Components\TextInput::make('tax_amount')
                            ->required()
                            ->numeric()
                            ->default(0.00)
                            ->prefix('৳')
                            ->step(0.01),
                            
                        Forms\Components\TextInput::make('total_amount')
                            ->required()
                            ->numeric()
                            ->prefix('৳')
                            ->step(0.01),
                    ])
                    ->columns(3),
                    
                Forms\Components\Section::make('Dates & Notes')
                    ->schema([
                        Forms\Components\DateTimePicker::make('purchase_date')
                            ->required()
                            ->default(now())
                            ->native(false),
                            
                        Forms\Components\DatePicker::make('expected_delivery')
                            ->label('Expected Delivery Date')
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
                Tables\Columns\TextColumn::make('purchase_number')
                    ->label('Purchase #')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'received' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('BDT')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('purchase_date')
                    ->label('Purchase Date')
                    ->dateTime()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('expected_delivery')
                    ->label('Expected Delivery')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Officer')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'received' => 'Received',
                        'cancelled' => 'Cancelled',
                    ]),
                    
                Tables\Filters\SelectFilter::make('supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('receive')
                    ->label('Mark as Received')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Purchase $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(fn (Purchase $record) => $record->update(['status' => 'received'])),
                    
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_received')
                        ->label('Mark as Received')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => 
                            $records->each->update(['status' => 'received'])),
                            
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'edit' => Pages\EditPurchase::route('/{record}/edit'),
        ];
    }
}