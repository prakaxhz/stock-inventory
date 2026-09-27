<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderPageController;

Route::get('/', [OrderPageController::class, 'create'])->name('orders.create');
Route::get('/orders/history', [OrderPageController::class, 'history'])->name('orders.history');
