<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\ForgetPasswordController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\Auth\CheckOTPController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;


// Public Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', RegisterController::class);
    Route::post('/login', [LoginController::class, '__invoke'])->name('login');
    Route::post('/forgot-password', [ForgetPasswordController::class, 'sendResetLinkEmail']);
    Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword'])->name('password.reset');
    Route::post('/check-otp', [CheckOTPController::class, 'checkOTP']);
    Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify']);
});

// Protected Routes (Requires Login)
Route::middleware(['auth:sanctum'])->group(function () {

    // Common user actions
    Route::post('/logout', [LogoutController::class, 'logout']);
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'sendVerificationEmail']);

    // Admin Routes
    Route::prefix('admin')->middleware(['role:admin'])->group(function () {
        Route::resource('products', ProductController::class);
        Route::resource('orders', OrderController::class);
    });

    // User Routes
    Route::middleware('role:user')->group(function () {

        // Products
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'index']);
            Route::get('/{id}', [ProductController::class, 'show']);
        });

        // Cart
        Route::prefix('cart')->group(function () {
            Route::get('/', [CartController::class, 'index']);
            Route::post('/add', [CartController::class, 'add']);
            Route::put('/update/{id}', [CartController::class, 'update']);
            Route::delete('/remove/{id}', [CartController::class, 'remove']);
        });

        // Orders
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::get('/{id}', [OrderController::class, 'show']);
            Route::post('/checkout', [OrderController::class, 'store']);
            Route::put('/{id}', [OrderController::class, 'update']);
            Route::delete('/{id}', [OrderController::class, 'destroy']);
            Route::put('/{id}/cancel', [OrderController::class, 'cancel']);
        });

        // Payment
        Route::post('payment/initiate', [PaymentController::class, 'Paymentprocess']);
    });
});


Route::get('payment/callback', [PaymentController::class, 'callBack'])->name('payment.callback');
