<?php

use App\Livewire\Orders\Create;
use App\Livewire\Orders\Index;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', 'orders')->name('dashboard');
    Route::livewire('orders', Index::class)->name('orders.index');
    Route::livewire('orders/create', Create::class)->name('orders.create');
});

require __DIR__.'/settings.php';
