<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Health Check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'order-service',
    ], 200);
});

// Checkout
Route::post('/orders', [OrderController::class, 'checkout']);

// Daftar pesanan berdasarkan user
Route::get('/orders/user/{userId}', [OrderController::class, 'getByUser'])
    ->whereNumber('userId');

// Pembayaran pesanan
Route::patch('/orders/{id}/pay', [OrderController::class, 'pay'])
    ->whereNumber('id');

// Detail pesanan
Route::get('/orders/{id}', [OrderController::class, 'show'])
    ->whereNumber('id');
    Route::fallback(function () {
    return response()->json([
        'message' => 'Pesanan atau endpoint tidak ditemukan.',
    ], 404);
});