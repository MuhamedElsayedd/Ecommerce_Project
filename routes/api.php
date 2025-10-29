<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\ForgetPasswordController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\Auth\CheckOTPController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;


// Authentication(Unprotected Routes)
Route::post('register', RegisterController::class);
Route::post('login', [LoginController::class, '__invoke'])->name('login');


// Protected Routes
Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('forgot-password', [ForgetPasswordController::class, 'sendResetLinkEmail']);
    Route::post('reset-password', [ResetPasswordController::class, 'resetPassword'])->name('password.reset');
    Route::post('logout', [LogoutController::class, 'logout']);
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'sendVerificationEmail']);
    Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify']);
    Route::post('/check-otp', [CheckOTPController::class, 'checkOTP']);


    Route::middleware(['role:admin'])->group(function () {
        Route::resource('products', ProductController::class);
    });

    Route::middleware(['role:user'])->group(function () {
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
    });
});
