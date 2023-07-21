<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('admin')->group(function(){
    Route::controller(AuthController::class)->group(function(){
        Route::post('/add-temp-admin', 'storeAdmin')->name('admin.addTempAdmin');
        Route::get('/by-token/{token}', 'byToken')->name('admin.byToken');
        Route::post('activate-account', 'activate_account')->name('admin.activateAccount');
        Route::post('/login', 'login')->name('admin.login');
        Route::post('/forgot-password', 'forgot_password')->name('admin.forgotPassword');
        Route::post('/reset-password', 'reset_password')->name('admin.resetPassword');
    });

    Route::middleware('auth:admin-api')->group(function(){
        Route::controller(AuthController::class)->group(function(){
            Route::get('/me', 'me')->name('admin.me');
            Route::post('/change-password', 'change_password')->name('admin.changePassword');
        });

        Route::controller(AdminController::class)->group(function(){
            Route::get('/admins', 'index')->name('admin.index');
            Route::post('/admins', 'store')->name('admin.store');
            Route::get('/admins/{admin}/resend-link', 'resend_activation_link')->name('admin.resendActivationLink');
            Route::get('/admins/{admin}', 'show')->name('admin.show');
            Route::put('/admins/{admin}', 'update')->name('admin.update');
            Route::get('/admins/{admin}/activation', 'account_activation')->name('admin.accountActivation');
            Route::delete('/admins/{admin}', 'destroy')->name('admin.delete');
        });
    });
});
