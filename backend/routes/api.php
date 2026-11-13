<?php

use App\Http\Controllers\AdminCheckoutItemController;
use App\Http\Controllers\AdminHomeOrderButtonsSettingController;
use App\Http\Controllers\AdminPickupSettingController;
use App\Http\Controllers\AdminProductCategoryController;
use App\Http\Controllers\AdminSchoolColorSettingController;
use App\Http\Controllers\AdminSchoolController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\CheckoutItemController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\HomeOrderButtonsSettingController;
use App\Http\Controllers\PickupSettingController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\YooKassaController;
use Illuminate\Support\Facades\Route;
Route::post('/register', [UserController::class, 'register'])->middleware('throttle:5,1');
Route::post('/register/confirm', [UserController::class, 'confirmRegistration'])->middleware('throttle:10,1');
Route::post('/register/complete', [UserController::class, 'completeRegistration'])->middleware('throttle:10,1');
Route::post('/register/resend', [UserController::class, 'resendRegistrationCode'])->middleware('throttle:5,1');
Route::post('/login', [UserController::class, 'login']);
Route::post('/password/forgot', [PasswordResetController::class, 'forgot'])->middleware('throttle:5,1');
Route::post('/password/verify', [PasswordResetController::class, 'verify'])->middleware('throttle:10,1');
Route::post('/password/reset', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [UserController::class, 'logout']);
    Route::get('/getUser', [UserController::class, 'getUser']);
    Route::put('/updateProfile', [UserController::class, 'updateProfile']);
    Route::get('/getOrders', [OrderController::class, 'getOrders']);
    Route::get('/payment/status/latest', [OrderController::class, 'latestPaymentStatus']);
    Route::delete('/orders/{id}', [OrderController::class, 'deleteMyOrder']);
    Route::post('/orders/{id}/pay', [OrderController::class, 'payMyOrder'])->middleware('throttle:6,1');
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
        Route::delete('/admin/orders/{id}', [OrderController::class, 'deleteAdminOrder']);
        Route::post('/admin/orders/{id}/refunds', [OrderController::class, 'createAdminRefund']);
        Route::post('/admin/orders/{id}/payment-link', [OrderController::class, 'adminPaymentLink'])->middleware('throttle:6,1');

        Route::get('/admin/settings/home-order-buttons', [AdminHomeOrderButtonsSettingController::class, 'show']);
        Route::patch('/admin/settings/home-order-buttons', [AdminHomeOrderButtonsSettingController::class, 'update']);
        Route::get('/admin/settings/pickup-address', [AdminPickupSettingController::class, 'show']);
        Route::patch('/admin/settings/pickup-address', [AdminPickupSettingController::class, 'update']);
        Route::get('/admin/settings/default-school-colors', [AdminSchoolColorSettingController::class, 'show']);
        Route::patch('/admin/settings/default-school-colors', [AdminSchoolColorSettingController::class, 'update']);
        Route::get('/admin/schools', [AdminSchoolController::class, 'index']);
        Route::post('/admin/schools', [AdminSchoolController::class, 'store']);
        Route::patch('/admin/schools/{id}', [AdminSchoolController::class, 'update']);
        Route::delete('/admin/schools/{id}', [AdminSchoolController::class, 'destroy']);

        Route::get('/admin/product-categories', [AdminProductCategoryController::class, 'index']);
        Route::post('/admin/product-categories', [AdminProductCategoryController::class, 'store']);
        Route::delete('/admin/product-categories/{id}', [AdminProductCategoryController::class, 'destroy']);

        Route::get('/admin/products', [AdminProductController::class, 'index']);
        Route::post('/admin/products', [AdminProductController::class, 'store']);
        Route::patch('/admin/products/{id}', [AdminProductController::class, 'update']);
        Route::delete('/admin/products/{id}', [AdminProductController::class, 'destroy']);

        Route::get('/admin/checkout-items', [AdminCheckoutItemController::class, 'index']);
        Route::post('/admin/checkout-items', [AdminCheckoutItemController::class, 'store']);
        Route::patch('/admin/checkout-items/reorder', [AdminCheckoutItemController::class, 'reorder']);
        Route::patch('/admin/checkout-items/{id}', [AdminCheckoutItemController::class, 'update']);
        Route::delete('/admin/checkout-items/{id}', [AdminCheckoutItemController::class, 'destroy']);
    });

    Route::middleware('role:super_admin')->group(function () {
        Route::post('/admin/admins', [UserController::class, 'createAdmin']);
    });
});
Route::post('/yookassa/webhook', [YooKassaController::class, 'handle'])->middleware('throttle:30,1');

Route::get('/schools', [SchoolController::class, 'index']);
Route::get('/product-categories', [ProductCategoryController::class, 'index']);
Route::get('/checkout-items', [CheckoutItemController::class, 'index']);
Route::get('/index', [ProductController::class, 'index']);
Route::get('/product/{id}', [ProductController::class, 'getProductPage']);
Route::get('/settings/pickup-address', [PickupSettingController::class, 'show']);
Route::get('/settings/home-order-buttons', [HomeOrderButtonsSettingController::class, 'show']);

Route::post('/createOrder', [OrderController::class, 'createOrder']);
Route::post('/orderEstimateTotal', [OrderController::class, 'estimateOrderTotal']);

