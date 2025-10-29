<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\ForgetPasswordController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\Auth\CheckOTPController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;


// Authentication(Unprotected Routes)
Route::post('register', RegisterController::class);
Route::post('login', [LoginController::class, '__invoke'])->name('login');


// ---------------------------
// Public Routes
// ---------------------------
Route::post('forgot-password', [ForgetPasswordController::class, 'sendResetLinkEmail']);
Route::post('reset-password', [ResetPasswordController::class, 'resetPassword'])->name('password.reset');
Route::post('/check-otp', [CheckOTPController::class, 'checkOTP']);
Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify']);

// ---------------------------
// Protected Routes (Requires Login)
// ---------------------------
Route::middleware(['auth:sanctum'])->group(function () {

    // Common user actions
    Route::post('/logout', [LogoutController::class, 'logout']);
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'sendVerificationEmail']);

    // ---------------------------
    // Admin Routes
    // ---------------------------
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('products', ProductController::class);
    });

    // ---------------------------
    // User Routes
    // ---------------------------
    Route::middleware(['role:user'])->group(function () {
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{id}', [ProductController::class, 'show']);

        // Cart routes (for logged-in users only)
        Route::prefix('cart')->group(function () {
            Route::get('/', [CartController::class, 'index']);
            Route::post('/add', [CartController::class, 'add']);
            Route::put('/update/{id}', [CartController::class, 'update']);
            Route::delete('/remove/{id}', [CartController::class, 'remove']);
        });
    });
});
