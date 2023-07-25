<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BasicQuestionController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DailyQuestionController;
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

        Route::controller(CategoryController::class)->group(function(){
            Route::get('/categories', 'index')->name('admin.categories.index');
            Route::post('/categories', 'store')->name('admin.categories.store');
            Route::get('/categories/{category}', 'show')->name('admin.categories.show');
            Route::put('/categories/{category}', 'update')->name('admin.categories.update');
            Route::delete('/categories/{category}', 'destroy')->name('admin.categories.delete');
        });

        Route::controller(DailyQuestionController::class)->group(function(){
            Route::get('/daily-questions', 'index')->name('admin.dailyQuestions.index');
            Route::post('/daily-questions', 'store')->name('admin.dailyQuestions.store');
            Route::get('/daily-questions/{question}', 'show')->name('admin.dailyQuestions.show');
            Route::put('/daily-questions/{question}', 'update')->name('admin.dailyQuestions.update');
            Route::post("/daily-questions/{question_id}/options", 'add_option')->name('admin.dailyQuestions.options.store');
            Route::delete('/daily-questions/options/{option}', 'remove_option')->name('admin.dailyQuestions.options.delete');
            Route::delete('/daily-questions/{question}', 'destroy')->name('admin.dailyQuestions.delete');
        });

        Route::controller(BasicQuestionController::class)->group(function(){
            Route::post('/basic-question-options', 'add_options')->name('admin.basicQuestionOption.store');
            Route::get('/basic-question-options', 'fetch_options')->name('admin.basicQuestionOption.index');
            Route::put('/basic-question-options', 'update_options')->name('admin.basicQuestionOption.update');
            Route::delete('/basic-question-options/{option}', 'remove_option')->name('admin.basicQuestionOption.delete');
            Route::get('/basic-questions', 'index')->name('admin.basicQuestion.index');
            Route::post('/basic-questions', 'store')->name('admin.basicQuestion.store');
            Route::get('/basic-questions/{question}', 'show')->name('admin.basicQuestion.show');
            Route::put('/basic-questions/{question}', 'update')->name('admin.basicQuestion.update');
            Route::put('/basic-questions/{question}/set-prerequisite', 'give_prerequisite')->name('admin.basicQuestion.setPrerequisite');
            Route::delete('/basic-question-special-options/{option}', 'delete_special_option')->name('admin.basicQuestionSpeciaOption.delete');
            Route::delete('/basic-questions/{question}', 'destroy')->name('admin.basicQuestion.delete');
        });
    });
});
