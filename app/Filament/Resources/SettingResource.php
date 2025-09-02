<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationGroup = 'System';
    
    protected static ?string $navigationLabel = 'Store Settings';
    
    protected static ?string $modelLabel = 'Store Setting';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Setting Details')
                    ->schema([
                        Forms\Components\TextInput::make('display_name')
                            ->label('Setting Name')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => self::getDisplayName($record?->key)),
                            
                        Forms\Components\Hidden::make('key'),
                        
                        self::getFormFieldForKey(),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->disabled()
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Setting')
                    ->getStateUsing(fn ($record) => self::getDisplayName($record->key))
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('current_value')
                    ->label('Current Value')
                    ->getStateUsing(fn ($record) => self::formatValue($record))
                    ->badge()
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Update'),
            ])
            ->defaultSort('key')
            ->paginated(false);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettings::route('/'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false; // Prevent creating new settings
    }
    
    public static function canDelete($record): bool
    {
        return false; // Prevent deleting settings
    }

    private static function getDisplayName(?string $key): string
    {
        return match($key) {
            'store_name' => 'Store Name',
            'store_address' => 'Store Address',
            'store_phone' => 'Store Phone',
            'tax_rate' => 'Tax Rate (%)',
            'points_per_dollar' => 'Loyalty Points per BDT',
            'default_min_stock_level' => 'Default Minimum Stock Level',
            'currency' => 'Currency',
            'receipt_footer' => 'Receipt Footer Message',
            'low_stock_email_alerts' => 'Low Stock Email Alerts',
            'alert_email' => 'Alert Email Address',
            default => ucwords(str_replace('_', ' ', $key ?? '')),
        };
    }

    private static function formatValue($record): string
    {
        if (!$record->value) return 'Not Set';
        
        $value = is_array($record->value) ? $record->value['value'] ?? 'N/A' : $record->value;
        
        return match($record->key) {
            'tax_rate' => $value . '%',
            'low_stock_email_alerts' => $value ? 'Enabled' : 'Disabled',
            'currency' => strtoupper($value),
            default => (string) $value,
        };
    }

    private static function getFormFieldForKey(): Forms\Components\Component
    {
        return Forms\Components\Group::make()
            ->schema([
                // Store Name
                Forms\Components\TextInput::make('value.value')
                    ->label('Store Name')
                    ->required()
                    ->maxLength(255)
                    ->visible(fn ($get) => $get('key') === 'store_name'),
                    
                // Store Address
                Forms\Components\Textarea::make('value.value')
                    ->label('Store Address')
                    ->required()
                    ->rows(3)
                    ->visible(fn ($get) => $get('key') === 'store_address'),
                    
                // Store Phone
                Forms\Components\TextInput::make('value.value')
                    ->label('Store Phone')
                    ->required()
                    ->tel()
                    ->visible(fn ($get) => $get('key') === 'store_phone'),
                    
                // Tax Rate
                Forms\Components\TextInput::make('value.value')
                    ->label('Tax Rate (%)')
                    ->required()
                    ->numeric()
                    ->step(0.01)
                    ->suffix('%')
                    ->helperText('Enter tax rate as percentage (e.g., 8.5 for 8.5%)')
                    ->visible(fn ($get) => $get('key') === 'tax_rate'),
                    
                // Points per Dollar
                Forms\Components\TextInput::make('value.value')
                    ->label('Loyalty Points per BDT')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->helperText('How many points customers earn per BDT spent')
                    ->visible(fn ($get) => $get('key') === 'points_per_dollar'),
                    
                // Default Min Stock Level
                Forms\Components\TextInput::make('value.value')
                    ->label('Default Minimum Stock Level')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->helperText('Default minimum stock level for new products')
                    ->visible(fn ($get) => $get('key') === 'default_min_stock_level'),
                    
                // Currency (Fixed to BDT)
                Forms\Components\TextInput::make('value.value')
                    ->label('Currency')
                    ->default('BDT')
                    ->disabled()
                    ->helperText('System currency is fixed to BDT (Bangladeshi Taka)')
                    ->visible(fn ($get) => $get('key') === 'currency'),
                    
                // Receipt Footer
                Forms\Components\Textarea::make('value.value')
                    ->label('Receipt Footer Message')
                    ->rows(2)
                    ->maxLength(500)
                    ->helperText('Message displayed at the bottom of receipts')
                    ->visible(fn ($get) => $get('key') === 'receipt_footer'),
                    
                // Email Alerts
                Forms\Components\Toggle::make('value.value')
                    ->label('Enable Low Stock Email Alerts')
                    ->helperText('Send email notifications when products are low in stock')
                    ->visible(fn ($get) => $get('key') === 'low_stock_email_alerts'),
                    
                // Alert Email
                Forms\Components\TextInput::make('value.value')
                    ->label('Alert Email Address')
                    ->email()
                    ->helperText('Email address to receive stock alerts')
                    ->visible(fn ($get) => $get('key') === 'alert_email'),
            ]);
    }
}