<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class POSLauncher extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    
    protected static ?string $navigationGroup = 'Sales';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $title = 'Point of Sale';
    
    protected static ?string $navigationLabel = 'POS System';

    protected static string $view = 'filament.pages.p-o-s-launcher';
    
    public function openPOS()
    {
        return redirect('/pos');
    }
}
