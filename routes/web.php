<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/payment',function ()
{
    return view('test/vnpay');
});

Route::post('vnpay',[\App\Http\Controllers\TestPaymentController::class,'donhang'])->name('vnpay');
Route::get('response',[\App\Http\Controllers\TestPaymentController::class,'responeVNPAY']);
Route::get('/clear-cache-all', function() {
    Artisan::call('cache:clear');
    dd("Cache Clear All");
});
