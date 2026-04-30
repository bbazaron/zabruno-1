<?php

use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\YooKassaController;
use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return response()->json(['message' => 'API works']);
});

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [UserController::class, 'logout']);
    Route::get('/getUser', [UserController::class, 'getUser']);
    Route::put('/updateProfile', [UserController::class, 'updateProfile']);
    Route::get('/getOrders', [OrderController::class, 'getOrders']);
    Route::post('/createCartOrder', [OrderController::class, 'createCartOrder']);
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::patch('/cart/{itemId}', [CartController::class, 'update']);
    Route::delete('/cart/{itemId}', [CartController::class, 'remove']);

    Route::middleware('role:admin,super_admin')->group(function () {
        Route::get('/admin/admins', [UserController::class, 'getAdmins']);
        Route::get('/admin/orders', [OrderController::class, 'getAllOrders']);
        Route::get('/admin/orders/{id}', [OrderController::class, 'getAdminOrderById']);
        Route::patch('/admin/orders/{id}', [OrderController::class, 'updateAdminOrder']);
        Route::patch('/admin/orders/{id}/status', [OrderController::class, 'updateAdminOrderStatus']);
        Route::post('/admin/orders/{id}/refunds', [OrderController::class, 'createAdminRefund']);

        Route::get('/admin/products', [AdminProductController::class, 'index']);
        Route::post('/admin/products', [AdminProductController::class, 'store']);
        Route::patch('/admin/products/{id}', [AdminProductController::class, 'update']);
        Route::delete('/admin/products/{id}', [AdminProductController::class, 'destroy']);
    });

    Route::middleware('role:super_admin')->group(function () {
        Route::post('/admin/admins', [UserController::class, 'createAdmin']);
    });
});
Route::post('/yookassa/webhook', [YooKassaController::class, 'handle'])->middleware('throttle:30,1');

Route::get('/index', [ProductController::class, 'index']);
Route::get('/product/{id}', [ProductController::class, 'getProductPage']);
Route::middleware('auth:sanctum')->get('/payment/status/latest', [OrderController::class, 'latestPaymentStatus']);

Route::post('/createOrder', [OrderController::class, 'createOrder']);
Route::post('/orderEstimateTotal', [OrderController::class, 'estimateOrderTotal']);

