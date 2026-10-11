<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CartController;

Route::get('/carts/{userId}', [CartController::class, 'show']);
Route::post('/carts', [CartController::class, 'store']);
Route::put('/carts/items/{id}', [CartController::class, 'update']);
Route::delete('/carts/items/{id}', [CartController::class, 'destroy']);
Route::delete('/carts/{userId}', [CartController::class, 'clear']);