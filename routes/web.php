<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\POS;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/pos', POS::class)->name('pos');
    Route::get('/receipt/{sale}', [App\Http\Controllers\ReceiptController::class, 'show'])->name('receipt.show');
    Route::get('/receipt/{sale}/print', [App\Http\Controllers\ReceiptController::class, 'print'])->name('receipt.print');
});
