<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/{id}', [OrderController::class, 'show']);
Route::get('/orders/user/{userId}', [OrderController::class, 'getOrdersByUser']);
Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);