<?php

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

    Route::middleware('role:admin,super_admin')->group(function () {
        Route::get('/admin/admins', [UserController::class, 'getAdmins']);
        Route::get('/admin/orders', [OrderController::class, 'getAllOrders']);
        Route::get('/admin/orders/{id}', [OrderController::class, 'getAdminOrderById']);
        Route::patch('/admin/orders/{id}', [OrderController::class, 'updateAdminOrder']);
        Route::patch('/admin/orders/{id}/status', [OrderController::class, 'updateAdminOrderStatus']);
    });

    Route::middleware('role:super_admin')->group(function () {
        Route::post('/admin/admins', [UserController::class, 'createAdmin']);
    });
});
Route::post('/yookassa/webhook', [YooKassaController::class, 'handle']);

Route::get('/index', [ProductController::class, 'index']);
Route::get('/product/{id}', [ProductController::class, 'getProductPage']);

Route::post('/createOrder', [OrderController::class, 'createOrder']);
Route::post('/orderEstimateTotal', [OrderController::class, 'estimateOrderTotal']);

