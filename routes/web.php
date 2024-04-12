<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-stripe-payment', function(){
    return view('initiate_stripe_payment');
});

Route::get('/confirm-payment', function(){
    return view('confirm_stripe_payment');
});

Route::get('/verify-email', function(){
    return view('verify_email');
});
